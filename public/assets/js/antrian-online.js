(() => {
  'use strict';
  const form = document.getElementById('queue-form');
  if (!form) return;
  const field = (name) => form.elements.namedItem(name);
  const feedback = document.getElementById('queue-feedback');
  const review = document.getElementById('queue-review');
  const evidence = document.getElementById('queue-evidence');
  const dropzone = document.getElementById('queue-dropzone');
  const fileStatus = document.getElementById('queue-file-status');
  const removeFile = document.querySelector('[data-queue-remove-file]');
  const ticketInputs = Array.from(form.querySelectorAll('[data-queue-ticket]'));
  const names = ['shift', 'cron_antrian', 'cron_bed', ...ticketInputs.map((input) => input.name), 'notes', 'recorded_at'];
  const storageKey = `ab-portal:antrian-online:draft:v1:${form.dataset.inspector}`;
  let selectedFile = null;
  const tell = (message) => { feedback.textContent = message; feedback.classList.remove('hidden'); };
  const readValues = () => Object.fromEntries(names.map((name) => [name, field(name).value]));
  const snapshot = () => JSON.stringify({ values: readValues(), attachment: selectedFile ? [selectedFile.name, selectedFile.size, selectedFile.lastModified] : null });
  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const updateMetadata = () => {
    const time = document.getElementById('queue-recorded-time');
    time.dateTime = field('recorded_at').value;
    time.textContent = `${dateFormatter.format(new Date(time.dateTime))} WIB`;
    document.getElementById('queue-period').textContent = `Inspeksi Harian (${field('shift').selectedOptions[0].dataset.short})`;
  };
  const validCount = (value) => value !== '' && Number.isSafeInteger(Number(value)) && Number(value) >= 0;
  const syncValidation = () => {
    const failed = ['cron_antrian', 'cron_bed'].some((name) => field(name).value === 'TIDAK_TERKIRIM');
    field('notes').required = failed;
    document.getElementById('queue-notes-required').hidden = !failed;
    field('notes').setCustomValidity(failed && !field('notes').value.trim() ? 'Tuliskan kendala atau tindak lanjut untuk cron yang tidak terkirim.' : '');
    ticketInputs.forEach((input) => input.setCustomValidity(input.value !== '' && !validCount(input.value) ? 'Jumlah tiket harus berupa bilangan bulat nol atau lebih.' : ''));
  };
  const clearFile = () => {
    selectedFile = null; evidence.value = ''; removeFile.hidden = true;
    fileStatus.textContent = 'Belum ada berkas dipilih.';
  };
  const selectFile = (files) => {
    selectedFile = null; removeFile.hidden = true;
    const reject = (message) => { evidence.value = ''; fileStatus.textContent = message; };
    if (!files.length) { clearFile(); return; }
    if (files.length !== 1) { reject('Pilih satu berkas bukti saja.'); return; }
    const file = files[0];
    const extension = file.name.split('.').pop().toLowerCase();
    const allowedTypes = { png: ['image/png'], jpg: ['image/jpeg'], jpeg: ['image/jpeg'], pdf: ['application/pdf'], txt: ['text/plain', 'application/octet-stream'], log: ['text/plain', 'application/octet-stream'] };
    if (!allowedTypes[extension] || (file.type && !allowedTypes[extension].includes(file.type))) {
      reject('Format berkas tidak didukung. Gunakan PNG, JPG, PDF, TXT, atau LOG.'); return;
    }
    if (!file.size || file.size > 5 * 1024 * 1024) { reject('Berkas harus berisi data dan berukuran maksimal 5 MB.'); return; }
    selectedFile = file; removeFile.hidden = false;
    fileStatus.textContent = `Dipilih: ${file.name} (${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(file.size / 1024)} KB). Belum diunggah ke server.`;
  };
  evidence.addEventListener('change', () => selectFile(Array.from(evidence.files)));
  removeFile.addEventListener('click', clearFile);
  ['dragenter', 'dragover'].forEach((type) => dropzone.addEventListener(type, (event) => { event.preventDefault(); dropzone.classList.add('queue-drop-active'); }));
  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('queue-drop-active'));
  dropzone.addEventListener('drop', (event) => {
    event.preventDefault(); dropzone.classList.remove('queue-drop-active');
    selectFile(Array.from(event.dataTransfer.files));
    if (selectedFile) evidence.files = event.dataTransfer.files;
  });

  const validDraft = (draft) => {
    if (!draft || draft.version !== 1 || !draft.values || typeof draft.savedAt !== 'string' || !Number.isFinite(Date.parse(draft.savedAt))) return false;
    const values = draft.values;
    return names.every((name) => typeof values[name] === 'string')
      && ['pagi', 'siang', 'malam'].includes(values.shift)
      && ['cron_antrian', 'cron_bed'].every((name) => ['TERKIRIM', 'TIDAK_TERKIRIM'].includes(values[name]))
      && ticketInputs.every((input) => values[input.name] === '' || validCount(values[input.name]))
      && values.notes.length <= 3000 && Number.isFinite(Date.parse(values.recorded_at))
      && typeof draft.attachmentName === 'string' && draft.attachmentName.length <= 1000;
  };
  try {
    const stored = localStorage.getItem(storageKey);
    if (stored) {
      const draft = JSON.parse(stored);
      if (validDraft(draft)) {
        names.forEach((name) => { field(name).value = draft.values[name]; });
        field('confirmed').checked = false;
        tell(`Draft dimuat dari browser perangkat ini. Konfirmasi pemeriksaan kembali.${draft.attachmentName ? ` Bukti ${draft.attachmentName} perlu dipilih ulang karena berkas tidak disimpan dalam draft.` : ''} Data belum dikirim ke server.`);
      } else tell('Draft sebelumnya tidak dapat dimuat karena format datanya tidak sesuai. Silakan isi form kembali.');
    }
  } catch { tell('Draft tidak dapat dibaca dari browser ini. Anda tetap dapat mengisi form.'); }
  updateMetadata(); syncValidation();
  let baseline = snapshot();
  form.addEventListener('input', syncValidation);
  form.addEventListener('change', () => { syncValidation(); updateMetadata(); });

  document.querySelectorAll('[data-queue-save]').forEach((button) => button.addEventListener('click', () => {
    try {
      localStorage.setItem(storageKey, JSON.stringify({ version: 1, savedAt: new Date().toISOString(), values: readValues(), attachmentName: selectedFile?.name || '' }));
      baseline = snapshot();
      if (review.open) review.close();
      tell(`Draft isian tersimpan di browser perangkat ini. Data belum dikirim ke server.${selectedFile ? ' Berkas bukti tidak disimpan dalam draft dan perlu dipilih ulang setelah halaman dibuka kembali.' : ''}`);
    } catch {
      if (review.open) review.close();
      tell('Draft gagal disimpan. Penyimpanan browser mungkin penuh atau diblokir. Isian form tetap tersedia di halaman ini.');
    }
    feedback.scrollIntoView({ block: 'nearest', behavior: 'instant' });
  }));
  form.addEventListener('reset', (event) => {
    if (!window.confirm('Kosongkan isian, bukti terpilih, dan hapus draft antrian di perangkat ini?')) { event.preventDefault(); return; }
    window.setTimeout(() => {
      const now = new Date();
      const hour = Number(new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', hour: '2-digit', hourCycle: 'h23' }).format(now));
      field('shift').value = hour >= 7 && hour < 14 ? 'pagi' : hour >= 14 && hour < 21 ? 'siang' : 'malam';
      field('recorded_at').value = now.toISOString();
      ticketInputs.forEach((input) => { input.value = '0'; });
      clearFile(); updateMetadata(); syncValidation(); baseline = snapshot();
      try { localStorage.removeItem(storageKey); tell('Form dikosongkan dan draft antrian di perangkat ini sudah dihapus.'); }
      catch { tell('Form dikosongkan. Draft di browser tidak dapat dihapus karena penyimpanan diblokir.'); }
      field('shift').focus();
    }, 0);
  });
  form.addEventListener('submit', (event) => {
    event.preventDefault(); syncValidation();
    if (!form.reportValidity()) return;
    const fields = document.getElementById('queue-review-fields'); fields.replaceChildren();
    const rows = [
      ['Verifikator', form.dataset.inspector], ['Waktu Pencatatan (WIB)', dateFormatter.format(new Date(field('recorded_at').value))],
      ['Shift', field('shift').selectedOptions[0].textContent], ['Cron Antrian', field('cron_antrian').value.replaceAll('_', ' ')], ['Cron Bed MJKN', field('cron_bed').value.replaceAll('_', ' ')],
      ...ticketInputs.map((input) => [input.dataset.label, String(Number(input.value))]),
      ['Bukti Monitoring', selectedFile ? selectedFile.name : 'Tidak ada berkas dipilih.'], ['Catatan', field('notes').value.trim() || 'Tidak ada catatan tambahan.'],
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
