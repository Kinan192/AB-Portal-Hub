(() => {
  'use strict';
  const form = document.getElementById('compliance-form');
  if (!form) return;
  const field = (name) => form.elements.namedItem(name);
  const feedback = document.getElementById('compliance-feedback');
  const review = document.getElementById('compliance-review');
  const names = ['username', 'status', 'reason', 'notes', 'login_date', 'login_time'];
  const storageKey = `ab-portal:kesesuaian-user:draft:v1:${form.dataset.inspector}`;
  const readValues = () => Object.fromEntries(names.map((name) => [name, field(name).value]));
  const tell = (message) => { feedback.textContent = message; feedback.classList.remove('hidden'); };
  const syncValidation = () => {
    const bad = field('status').value === 'tidak_sesuai';
    document.getElementById('compliance-reason-panel').hidden = !bad;
    field('reason').disabled = field('notes').disabled = !bad;
    field('reason').required = bad;
    const needsNotes = bad && field('reason').value === 'lainnya';
    field('notes').required = needsNotes;
    document.getElementById('compliance-notes-required').hidden = !needsNotes;
    field('notes').setCustomValidity(needsNotes && !field('notes').value.trim() ? 'Tuliskan catatan untuk alasan Lainnya.' : '');
    const username = field('username').value.trim();
    field('username').setCustomValidity(!username ? 'Masukkan username HIS yang diperiksa.' : /\s/.test(username) ? 'Username HIS tidak boleh mengandung spasi.' : '');
  };
  const nowParts = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });
  const setNow = () => {
    const now = new Date();
    const parts = Object.fromEntries(nowParts.formatToParts(now).map(({ type, value }) => [type, value]));
    field('login_date').value = `${parts.year}-${parts.month}-${parts.day}`;
    field('login_time').value = `${parts.hour}:${parts.minute}`;
    const period = document.getElementById('compliance-period');
    period.dateTime = field('login_date').value;
    period.textContent = dateFormatter.format(now);
  };
  const validDate = (value) => {
    if (!/^[1-9]\d{3}-\d{2}-\d{2}$/.test(value)) return false;
    const parsed = new Date(`${value}T00:00:00Z`);
    return Number.isFinite(parsed.getTime()) && parsed.toISOString().slice(0, 10) === value;
  };
  const validDraft = (draft) => {
    if (!draft || draft.version !== 1 || !draft.values || typeof draft.savedAt !== 'string' || !Number.isFinite(Date.parse(draft.savedAt))) return false;
    const values = draft.values;
    return names.every((name) => typeof values[name] === 'string')
      && values.username.length <= 100 && values.notes.length <= 1000
      && ['sesuai', 'tidak_sesuai'].includes(values.status)
      && Array.from(field('reason').options).some((option) => option.value === values.reason)
      && (!values.login_date || validDate(values.login_date))
      && (!values.login_time || /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(values.login_time));
  };
  try {
    const stored = localStorage.getItem(storageKey);
    if (stored) {
      const draft = JSON.parse(stored);
      if (validDraft(draft)) {
        names.forEach((name) => { field(name).value = draft.values[name]; });
        field('confirmed').checked = false;
        tell('Draft dimuat dari browser perangkat ini. Konfirmasi pemeriksaan kembali sebelum mengirim verifikasi. Data belum dikirim ke server.');
      } else tell('Draft sebelumnya tidak dapat dimuat karena format datanya tidak sesuai. Silakan isi form kembali.');
    }
  } catch { tell('Draft tidak dapat dibaca dari browser ini. Anda tetap dapat mengisi form.'); }
  syncValidation();
  let baseline = JSON.stringify(readValues());
  form.addEventListener('input', syncValidation);
  form.addEventListener('change', syncValidation);
  document.querySelector('[data-compliance-now]').addEventListener('click', () => {
    setNow();
    tell('Tanggal dan jam login diperbarui menggunakan waktu Asia/Jakarta (WIB).');
  });
  document.querySelectorAll('[data-compliance-save]').forEach((button) => button.addEventListener('click', () => {
    try {
      localStorage.setItem(storageKey, JSON.stringify({ version: 1, savedAt: new Date().toISOString(), values: readValues() }));
      baseline = JSON.stringify(readValues());
      if (review.open) review.close();
      tell('Draft tersimpan di browser perangkat ini. Data belum dikirim ke server.');
    } catch {
      if (review.open) review.close();
      tell('Draft gagal disimpan. Penyimpanan browser mungkin penuh atau diblokir. Isian form tetap tersedia di halaman ini.');
    }
    feedback.scrollIntoView({ block: 'nearest', behavior: 'instant' });
  }));
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    syncValidation();
    if (!form.reportValidity()) return;
    field('username').value = field('username').value.trim();
    const values = readValues();
    const rows = [
      ['Username HIS', values.username],
      ['Status Kesesuaian', values.status === 'sesuai' ? 'Sesuai Regulasi' : 'Tidak Sesuai Regulasi'],
      ['Waktu Login (WIB)', `${values.login_date} • ${values.login_time}`], ['Petugas Pemeriksa', form.dataset.inspector],
    ];
    if (values.status === 'tidak_sesuai') {
      rows.push(['Alasan Ketidaksesuaian', field('reason').selectedOptions[0].textContent], ['Catatan Tindak Lanjut', values.notes.trim() || 'Tidak ada catatan tambahan.']);
    }
    const details = document.getElementById('compliance-review-fields');
    details.replaceChildren();
    rows.forEach(([label, value]) => {
      const row = document.createElement('div');
      const term = document.createElement('dt');
      term.className = 'text-xs text-slate-500'; term.textContent = label;
      const description = document.createElement('dd');
      description.className = 'mt-1 whitespace-pre-wrap break-words font-medium text-slate-800';
      description.textContent = value;
      row.append(term, description); details.append(row);
    });
    review.showModal();
  });
  document.querySelectorAll('[data-compliance-close]').forEach((button) => button.addEventListener('click', () => review.close()));
  review.addEventListener('click', (event) => {
    if (event.target !== review) return;
    const bounds = review.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) review.close();
  });
  window.addEventListener('beforeunload', (event) => {
    if (JSON.stringify(readValues()) === baseline) return;
    event.preventDefault(); event.returnValue = '';
  });
})();
