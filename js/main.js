(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const header = $('.site-header'), nav = $('.main-nav'), toggle = $('.menu-toggle');
  const start = performance.now();
  const progress = $('#loader-progress'), number = $('#loader-number');
  const preloaderTimer = setInterval(() => {
    const amount = Math.min(100, Math.round((performance.now() - start) / 13));
    if (progress) progress.style.width = `${amount}%`;
    if (number) number.textContent = `${String(amount).padStart(2, '0')}%`;
    if (amount >= 100) { clearInterval(preloaderTimer); setTimeout(() => $('#preloader')?.classList.add('done'), 120); }
  }, 24);
  setTimeout(() => { clearInterval(preloaderTimer); $('#preloader')?.classList.add('done'); }, 2100);
  addEventListener('scroll', () => header.classList.toggle('scrolled', scrollY > 20), { passive: true });
  const closeNavigation = (restoreFocus = false) => {
    toggle?.setAttribute('aria-expanded', 'false');
    toggle?.setAttribute('aria-label', 'Open navigation');
    nav?.classList.remove('open');
    document.body.classList.remove('menu-open');
    if (restoreFocus) toggle?.focus();
  };
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    nav.classList.toggle('open', open);
    document.body.classList.toggle('menu-open', open);
  });
  $$('.main-nav a').forEach(link => link.addEventListener('click', () => closeNavigation()));
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') closeNavigation(true); });
  document.addEventListener('pointerdown', event => {
    if (toggle?.getAttribute('aria-expanded') === 'true' && !nav.contains(event.target) && !toggle.contains(event.target)) closeNavigation();
  });
  const sections = $$('main section[id]');
  const navObserver = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { $$('.main-nav a').forEach(a => a.classList.toggle('active', a.hash === `#${entry.target.id}`)); } }), { rootMargin: '-35% 0px -55% 0px' });
  sections.forEach(section => navObserver.observe(section));
  $$('.orbit-node').forEach(node => node.addEventListener('click', () => {
    $$('.orbit-node').forEach(item => item.classList.remove('active'));
    node.classList.add('active');
    $('#orbit-title').textContent = node.dataset.orbit;
    $('#orbit-description').textContent = node.dataset.description;
    const sphere = $('.nexora-sphere'); sphere.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }], { duration: 450, easing: 'ease-out' });
  }));
  const serviceSlots = { web: 0, app: 1, marketing: 2, 'graphic-design': 3, 'post-editing': 4, custom: 5 };
  fetch('api/services.php', { headers: { Accept: 'application/json' } }).then(response => response.json()).then(data => {
    if (data.available !== true) return; // Keep the built-in catalogue visible while MySQL is not configured.
    const cards = $$('.service-card');
    (data.services || []).forEach(service => {
      const card = cards[serviceSlots[service.service_key]];
      if (!card) return;
      card.hidden = false;
      card.querySelector('h3').textContent = service.title;
      card.querySelector('p').textContent = service.description;
    });
    Object.entries(serviceSlots).forEach(([key, index]) => {
      if (!(data.services || []).some(service => service.service_key === key) && cards[index]) cards[index].hidden = true;
    });
  }).catch(() => {});
  fetch('api/settings.php', { headers: { Accept: 'application/json' } }).then(response => response.json()).then(data => {
    const stats = data.settings || {};
    const statKeys = { projects: 'stat_projects', clients: 'stat_clients', solutions: 'stat_solutions', experience: 'stat_experience' };
    Object.entries(statKeys).forEach(([key, setting]) => {
      const element = document.querySelector(`[data-stat="${key}"]`);
      if (element && stats[setting]) element.textContent = stats[setting];
    });
    const socialTarget = $('#social-links');
    if (socialTarget && typeof stats.social_links === 'string') {
      try {
        const links = JSON.parse(stats.social_links);
        Object.entries(links).forEach(([network, value]) => {
          if (typeof value !== 'string') return;
          try {
            const url = new URL(value);
            if (!['https:', 'http:'].includes(url.protocol)) return;
            const link = document.createElement('a');
            link.href = url.href; link.target = '_blank'; link.rel = 'noopener noreferrer';
            link.textContent = network.replace(/[-_]/g, ' ').trim().toUpperCase();
            socialTarget.append(link);
          } catch { /* Ignore invalid administrator-entered URLs. */ }
        });
      } catch { /* Ignore invalid stored JSON and keep the footer clean. */ }
    }
  }).catch(() => {});
  const form = $('#contact-form');
  form?.addEventListener('submit', async event => {
    event.preventDefault();
    const status = $('#form-status'), button = form.querySelector('button[type=submit]');
    if (!form.reportValidity()) return;
    button.disabled = true; status.textContent = 'Sending your project request…';
    try {
      const response = await fetch('api/contact.php', { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'We could not send your request. Please try again.');
      form.reset(); status.textContent = result.message || 'Thanks — your request has been received.';
    } catch (error) { status.textContent = error.message || 'Something went wrong. Please try again later.'; }
    finally { button.disabled = false; }
  });
})();
