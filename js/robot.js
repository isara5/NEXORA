(() => {
  const robot = document.getElementById('nexora-robot');
  const stage = robot?.closest('.hero-stage');
  if (!robot || !stage) return;

  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  const head = robot.querySelector('.robot-head');
  const eyes = robot.querySelector('.robot-eyes');
  const core = robot.querySelector('.body-core');
  const panel = stage.querySelector('.holo-code');
  const panelSmall = panel?.querySelector('small');
  const panelValue = panel?.querySelector('b');
  const panelDetail = panel?.querySelector('span');
  const caption = stage.querySelector('.stage-caption');
  const stateInfo = {
    hero: { label: 'Floating and waving', title: 'LIVE SYSTEM', value: '0101 1100', detail: 'Building tomorrow', caption: 'YOUR DIGITAL CO-PILOT' },
    about: { label: 'Turning ideas into digital reality', title: 'NEXORA // STUDIO', value: 'IDEA → FORM', detail: 'Technology meets creativity', caption: 'THOUGHTFUL BY DESIGN' },
    services: { label: 'Introducing NEXORA services', title: 'CAPABILITIES', value: '6 / SERVICES', detail: 'Ideas into useful outcomes', caption: 'BUILT AROUND YOUR NEEDS' },
    web: { label: 'Showing a web development interface', title: 'WEB // BUILD', value: '</> 01', detail: 'Responsive digital experiences', caption: 'WEB DEVELOPMENT' },
    app: { label: 'Showing an app development interface', title: 'APP // PROTOTYPE', value: '▣ 02', detail: 'Useful on every screen', caption: 'APP DEVELOPMENT' },
    marketing: { label: 'Showing a digital marketing analytics interface', title: 'GROWTH // SIGNAL', value: '↗ REACH', detail: 'Strategy, content, insight', caption: 'DIGITAL MARKETING' },
    design: { label: 'Showing a graphic design palette', title: 'DESIGN // STUDIO', value: '✳ FORM + COLOR', detail: 'A visual language of your own', caption: 'GRAPHIC DESIGN' },
    creative: { label: 'Showing a creative editing timeline', title: 'MOTION // EDIT', value: '▶ FRAME 05', detail: 'Stories with movement', caption: 'CREATIVE CONTENT' },
    custom: { label: 'Showing a custom digital solution', title: 'SYSTEM // CUSTOM', value: '⌘ NXR', detail: 'Made for your workflow', caption: 'CUSTOM SOLUTIONS' },
    orbital: { label: 'Exploring the NEXORA digital universe', title: 'CONNECTED THINKING', value: 'WEB · APP · AI', detail: 'One core, many possibilities', caption: 'THE NEXORA UNIVERSE' },
    project: { label: 'Opening a project interface', title: 'PROJECT // VIEW', value: '↗ SELECTED', detail: 'Turning plans into launches', caption: 'MADE TO MAKE A MARK' },
    discover: { label: 'Discovering the project brief', title: 'PROCESS // 01', value: 'LISTEN + LEARN', detail: 'Understand the opportunity', caption: '01 — DISCOVER' },
    plan: { label: 'Planning the project', title: 'PROCESS // 02', value: 'MAP THE ROUTE', detail: 'Scope, goals and direction', caption: '02 — PLAN' },
    'design-process': { label: 'Designing the experience', title: 'PROCESS // 03', value: 'FORM + FLOW', detail: 'Define the experience', caption: '03 — DESIGN' },
    develop: { label: 'Developing the solution', title: 'PROCESS // 04', value: 'BUILD // NXR', detail: 'Carefully engineered', caption: '04 — DEVELOP' },
    test: { label: 'Testing the experience', title: 'PROCESS // 05', value: 'CHECK + REFINE', detail: 'Details matter', caption: '05 — TEST' },
    launch: { label: 'Preparing for launch', title: 'PROCESS // 06', value: 'READY ↗', detail: 'Deliver and move forward', caption: '06 — LAUNCH' },
    contact: { label: 'Welcoming a project enquiry', title: 'SIGNAL // RECEIVED', value: 'LET’S TALK', detail: 'Your idea starts here', caption: 'LET’S BUILD SOMETHING GREAT' }
  };
  let state = 'hero';
  let scrollState = 'hero';
  let hoverState = '';
  let visible = true;
  let raf = 0;
  const pointer = { x: 0, y: 0, currentX: 0, currentY: 0 };
  let activeTimeline = null;

  function renderState(nextState, { fromScroll = false } = {}) {
    if (!stateInfo[nextState]) return;
    if (fromScroll) scrollState = nextState;
    state = nextState;
    robot.dataset.state = nextState;
    robot.setAttribute('aria-label', `NEXORA digital assistant, ${stateInfo[nextState].label.toLowerCase()}`);
    if (panelSmall) panelSmall.textContent = stateInfo[nextState].title;
    if (panelValue) panelValue.textContent = stateInfo[nextState].value;
    if (panelDetail) panelDetail.textContent = stateInfo[nextState].detail;
    const captionLabel = caption?.querySelector('[data-robot-caption]');
    if (captionLabel) captionLabel.textContent = stateInfo[nextState].caption;
    else if (caption?.firstChild?.nodeType === Node.TEXT_NODE) caption.firstChild.nodeValue = `${stateInfo[nextState].caption} `;

    if (window.gsap && !reducedMotion.matches) {
      activeTimeline?.kill();
      activeTimeline = gsap.timeline();
      const pulse = ['hero', 'marketing', 'project', 'contact'].includes(nextState) ? 1.12 : 1.04;
      activeTimeline.to(core, { scale: pulse, duration: 0.22, ease: 'power2.out' }).to(core, { scale: 1, duration: 0.42, ease: 'elastic.out(1, 0.7)' });
      gsap.fromTo(panel, { autoAlpha: 0.6, x: 8 }, { autoAlpha: 1, x: 0, duration: 0.32, ease: 'power2.out', overwrite: true });
    }
  }

  function setState(nextState) {
    hoverState = nextState;
    renderState(nextState);
  }
  function restoreScrollState() {
    hoverState = '';
    renderState(scrollState);
  }
  window.NexoraRobot = Object.freeze({ setState, restoreScrollState, getState: () => state });

  function onPointerMove(event) {
    if (reducedMotion.matches || !visible) return;
    const rect = stage.getBoundingClientRect();
    pointer.x = Math.max(-1, Math.min(1, (event.clientX - rect.left - rect.width / 2) / (rect.width / 2)));
    pointer.y = Math.max(-1, Math.min(1, (event.clientY - rect.top - rect.height / 2) / (rect.height / 2)));
    if (!raf) raf = requestAnimationFrame(updateGaze);
  }
  function updateGaze() {
    raf = 0;
    if (!visible || reducedMotion.matches) return;
    pointer.currentX += (pointer.x - pointer.currentX) * 0.16;
    pointer.currentY += (pointer.y - pointer.currentY) * 0.16;
    head?.style.setProperty('--look-x', `${pointer.currentX * 11}deg`);
    head?.style.setProperty('--look-y', `${pointer.currentY * -7}deg`);
    eyes?.style.setProperty('--eye-x', `${pointer.currentX * 2}px`);
    eyes?.style.setProperty('--eye-y', `${pointer.currentY * 1.5}px`);
    if (Math.abs(pointer.x - pointer.currentX) + Math.abs(pointer.y - pointer.currentY) > 0.012) raf = requestAnimationFrame(updateGaze);
  }

  stage.addEventListener('pointermove', onPointerMove, { passive: true });
  stage.addEventListener('pointerleave', () => { pointer.x = 0; pointer.y = 0; });
  document.querySelectorAll('.service-card[data-robot-state],.timeline article[data-robot-state]').forEach(card => {
    const next = card.dataset.robotState;
    card.addEventListener('pointerenter', () => setState(next));
    card.addEventListener('pointerleave', restoreScrollState);
    card.addEventListener('focusin', () => setState(next));
    card.addEventListener('focusout', event => { if (!card.contains(event.relatedTarget)) restoreScrollState(); });
  });

  const sectionTriggers = [
    ['#home', 'hero'], ['#about', 'about'], ['#services', 'services'],
    ['#universe', 'orbital'], ['#projects', 'project'], ['#contact', 'contact']
  ];
  if (window.gsap && window.ScrollTrigger) {
    gsap.registerPlugin(ScrollTrigger);
    sectionTriggers.forEach(([selector, next]) => {
      const target = document.querySelector(selector);
      if (!target) return;
      ScrollTrigger.create({
        trigger: target,
        start: 'top 58%',
        end: 'bottom 42%',
        onEnter: () => renderState(next, { fromScroll: true }),
        onEnterBack: () => renderState(next, { fromScroll: true })
      });
    });
    document.querySelectorAll('.service-card[data-robot-state],.timeline article[data-robot-state]').forEach(card => {
      ScrollTrigger.create({
        trigger: card,
        start: 'top 64%',
        end: 'bottom 36%',
        onEnter: () => renderState(card.dataset.robotState, { fromScroll: true }),
        onEnterBack: () => renderState(card.dataset.robotState, { fromScroll: true })
      });
    });
  } else {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const next = entry.target.dataset.robotState || entry.target.dataset.robotSection;
        if (next) renderState(next, { fromScroll: true });
      });
    }, { rootMargin: '-40% 0px -45% 0px', threshold: 0 });
    sectionTriggers.forEach(([selector, next]) => {
      const target = document.querySelector(selector);
      if (target) { target.dataset.robotSection = next; observer.observe(target); }
    });
    document.querySelectorAll('.service-card[data-robot-state],.timeline article[data-robot-state]').forEach(item => observer.observe(item));
  }

  const visibilityObserver = new IntersectionObserver(([entry]) => {
    visible = entry.isIntersecting;
    if (!visible && raf) { cancelAnimationFrame(raf); raf = 0; }
  }, { threshold: 0.01 });
  visibilityObserver.observe(stage);
  reducedMotion.addEventListener?.('change', () => {
    if (reducedMotion.matches && raf) { cancelAnimationFrame(raf); raf = 0; }
    else if (!reducedMotion.matches) onPointerMove({ clientX: innerWidth / 2, clientY: innerHeight / 2 });
  });
})();
