(() => {
  'use strict';

  const form = document.getElementById('hardware-monitoring-form');
  if (!form) return;

  const feedback = document.getElementById('inspection-feedback');
  const review = document.getElementById('inspection-review');
  const notes = form.elements.namedItem('catatan_hardware');
  const fields = ['rak_server', 'kondisi_cpu', 'keterangan_cpu', 'kondisi_monitor', 'keterangan_monitor', 'catatan_hardware', 'recorded_at'];
  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const showFeedback = (message) => {
    feedback.textContent = message;
    feedback.classList.remove('hidden');
  };
  const readValues = () => Object.fromEntries(fields.map((name) => [name, form.elements.namedItem(name).value]));
  const updateTimestamp = () => {
    const time = document.getElementById('inspection-timestamp');
    const value = form.elements.namedItem('recorded_at').value;
    time.dateTime = value;
    time.textContent = `${dateFormatter.format(new Date(value))} WIB`;
  };
  const updateOtherFields = () => {
    form.querySelectorAll('[data-other-for]').forEach((input) => {
      const selected = form.elements.namedItem(input.dataset.otherFor).value === 'Other';
      input.required = selected;
      input.setCustomValidity(selected && !input.value.trim() ? 'Tuliskan keterangan kondisi lainnya.' : '');
    });
  };

  updateTimestamp();
  updateOtherFields();
  let baseline = JSON.stringify(readValues());
  const hasChanges = () => JSON.stringify(readValues()) !== baseline;

  form.addEventListener('input', updateOtherFields);
  form.addEventListener('change', updateOtherFields);
  form.querySelectorAll('[data-other-for]').forEach((input) => {
    input.addEventListener('focus', () => {
      form.querySelector(`input[name="${input.dataset.otherFor}"][value="Other"]`).checked = true;
      updateOtherFields();
    });
  });

  document.querySelectorAll('[data-quick-note]').forEach((button) => {
    button.addEventListener('click', () => {
      const addition = button.dataset.quickNote;
      const existing = notes.value.trim();
      if (!existing.split(';\n').includes(addition)) {
        const next = existing ? `${existing};\n${addition}` : addition;
        if (next.length > notes.maxLength) {
          showFeedback('Catatan sudah mendekati batas 3.000 karakter. Ringkas catatan sebelum menambah rekomendasi.');
          return;
        }
        notes.value = next;
      }
      notes.focus();
    });
  });

  form.addEventListener('reset', (event) => {
    if (!window.confirm('Kosongkan catatan dan mulai pemeriksaan baru?')) {
      event.preventDefault();
      return;
    }
    // Run after the browser restores the original radio/input defaults.
    window.setTimeout(() => {
      form.elements.namedItem('recorded_at').value = new Date().toISOString();
      updateOtherFields();
      updateTimestamp();
      baseline = JSON.stringify(readValues());
      showFeedback('Form baru siap diisi.');
      form.querySelector('[name="rak_server"]:checked').focus();
    }, 0);
  });
  document.querySelector('[data-new-inspection]').addEventListener('click', () => form.reset());

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    updateOtherFields();
    if (!form.reportValidity()) return;
    const values = readValues();
    const details = document.getElementById('inspection-review-details');
    details.replaceChildren();
    const rows = [
      ['Petugas', form.dataset.inspector], ['Rak Server', values.rak_server],
      ['Kondisi CPU', values.kondisi_cpu === 'Other' ? values.keterangan_cpu.trim() : values.kondisi_cpu],
      ['Kondisi Monitor', values.kondisi_monitor === 'Other' ? values.keterangan_monitor.trim() : values.kondisi_monitor],
      ['Catatan Hardware', values.catatan_hardware.trim() || 'Tidak ada catatan tambahan.'],
    ];
    rows.forEach(([label, value]) => {
      const row = document.createElement('div');
      const term = document.createElement('dt');
      const description = document.createElement('dd');
      term.className = 'text-xs text-slate-500';
      description.className = 'mt-1 whitespace-pre-wrap break-words font-medium text-slate-800';
      term.textContent = label;
      description.textContent = value;
      row.append(term, description);
      details.append(row);
    });
    review.showModal();
  });

  document.querySelectorAll('[data-close-inspection-review]').forEach((button) => button.addEventListener('click', () => review.close()));
  review.addEventListener('click', (event) => {
    if (event.target !== review) return;
    const bounds = review.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) review.close();
  });
  window.addEventListener('beforeunload', (event) => {
    if (!hasChanges()) return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
