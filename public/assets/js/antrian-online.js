(() => {
  'use strict';
  const form = document.getElementById('queue-form');
  if (!form) return;
  const field = (name) => form.elements.namedItem(name);
  const feedback = document.getElementById('queue-feedback');
  const review = document.getElementById('queue-review');
  const names = ['cron_antrian', 'cron_bed', 'notes', 'recorded_at'];
  const storageKey = `ab-portal:antrian-online:draft:v1:${form.dataset.inspector}`;
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
  const validDraft = (draft) => {
    if (!draft || draft.version !== 1 || !draft.values || typeof draft.savedAt !== 'string' || !Number.isFinite(Date.parse(draft.savedAt))) return false;
    const values = draft.values;
    return names.every((name) => typeof values[name] === 'string')
      && ['cron_antrian', 'cron_bed'].every((name) => ['TERKIRIM', 'TIDAK_TERKIRIM'].includes(values[name]))
      && values.notes.length <= 3000 && Number.isFinite(Date.parse(values.recorded_at));
  };
  try {
    const stored = localStorage.getItem(storageKey);
    if (stored) {
      const draft = JSON.parse(stored);
      if (validDraft(draft)) {
        names.forEach((name) => { field(name).value = draft.values[name]; });
        field('confirmed').checked = false;
        tell('Draft dimuat dari browser perangkat ini. Konfirmasi pemeriksaan kembali. Data belum dikirim ke server.');
      } else tell('Draft sebelumnya tidak dapat dimuat karena format datanya tidak sesuai. Silakan isi form kembali.');
    }
  } catch { tell('Draft tidak dapat dibaca dari browser ini. Anda tetap dapat mengisi form.'); }
  updateMetadata(); syncValidation();
  let baseline = snapshot();
  form.addEventListener('input', syncValidation);
  form.addEventListener('change', () => { syncValidation(); updateMetadata(); });
  document.querySelectorAll('[data-queue-save]').forEach((button) => button.addEventListener('click', () => {
    try {
      localStorage.setItem(storageKey, JSON.stringify({ version: 1, savedAt: new Date().toISOString(), values: readValues() }));
      baseline = snapshot();
      if (review.open) review.close();
      tell('Draft isian tersimpan di browser perangkat ini. Data belum dikirim ke server.');
    } catch {
      if (review.open) review.close();
      tell('Draft gagal disimpan. Penyimpanan browser mungkin penuh atau diblokir. Isian form tetap tersedia di halaman ini.');
    }
    feedback.scrollIntoView({ block: 'nearest', behavior: 'instant' });
  }));
  form.addEventListener('reset', (event) => {
    if (!window.confirm('Kosongkan isian dan hapus draft antrian di perangkat ini?')) { event.preventDefault(); return; }
    window.setTimeout(() => {
      field('recorded_at').value = new Date().toISOString();
      field('confirmed').checked = false;
      updateMetadata(); syncValidation(); baseline = snapshot();
      try { localStorage.removeItem(storageKey); tell('Form dikosongkan dan draft antrian di perangkat ini sudah dihapus.'); }
      catch { tell('Form dikosongkan. Draft di browser tidak dapat dihapus karena penyimpanan diblokir.'); }
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
