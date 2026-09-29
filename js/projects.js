(() => {
  const grid = document.getElementById('project-grid');
  const modal = document.getElementById('project-modal');
  const modalContent = document.getElementById('modal-content');
  if (!grid || !modal || !modalContent) return;
  let projects = [];
  const escapeUrl = value => {
    if (typeof value !== 'string' || !value.trim()) return '';
    try { const url = new URL(value, location.origin); return ['http:', 'https:'].includes(url.protocol) ? url.href : ''; }
    catch { return ''; }
  };
  function buildCard(project) {
    const card = document.createElement('article'); card.className = 'project-card reveal'; card.tabIndex = 0; card.setAttribute('role', 'button');
    card.setAttribute('aria-label', `View project: ${project.title}`); card.dataset.category = project.category;
    const image = document.createElement('img'); image.className = 'project-image'; image.src = escapeUrl(project.cover_image); image.alt = `${project.title} project preview`; image.loading = 'lazy'; image.decoding = 'async';
    const copy = document.createElement('div'); copy.className = 'project-card-copy';
    const category = document.createElement('small'); category.textContent = project.category;
    const title = document.createElement('h3'); title.textContent = project.title;
    const description = document.createElement('p'); description.textContent = project.description;
    const action = document.createElement('span'); action.textContent = 'VIEW PROJECT  ↗';
    copy.append(category, title, description, action); card.append(image, copy);
    card.addEventListener('click', () => openProject(project));
    card.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openProject(project); } });
    card.addEventListener('pointerenter', () => window.NexoraRobot?.setState('project'));
    card.addEventListener('pointerleave', () => window.NexoraRobot?.restoreScrollState());
    card.addEventListener('focusin', () => window.NexoraRobot?.setState('project'));
    card.addEventListener('focusout', event => { if (!card.contains(event.relatedTarget)) window.NexoraRobot?.restoreScrollState(); });
    return card;
  }
  function openProject(project) {
    modalContent.replaceChildren();
    const hero = document.createElement('div'); hero.className = 'modal-hero';
    const heroImage = document.createElement('img'); heroImage.src = escapeUrl(project.cover_image); heroImage.alt = `${project.title} project`; heroImage.loading = 'lazy'; heroImage.decoding = 'async'; hero.append(heroImage);
    const category = document.createElement('small'); category.className = 'eyebrow'; category.textContent = `${project.category} / SELECTED WORK`;
    const title = document.createElement('h2'); title.id = 'modal-title'; title.textContent = project.title;
    const description = document.createElement('p'); description.textContent = project.description;
    const tech = document.createElement('p'); tech.className = 'modal-tech'; tech.textContent = `TECHNOLOGIES  ·  ${(project.technologies || []).join('  /  ') || 'Not specified'}`;
    modalContent.append(hero, category, title, description, tech);
    const external = escapeUrl(project.project_url || '');
    if (external) { const link = document.createElement('a'); link.href = external; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.className = 'button button-primary'; link.textContent = 'VISIT PROJECT ↗'; modalContent.append(link); }
    if (Array.isArray(project.gallery) && project.gallery.length) {
      const gallery = document.createElement('div'); gallery.className = 'modal-gallery';
      project.gallery.forEach(item => { const img = document.createElement('img'); img.src = escapeUrl(item.image_path); img.alt = item.alt_text || `${project.title} gallery image`; img.loading = 'lazy'; img.decoding = 'async'; gallery.append(img); });
      modalContent.append(gallery);
    }
    modal.dataset.returnFocus = document.activeElement?.classList.contains('project-card') ? 'project-card' : '';
    modal.returnFocusElement = document.activeElement;
    modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden';
    document.querySelector('.modal-close').focus();
  }
  function close() {
    modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = '';
    if (modal.returnFocusElement?.isConnected) modal.returnFocusElement.focus();
  }
  document.querySelector('.modal-close').addEventListener('click', close);
  modal.addEventListener('click', event => { if (event.target === modal) close(); });
  document.addEventListener('keydown', event => {
    if (!modal.classList.contains('open')) return;
    if (event.key === 'Escape') { close(); return; }
    if (event.key === 'Tab') {
      const focusable = [...modal.querySelectorAll('a[href],button:not([disabled]),[tabindex="0"]')];
      if (!focusable.length) return;
      const first = focusable[0], last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  const filterGroup = document.querySelector('.project-filters');
  if (filterGroup && !filterGroup.querySelector('[data-filter="OTHER"]')) {
    const otherFilter = document.createElement('button');
    otherFilter.type = 'button'; otherFilter.className = 'filter-button'; otherFilter.dataset.filter = 'OTHER'; otherFilter.textContent = 'OTHER';
    filterGroup.append(otherFilter);
  }
  document.querySelectorAll('.filter-button').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.filter-button').forEach(item => item.classList.remove('active')); button.classList.add('active');
    const selected = button.dataset.filter;
    grid.querySelectorAll('.project-card').forEach(card => { card.hidden = selected !== 'ALL' && card.dataset.category !== selected; });
  }));
  fetch('api/projects.php', { headers: { Accept: 'application/json' } }).then(response => response.json()).then(data => {
    projects = Array.isArray(data.projects) ? data.projects.filter(item => item.cover_image && escapeUrl(item.cover_image)) : [];
    if (!projects.length) return;
    grid.replaceChildren(...projects.map(buildCard));
  }).catch(() => { /* The editorial empty state remains visible if the project API is unavailable. */ });
})();
