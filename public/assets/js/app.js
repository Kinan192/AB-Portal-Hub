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

  const dialog = document.getElementById('portal-dialog');
  const title = document.getElementById('dialog-title');
  const description = document.getElementById('dialog-description');
  const accountMessages = {
    password: ['Ubah password', 'Penggantian password belum tersedia. Fitur ini akan diaktifkan setelah autentikasi akun portal terhubung.'],
    users: ['Pengelolaan pengguna', 'Pengelolaan akun belum tersedia. Halaman ini akan digunakan administrator untuk mengatur akun dan akses petugas.'],
    logout: ['Keluar dari portal', 'Fitur logout akan diaktifkan setelah integrasi autentikasi selesai.'],
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-module], [data-account]');
    if (!trigger || !dialog) return;
    if (trigger.dataset.account) {
      [title.textContent, description.textContent] = accountMessages[trigger.dataset.account];
    } else {
      title.textContent = trigger.dataset.title;
      description.textContent = trigger.dataset.type === 'external'
        ? 'Alamat aplikasi belum dikonfigurasi. Tautan layanan ini akan tersedia setelah URL tujuan ditambahkan oleh pengelola portal.'
        : 'Form internal ini belum tersedia pada tahap dashboard. Halaman pengisian dan riwayat akan dibuat setelah formulir resmi serta autentikasi portal disiapkan.';
    }
    dialog.showModal();
  });
  document.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
  });
})();
