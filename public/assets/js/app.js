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

<<<<<<< HEAD
  // The sticky "Kembali ke Dashboard" row sits under the sticky header; the header height (it wraps on
  // phones) and the row's own height go to CSS for the row, the editor toolbar and scroll-padding.
  const navbar = document.querySelector('.portal-navbar');
  const backRow = document.querySelector('.breadcrumb-shell.is-sticky');
  if (navbar && backRow && 'ResizeObserver' in window) {
    const root = document.documentElement.style;
    const measure = () => {
      root.setProperty('--header-h', `${navbar.offsetHeight}px`);
      root.setProperty('--back-h', `${backRow.offsetHeight}px`);
    };
    const watch = new ResizeObserver(measure);
    watch.observe(navbar);
    watch.observe(backRow);
    measure();
  }

=======
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
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

<<<<<<< HEAD
  // Notification bell ("Perlu perhatian"): opens on click, closes on Esc (focus back to the bell) or an outside click.
  const bell = document.querySelector('[data-notif-menu]');
  if (bell) {
    const toggle = bell.querySelector('[data-notif-toggle]');
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));
    const setOpen = (open, focusToggle = false) => {
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      if (open) panel.querySelector('a')?.focus();
      else if (focusToggle) toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    bell.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); setOpen(false, true); }
    });
    document.addEventListener('click', (event) => {
      if (!panel.hidden && !bell.contains(event.target)) setOpen(false);
    });

    // Seen: a clicked report (and "Tandai semua dilihat") fades at once and leaves the badge; the server keeps
    // it per account. keepalive lets the request finish while the link opens the report.
    const badge = toggle.querySelector('[data-notif-badge]');
    const seenAll = panel.querySelector('[data-notif-seen-all]');
    const showUnread = (n) => {
      badge.textContent = n > 9 ? '9+' : String(n);
      badge.hidden = n <= 0;
      seenAll.hidden = n <= 0;
      toggle.setAttribute('aria-label', `${toggle.dataset.label}: ${Math.max(0, n)} belum dilihat`);
    };
    const send = (body) => fetch(panel.dataset.seenUrl, {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
      body: JSON.stringify(body),
    }).then((response) => (response.ok ? response.json() : null)).catch(() => null);
    // The badge may count reports older than the eight in the panel; the server's answer has the real count.
    let count = Number(badge.hidden ? 0 : (badge.textContent === '9+' ? 10 : badge.textContent)) || 0;
    panel.addEventListener('click', (event) => {
      const link = event.target.closest('[data-notif-id]');
      if (!link || link.classList.contains('is-read')) return;
      link.classList.add('is-read');
      count -= 1;
      showUnread(count);
      send({ ids: [Number(link.dataset.notifId)] }).then((answer) => { if (answer) { count = answer.unread; showUnread(count); } });
    });
    seenAll.addEventListener('click', async () => {
      panel.querySelectorAll('[data-notif-id]').forEach((link) => link.classList.add('is-read'));
      count = 0;
      showUnread(0);
      const answer = await send({ all: true });
      if (answer) { count = answer.unread; showUnread(count); }
      toggle.focus();
    });
  }

=======
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
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
