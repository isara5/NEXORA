(() => {
  const canvas = document.getElementById('ambient-canvas');
  const hero = document.getElementById('home');
  if (!canvas || !hero || !window.THREE) return;

  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  const mobileQuery = matchMedia('(max-width: 760px)');
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(52, innerWidth / innerHeight, 0.1, 80);
  camera.position.set(0, 0, 12);

  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({
      canvas,
      alpha: true,
      antialias: !mobileQuery.matches,
      powerPreference: 'low-power',
      stencil: false,
      depth: true
    });
  } catch {
    canvas.hidden = true;
    return; // The CSS-lit hero remains fully usable when WebGL is unavailable.
  }
  renderer.setPixelRatio(Math.min(devicePixelRatio || 1, mobileQuery.matches ? 1.25 : 1.6));
  renderer.setSize(innerWidth, innerHeight, false);

  const field = new THREE.Group();
  scene.add(field);

  const particleCount = reducedMotion.matches ? 56 : mobileQuery.matches ? 92 : 178;
  const positions = new Float32Array(particleCount * 3);
  const colors = new Float32Array(particleCount * 3);
  const sizes = new Float32Array(particleCount);
  const alphas = new Float32Array(particleCount);
  const cyan = new THREE.Color('#63e8f7');
  const blue = new THREE.Color('#7895ff');
  const white = new THREE.Color('#d9ffff');

  for (let i = 0; i < particleCount; i++) {
    const offset = i * 3;
    // Keep the brightest cluster around the hero artwork, with quiet depth at the edges.
    positions[offset] = (Math.random() - 0.5) * (mobileQuery.matches ? 15 : 25);
    positions[offset + 1] = (Math.random() - 0.5) * 14;
    positions[offset + 2] = (Math.random() - 0.5) * 11;
    const tint = Math.random() > 0.77 ? blue : Math.random() > 0.87 ? white : cyan;
    colors[offset] = tint.r;
    colors[offset + 1] = tint.g;
    colors[offset + 2] = tint.b;
    sizes[i] = 0.045 + Math.random() * (mobileQuery.matches ? 0.07 : 0.1);
    alphas[i] = 0.28 + Math.random() * 0.55;
  }

  const particleGeometry = new THREE.BufferGeometry();
  particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  particleGeometry.setAttribute('aColor', new THREE.BufferAttribute(colors, 3));
  particleGeometry.setAttribute('aSize', new THREE.BufferAttribute(sizes, 1));
  particleGeometry.setAttribute('aAlpha', new THREE.BufferAttribute(alphas, 1));

  const particleMaterial = new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    blending: THREE.AdditiveBlending,
    uniforms: { uPixelRatio: { value: renderer.getPixelRatio() } },
    vertexShader: `
      attribute vec3 aColor;
      attribute float aSize;
      attribute float aAlpha;
      uniform float uPixelRatio;
      varying vec3 vColor;
      varying float vAlpha;
      void main() {
        vColor = aColor;
        vAlpha = aAlpha;
        vec4 viewPosition = modelViewMatrix * vec4(position, 1.0);
        gl_Position = projectionMatrix * viewPosition;
        gl_PointSize = clamp(aSize * uPixelRatio * (240.0 / -viewPosition.z), 1.0, 7.0);
      }
    `,
    fragmentShader: `
      varying vec3 vColor;
      varying float vAlpha;
      void main() {
        float radius = length(gl_PointCoord - vec2(0.5));
        float core = 1.0 - smoothstep(0.04, 0.19, radius);
        float halo = 1.0 - smoothstep(0.12, 0.5, radius);
        float alpha = (core * 0.82 + halo * 0.19) * vAlpha;
        if (alpha < 0.015) discard;
        gl_FragColor = vec4(vColor, alpha);
      }
    `
  });
  const particles = new THREE.Points(particleGeometry, particleMaterial);
  field.add(particles);

  // Sparse, fixed links suggest a connected digital network without per-frame geometry work.
  const links = [];
  const maxLinkDistance = mobileQuery.matches ? 2.1 : 2.65;
  for (let i = 0; i < particleCount; i++) {
    let linked = 0;
    for (let j = i + 1; j < particleCount && linked < 2; j++) {
      const dx = positions[i * 3] - positions[j * 3];
      const dy = positions[i * 3 + 1] - positions[j * 3 + 1];
      const dz = positions[i * 3 + 2] - positions[j * 3 + 2];
      if (dx * dx + dy * dy + dz * dz < maxLinkDistance * maxLinkDistance && Math.random() > 0.58) {
        links.push(positions[i * 3], positions[i * 3 + 1], positions[i * 3 + 2]);
        links.push(positions[j * 3], positions[j * 3 + 1], positions[j * 3 + 2]);
        linked++;
      }
    }
  }
  const linkGeometry = new THREE.BufferGeometry();
  linkGeometry.setAttribute('position', new THREE.Float32BufferAttribute(links, 3));
  const linkMaterial = new THREE.LineBasicMaterial({ color: 0x47cde8, transparent: true, opacity: mobileQuery.matches ? 0.055 : 0.085, depthWrite: false });
  field.add(new THREE.LineSegments(linkGeometry, linkMaterial));

  const poly = new THREE.Mesh(
    new THREE.IcosahedronGeometry(mobileQuery.matches ? 3.1 : 4.1, 1),
    new THREE.MeshBasicMaterial({ color: 0x39718d, wireframe: true, transparent: true, opacity: 0.1, depthWrite: false })
  );
  poly.position.set(mobileQuery.matches ? 2.3 : 4.3, -0.45, -3.7);
  field.add(poly);

  const mouse = { x: 0, y: 0, smoothX: 0, smoothY: 0 };
  let heroVisible = true;
  let pageVisible = !document.hidden;
  let frame = 0;
  let scrollProgress = 0;
  let lastFrame = 0;
  let frameInterval = mobileQuery.matches ? 1000 / 30 : 1000 / 45;

  function onPointerMove(event) {
    mouse.x = (event.clientX / innerWidth - 0.5) * 2;
    mouse.y = (event.clientY / innerHeight - 0.5) * 2;
  }
  function onScroll() {
    scrollProgress = Math.min(1, Math.max(0, scrollY / Math.max(hero.offsetHeight, 1)));
  }
  function render(time = 0) {
    frame = 0;
    if (!heroVisible || !pageVisible) return;
    if (time - lastFrame < frameInterval) {
      frame = requestAnimationFrame(render);
      return;
    }
    lastFrame = time;
    mouse.smoothX += (mouse.x - mouse.smoothX) * 0.035;
    mouse.smoothY += (mouse.y - mouse.smoothY) * 0.035;

    if (!reducedMotion.matches) {
      field.rotation.y += 0.00032;
      field.rotation.x += 0.00012;
      field.position.x += (mouse.smoothX * -0.22 - field.position.x) * 0.018;
      field.position.y += (mouse.smoothY * 0.12 + scrollProgress * 0.6 - field.position.y) * 0.018;
      poly.rotation.y += 0.00055;
      poly.rotation.x += 0.0002;
    }
    renderer.render(scene, camera);
    if (!reducedMotion.matches) frame = requestAnimationFrame(render);
  }
  function startRendering() {
    if (!frame && heroVisible && pageVisible) frame = requestAnimationFrame(render);
  }
  function resize() {
    const mobile = mobileQuery.matches;
    frameInterval = mobile ? 1000 / 30 : 1000 / 45;
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setPixelRatio(Math.min(devicePixelRatio || 1, mobile ? 1.25 : 1.6));
    renderer.setSize(innerWidth, innerHeight, false);
    particleMaterial.uniforms.uPixelRatio.value = renderer.getPixelRatio();
    startRendering();
  }

  const heroObserver = new IntersectionObserver(([entry]) => {
    heroVisible = entry.isIntersecting;
    if (heroVisible) startRendering();
    else if (frame) { cancelAnimationFrame(frame); frame = 0; }
  }, { threshold: 0.02 });
  heroObserver.observe(hero);
  addEventListener('pointermove', onPointerMove, { passive: true });
  addEventListener('scroll', onScroll, { passive: true });
  addEventListener('resize', resize, { passive: true });
  mobileQuery.addEventListener?.('change', resize);
  document.addEventListener('visibilitychange', () => {
    pageVisible = !document.hidden;
    if (pageVisible) startRendering();
    else if (frame) { cancelAnimationFrame(frame); frame = 0; }
  });
  reducedMotion.addEventListener?.('change', () => startRendering());
  startRendering();
})();
