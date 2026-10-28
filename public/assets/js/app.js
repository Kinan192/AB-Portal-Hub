(() => {
  'use strict';

  const clock = document.getElementById('portal-clock');
  const date = document.getElementById('hero-date');
  const clockFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', weekday: 'short', day: '2-digit', month: 'short',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
  });
  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });
  const tick = () => {
    const now = new Date();
    if (clock) {
      clock.dateTime = now.toISOString();
      clock.querySelector('[data-clock-text]').textContent = `${clockFormatter.format(now)} WIB`;
    }
    if (date) date.textContent = dateFormatter.format(now);
  };
  tick();
  window.setInterval(tick, 1000);

  // Profile menu: opens on click, closes on Esc, outside click or Tab out; arrow keys move between items.
  const menu = document.querySelector('[data-profile-menu]');
  if (menu) {
    const toggle = menu.querySelector('[data-profile-toggle]');
    const list = menu.querySelector('[role="menu"]');
    const items = () => [...list.querySelectorAll('[role="menuitem"]')];
    const setOpen = (open, focusToggle = false) => {
      list.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      if (open) items()[0]?.focus();
      else if (focusToggle) toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(list.hidden));
    toggle.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowDown') { event.preventDefault(); setOpen(true); }
    });
    list.addEventListener('keydown', (event) => {
      const all = items();
      const at = all.indexOf(document.activeElement);
      if (event.key === 'Escape') { event.preventDefault(); setOpen(false, true); }
      else if (event.key === 'ArrowDown') { event.preventDefault(); all[(at + 1) % all.length].focus(); }
      else if (event.key === 'ArrowUp') { event.preventDefault(); all[(at - 1 + all.length) % all.length].focus(); }
      else if (event.key === 'Tab') setOpen(false);
    });
    document.addEventListener('click', (event) => {
      if (!list.hidden && !menu.contains(event.target)) setOpen(false);
    });
  }

  const dialog = document.getElementById('portal-dialog');
  const title = document.getElementById('dialog-title');
  const description = document.getElementById('dialog-description');

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-module]');
    if (!trigger || !dialog) return;
    title.textContent = trigger.dataset.title;
    description.textContent = trigger.dataset.type === 'external' ? 'Alamat aplikasi belum diatur.' : 'Form belum tersedia.';
    dialog.showModal();
  });
  document.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
  });
})();
