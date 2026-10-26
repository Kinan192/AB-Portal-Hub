(() => {
  'use strict';

  /* Navbar of components/design-navbar.php: live WIB clock and mobile menu.
     Loaded by layouts/design.php and by the form/report pages on layouts/main.php. */
  const $ = (selector, root = document) => root.querySelector(selector);

  const DAYS = { Sun: 'Min', Mon: 'Sen', Tue: 'Sel', Wed: 'Rab', Thu: 'Kam', Fri: 'Jum', Sat: 'Sab' };
  const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const jakarta = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Jakarta', weekday: 'short', day: '2-digit', month: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
  });
  const clock = $('[data-clock]');
  const tick = () => {
    const parts = Object.fromEntries(jakarta.formatToParts(new Date()).map((p) => [p.type, p.value]));
    clock.textContent = `${DAYS[parts.weekday]} ${parts.day} ${MONTHS[Number(parts.month) - 1]} ${parts.hour}:${parts.minute}:${parts.second}`;
  };
  if (clock) {
    tick();
    window.setInterval(tick, 1000);
  }

  const nav = $('.rsab-nav');
  const menuButton = $('.rsab-menu-btn');
  if (nav && menuButton) {
    menuButton.addEventListener('click', () => {
      menuButton.setAttribute('aria-expanded', String(nav.classList.toggle('is-open')));
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.focus();
      }
    });
  }
})();
