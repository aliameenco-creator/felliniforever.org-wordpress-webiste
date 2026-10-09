/* Progressive enhancement: content and forms remain usable without JavaScript. */
(() => {
  'use strict';
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const headline = document.querySelector('.hero-copy h1');
  if (headline && headline.textContent === 'There is no end. There is no beginning.') {
    headline.textContent = '';
    ['There is no end.', 'There is no beginning.'].forEach((text, i) => { const line = document.createElement('span'); line.className = 'hero-line'; line.style.setProperty('--line-delay', `${i * .16}s`); line.textContent = text; headline.append(line); });
  }
  const nav = document.querySelector('#site-navigation');
  const toggle = document.querySelector('.menu-toggle');
  const closeMenu = () => { nav?.classList.remove('menu-open'); toggle?.setAttribute('aria-expanded', 'false'); };
  toggle?.addEventListener('click', () => { const open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); nav.classList.toggle('menu-open', open); });
  document.querySelectorAll('.menu-item-has-children').forEach((li, i) => {
    let button = li.querySelector(':scope > .submenu-toggle');
    const sub = li.querySelector(':scope > .sub-menu');
    if (!sub) return;
    sub.id = `fellini-submenu-${i}`;
    if (!button) { button = document.createElement('button'); button.className = 'submenu-toggle'; button.textContent = '⌄'; button.setAttribute('aria-label', `Open ${li.querySelector('a').textContent} submenu`); li.insertBefore(button, sub); }
    button.setAttribute('aria-expanded', 'false'); button.setAttribute('aria-controls', sub.id);
    button.addEventListener('click', () => { const open = !li.classList.contains('open'); li.classList.toggle('open', open); button.setAttribute('aria-expanded', String(open)); });
  });
  document.addEventListener('click', event => { if (!event.target.closest('.site-header')) closeMenu(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') { closeMenu(); document.querySelectorAll('.menu-item-has-children.open').forEach(li => { li.classList.remove('open'); li.querySelector('button')?.setAttribute('aria-expanded', 'false'); }); } });
  if (!reduced && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.remove('reveal-pending'); observer.unobserve(entry.target); } }), { threshold: .05 });
    document.querySelectorAll('.section:not(.quote-band), .gallery-card').forEach(el => {
      // Never hide enormous sections or content already visible on screen.
      if (el.getBoundingClientRect().top < innerHeight || el.offsetHeight > innerHeight * 1.5) return;
      el.classList.add('reveal-ready', 'reveal-pending'); observer.observe(el);
    });
    // Recover visible content after fast anchors, back/forward restore or stalled observers.
    const recover = () => document.querySelectorAll('.reveal-pending').forEach(el => { const box = el.getBoundingClientRect(); if (box.top < innerHeight && box.bottom > 0) el.classList.remove('reveal-pending'); });
    addEventListener('pageshow', recover); addEventListener('hashchange', recover); setTimeout(recover, 1800);
  }
  const dialog = document.querySelector('#gallery-lightbox');
  document.querySelectorAll('.lightbox-link').forEach(link => link.addEventListener('click', event => {
    if (!dialog?.showModal) return;
    event.preventDefault(); const img = link.querySelector('img'); dialog.querySelector('img').src = link.href; dialog.querySelector('img').alt = img.alt; dialog.querySelector('p').textContent = img.alt; dialog.showModal();
  }));
  dialog?.querySelector('button').addEventListener('click', () => dialog.close());
  dialog?.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  document.querySelectorAll('[data-viewer]').forEach(link => link.addEventListener('click', event => {
    event.preventDefault(); const iframe = document.createElement('iframe'); iframe.src = link.dataset.viewer; iframe.title = link.textContent; iframe.loading = 'lazy'; iframe.allowFullscreen = true; iframe.referrerPolicy = 'strict-origin-when-cross-origin'; link.replaceWith(iframe);
  }));
  const form = document.querySelector('[data-fellini-form]');
  if (form) {
    const started = performance.now();
    form.addEventListener('submit', async event => {
      event.preventDefault(); const submit = form.querySelector('[type=submit]'); const status = form.querySelector('.form-status');
      form.querySelector('[data-elapsed]').value = String(Math.round(performance.now() - started));
      form.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; });
      form.querySelectorAll('[aria-invalid]').forEach(el => el.removeAttribute('aria-invalid'));
      submit.disabled = true; status.textContent = 'Sending…';
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
        const result = await response.json(); status.textContent = result.message;
        Object.entries(result.errors || {}).forEach(([key, message]) => { const field = form.querySelector(`[name="fellini_${key}"]`); if (field) { field.setAttribute('aria-invalid', 'true'); field.closest('.field').querySelector('.field-error').textContent = message; } });
        if (result.ok) form.reset(); else form.querySelector('[aria-invalid=true]')?.focus();
      } catch (_) { status.textContent = 'Your message could not be confirmed. Please try again.'; }
      finally { submit.disabled = false; }
    });
  }
})();
