(() => {
  'use strict';

  /* Error state of the Google-Forms style form pages (resources/css/gform.css). Validation itself is
     unchanged: when the browser reports a missing or invalid answer, its card is marked and the field's
     own validation message is shown under it until the answer is valid again or the form is reset. */
  const page = document.querySelector('.gf-page');
  if (!page) return;

  // The sticky navbar changes height with the viewport; gform.css keeps scrolled-to fields below it.
  const navbar = document.querySelector('.portal-navbar');
  if (navbar) {
    const syncNavbar = () => {
      const sticky = ['sticky', 'fixed'].includes(getComputedStyle(navbar).position);
      document.documentElement.style.setProperty('--gf-navbar-h', `${sticky ? navbar.offsetHeight : 0}px`);
    };
    syncNavbar();
    if (window.ResizeObserver) new ResizeObserver(syncNavbar).observe(navbar);
    else window.addEventListener('resize', syncNavbar);
  }

  const alertIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>';
  const fieldsOf = (card) => [...card.querySelectorAll('input, select, textarea')].filter((field) => field.willValidate);

  const clear = (card) => {
    card.classList.remove('is-invalid');
    card.querySelector(':scope > .gf-error')?.remove();
  };
  const mark = (card, field) => {
    card.classList.add('is-invalid');
    let message = card.querySelector(':scope > .gf-error');
    if (!message) {
      message = document.createElement('p');
      message.className = 'gf-error';
      card.append(message);
    }
    message.innerHTML = alertIcon;
    message.append(document.createTextNode(field.validationMessage));
  };

  document.addEventListener('invalid', (event) => {
    const card = event.target.closest?.('.gf-page .gf-card');
    if (!card) return;
    mark(card, event.target);
    // When the browser scrolls to this answer, show its card from the top (question title included).
    const offset = event.target.getBoundingClientRect().top - card.getBoundingClientRect().top;
    if (offset > 0) event.target.style.scrollMarginTop = `${Math.min(Math.round(offset) + 1, 240)}px`;
  }, true);

  const recheck = (event) => {
    const card = event.target.closest?.('.gf-card.is-invalid');
    if (!card) return;
    const invalid = fieldsOf(card).find((field) => !field.validity.valid);
    if (invalid) mark(card, invalid);
    else clear(card);
  };
  document.addEventListener('input', recheck);
  document.addEventListener('change', recheck);

  page.querySelectorAll('form').forEach((form) => form.addEventListener('reset', () => {
    window.setTimeout(() => page.querySelectorAll('.gf-card.is-invalid').forEach(clear), 0);
  }));
})();
