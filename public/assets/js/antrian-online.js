(() => {
  'use strict';
  const form = document.getElementById('queue-form');
  if (!form) return;
  const field = (name) => form.elements.namedItem(name);
  const feedback = document.getElementById('queue-feedback');
  const review = document.getElementById('queue-review');
  const names = ['cron_antrian', 'cron_bed', 'notes', 'recorded_at'];
  const tell = (message) => { feedback.textContent = message; feedback.classList.remove('hidden'); };
  const readValues = () => Object.fromEntries(names.map((name) => [name, field(name).value]));
  const snapshot = () => JSON.stringify(readValues());
  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const updateMetadata = () => {
    const time = document.getElementById('queue-recorded-time');
    time.dateTime = field('recorded_at').value;
    time.textContent = `${dateFormatter.format(new Date(time.dateTime))} WIB`;
  };
  const syncValidation = () => {
    const failed = ['cron_antrian', 'cron_bed'].some((name) => field(name).value === 'TIDAK_TERKIRIM');
    field('notes').required = failed;
    document.getElementById('queue-notes-required').hidden = !failed;
    field('notes').setCustomValidity(failed && !field('notes').value.trim() ? 'Tuliskan kendala atau tindak lanjut untuk cron yang tidak terkirim.' : '');
  };
  updateMetadata(); syncValidation();
  let baseline = snapshot();
  form.addEventListener('input', syncValidation);
  form.addEventListener('change', () => { syncValidation(); updateMetadata(); });
  form.addEventListener('reset', (event) => {
    if (!window.confirm('Kosongkan isian form antrian?')) { event.preventDefault(); return; }
    window.setTimeout(() => {
      field('recorded_at').value = new Date().toISOString();
      field('confirmed').checked = false;
      updateMetadata(); syncValidation(); baseline = snapshot();
      tell('Form dikosongkan.');
      document.getElementById('cron_antrian-good').focus();
    }, 0);
  });
  form.addEventListener('submit', (event) => {
    event.preventDefault(); syncValidation();
    if (!form.reportValidity()) return;
    const fields = document.getElementById('queue-review-fields'); fields.replaceChildren();
    const rows = [
      ['Verifikator', form.dataset.inspector], ['Waktu Pencatatan (WIB)', dateFormatter.format(new Date(field('recorded_at').value))],
      ['Cron Antrian', field('cron_antrian').value.replaceAll('_', ' ')], ['Cron Bed MJKN', field('cron_bed').value.replaceAll('_', ' ')],
      ['Catatan', field('notes').value.trim() || 'Tidak ada catatan tambahan.'],
    ];
    rows.forEach(([label, value]) => {
      const group = document.createElement('div'); const term = document.createElement('dt');
      term.className = 'text-xs text-slate-500'; term.textContent = label;
      const description = document.createElement('dd'); description.className = 'mt-1 whitespace-pre-wrap break-words font-medium text-slate-800'; description.textContent = value;
      group.append(term, description); fields.append(group);
    });
    review.showModal();
  });
  document.querySelectorAll('[data-queue-close]').forEach((button) => button.addEventListener('click', () => review.close()));
  review.addEventListener('click', (event) => {
    if (event.target !== review) return;
    const bounds = review.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) review.close();
  });
  window.addEventListener('beforeunload', (event) => { if (snapshot() !== baseline) { event.preventDefault(); event.returnValue = ''; } });
})();
