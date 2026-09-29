(() => {
  const panel = document.getElementById('chat-panel'), launcher = document.getElementById('assistant-launcher');
  if (!panel || !launcher) return;
  const messages = document.getElementById('chat-messages');
  const responses = {
    'WEB DEVELOPMENT': 'We build modern, responsive websites that balance great design with performance. Tell us about your project in the contact form and the team will follow up.',
    'APP DEVELOPMENT': 'We create web and mobile applications tailored to your business needs. Share your idea with us and we can explore the right approach together.',
    'DIGITAL MARKETING': 'NEXORA offers digital marketing and creative strategy focused on helping businesses reach and engage the right audience.',
    DESIGN: 'From visual identity to social media assets, we can help create a consistent and distinctive look for your brand.',
    'START A PROJECT': 'Great! Use the project request form on this page to share your goals, service needs and contact details. The NEXORA team will get back to you.'
  };
  function open(value) { panel.classList.toggle('open', value); panel.setAttribute('aria-hidden', String(!value)); launcher.setAttribute('aria-expanded', String(value)); if (value) document.getElementById('chat-input').focus(); }
  function addMessage(text, who) { const bubble = document.createElement('div'); bubble.className = `chat-bubble ${who}`; bubble.textContent = text; messages.append(bubble); messages.scrollTop = messages.scrollHeight; }
  function reply(text) {
    const query = text.trim(); if (!query) return;
    addMessage(query, 'user');
    const match = Object.keys(responses).find(key => query.toUpperCase().includes(key) || (key === 'DESIGN' && /graphic|creative|logo/i.test(query)));
    window.setTimeout(() => addMessage(responses[match] || 'Thanks for reaching out. Tell us a little more, or choose “START A PROJECT” and send your request to our team.', 'bot'), 320);
  }
  launcher.addEventListener('click', () => open(!panel.classList.contains('open')));
  document.getElementById('chat-close').addEventListener('click', () => open(false));
  panel.querySelectorAll('.chat-quick button').forEach(button => button.addEventListener('click', () => reply(button.textContent)));
  document.getElementById('chat-form').addEventListener('submit', event => { event.preventDefault(); const input = document.getElementById('chat-input'); reply(input.value); input.value = ''; });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') open(false); });
})();
