(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (window.gsap && window.ScrollTrigger && !reduce) {
    gsap.registerPlugin(ScrollTrigger);
    const heroIntro = gsap.timeline({ defaults: { ease: 'power3.out' } });
    heroIntro
      .fromTo('.hero-copy .eyebrow', { autoAlpha: 0, y: 16 }, { autoAlpha: 1, y: 0, duration: 0.55 })
      .fromTo('.hero-copy h1 span', { autoAlpha: 0, y: 34, skewY: 2 }, { autoAlpha: 1, y: 0, skewY: 0, duration: 0.72, stagger: 0.14 }, '-=0.24')
      .fromTo('.hero-copy .hero-kicker, .hero-copy .hero-description', { autoAlpha: 0, y: 15 }, { autoAlpha: 1, y: 0, duration: 0.5, stagger: 0.09 }, '-=0.33')
      .fromTo('.hero-copy .hero-actions', { autoAlpha: 0, y: 16 }, { autoAlpha: 1, y: 0, duration: 0.5 }, '-=0.22')
      .fromTo('.hero-copy .hero-foot', { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.45 }, '-=0.12')
      .fromTo('.hero-stage .stage-label, .hero-stage .hero-core, .hero-stage .holo-card, .hero-stage .stage-caption', { autoAlpha: 0, y: 14 }, { autoAlpha: 1, y: 0, duration: 0.58, stagger: 0.1 }, '-=0.85');
    gsap.to('.hero-grid', { yPercent: 14, ease: 'none', scrollTrigger: { trigger: '#home', start: 'top top', end: 'bottom top', scrub: 0.5 } });
    gsap.to('.hero-stage', { y: 36, opacity: 0.65, ease: 'none', scrollTrigger: { trigger: '#home', start: 'top top', end: 'bottom top', scrub: 0.6 } });
    gsap.utils.toArray('.section-heading,.about-content,.orbital-copy,.orbital-visual,.technology-copy,.tech-cloud,.contact-heading,.contact-form').forEach(el => gsap.from(el, { y: 25, opacity: 0, duration: .75, ease: 'power2.out', scrollTrigger: { trigger: el, start: 'top 86%', once: true } }));
    gsap.from('.timeline article', { y: 22, opacity: 0, stagger: .12, duration: .55, ease: 'power2.out', scrollTrigger: { trigger: '.timeline', start: 'top 78%', once: true } });
    gsap.to('.timeline-progress i', { width: '100%', ease: 'none', scrollTrigger: { trigger: '.timeline', start: 'top 75%', end: 'bottom 70%', scrub: .6 } });
    gsap.utils.toArray('.service-card,.why-grid article').forEach(el => gsap.from(el, { y: 20, opacity: 0, duration: .55, scrollTrigger: { trigger: el, start: 'top 90%', once: true } }));
  } else {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); } }), { threshold: .12 });
    document.querySelectorAll('.section-heading,.about-content,.orbital-copy,.orbital-visual,.technology-copy,.tech-cloud,.contact-heading,.contact-form,.service-card,.why-grid article').forEach(el => { el.classList.add('reveal'); observer.observe(el); });
    document.querySelector('.timeline-progress i')?.style.setProperty('height', '100%');
  }
  document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('pointermove', event => {
      const box = card.getBoundingClientRect();
      const px = (event.clientX - box.left) / box.width;
      const py = (event.clientY - box.top) / box.height;
      card.style.setProperty('--mx', `${px * 100}%`); card.style.setProperty('--my', `${py * 100}%`);
      if (innerWidth > 760 && !reduce) card.style.transform = `perspective(800px) rotateX(${(py - .5) * -3}deg) rotateY(${(px - .5) * 4}deg) translateY(-3px)`;
    });
    card.addEventListener('pointerleave', () => { card.style.transform = ''; });
  });
  const cursor = document.querySelector('.cursor-glow');
  if (cursor && !reduce) addEventListener('pointermove', e => { cursor.style.left = `${e.clientX}px`; cursor.style.top = `${e.clientY}px`; }, { passive: true });
})();
