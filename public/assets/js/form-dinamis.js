(() => {
  'use strict';

  /* Fill-in page of a form (app/Views/form-dinamis/fill.php), drawn from its questions with question-ui.js.
     The browser checks the answers first, then Kirim sends them and the files as FormData (data-submit-url)
     with the page's one-time token and the version shown. Server messages show under the questions.
     data-preview: the editor's "Pratinjau" tab, nothing can be sent. */
  const store = window.DashboardStore;
  const ui = window.QuestionUI;
  if (!store?.ready || !ui) return;
  const { $, $$, esc, icon, showToast, scheduleLabel, optValue, optLabel, api, apiError, ask } = store;
  const { BLOCKS, decimal, isDecimal } = ui;

  const page = $('[data-form-fill]');
  if (!page) return;
  const preview = page.hasAttribute('data-preview');
  const submitUrl = page.dataset.submitUrl ?? '';
  const entry = JSON.parse($('#fill-entry')?.textContent || '{}');
  const editing = entry.editing ?? null;
  const state = store.seed();
  const formId = page.dataset.formId;
  const card = state.cards.find((item) => item.id === formId && item.kind === 'form') ?? null;
  const form = state.forms?.[formId] ?? null;
  if (!form || !card) { $('[data-fill-missing]').hidden = false; return; }
  const questions = (form.questions ?? []).filter((q) => !BLOCKS.includes(q.type));
  const responsesUrl = `${page.dataset.responsesUrl ?? ''}?id=${encodeURIComponent(formId)}`;
  const formTitle = form.title || card.title || '';

  const el = $('[data-fill-form]');
  const byId = Object.fromEntries(questions.map((q) => [q.id, q]));
  const { name, questionCard, block, keptFiles } = ui.create({ prefix: 'q-', editing });

  const header = () => {
    const meta = [];
    if (card.schedule) {
      meta.push(['Jadwal', scheduleLabel(card.schedule).text]);
      if (card.schedule.deadline) meta.push(['Batas', `${card.schedule.deadline} WIB`]);
    }
    return '<section class="gf-card gf-card--header" aria-labelledby="fill-title">'
      + (preview ? '<p class="gf-eyebrow">Pratinjau</p>' : editing ? `<p class="gf-eyebrow">Mengubah ${esc(editing.entry_no)}</p>` : '')
      + `<h1 class="gf-title" id="fill-title">${esc(formTitle)}</h1>${form.description ? `<p class="gf-desc">${esc(form.description)}</p>` : ''}`
      + (meta.length ? `<div class="gf-meta">${meta.map(([k, v]) => `<div class="min-w-0"><span class="gf-meta-label">${esc(k)}</span><div class="gf-meta-value">${esc(v)}</div></div>`).join('')}</div>` : '')
      + `<div class="gf-header-foot">${questions.some((q) => q.required || q.requiredWhen) ? '<span class="gf-required-note">* Wajib diisi</span>' : '<span></span>'}`
      + (preview ? '' : `<div class="gf-links"><a class="gf-rekap" href="${esc(responsesUrl)}" target="_blank" rel="noopener">${icon('table-2', 'gf-rekap__icon')}Lihat Rekap</a></div>`)
      + '</div></section>';
  };
  const render = () => {
    let sectionNo = 0;
    const items = (form.questions ?? []).map((q) => {
      if (q.type === 'section') sectionNo++;
      return BLOCKS.includes(q.type) ? block(q, sectionNo) : questionCard(q);
    }).join('');
    const empty = questions.length ? '' : `<div class="gf-card fd-empty">${icon('list-checks', 'size-6')}<b>Belum ada pertanyaan</b></div>`;
    const secondary = editing
      ? `<a class="gf-btn gf-btn--text" href="${esc(editing.cancel_url)}" data-leave>Batal</a>`
      : '<button type="reset" class="gf-btn gf-btn--text">Kosongkan form</button>';
    el.innerHTML = header() + items + empty
      + `<div class="gf-actions"><div class="gf-actions-end">${secondary}`
      + `<button type="submit" class="gf-btn gf-btn--primary"${questions.length && !preview ? '' : ' disabled'}>${icon(editing ? 'save' : 'send', 'size-4')}<span>${editing ? 'Simpan Perubahan' : 'Kirim'}</span></button></div></div>`;
    el.hidden = false;
  };
  render();

  /* ---------- Conditional questions ---------- */
  const cardOf = (q) => $(`[data-question="${CSS.escape(q.id)}"]`, el);
  const picked = (q) => {
    if (['radio', 'scale', 'rating'].includes(q.type)) return $(`[name="${CSS.escape(name(q))}"]:checked`, el)?.value ?? '';
    if (q.type === 'checkbox') return $$(`[name="${CSS.escape(name(q))}"]:checked`, el).map((input) => input.value);
    return el.elements.namedItem(name(q))?.value ?? '';
  };
  const matches = (rule) => {
    const q = byId[rule.q];
    if (!q) return false;
    const v = picked(q);
    const wanted = rule.values ?? [rule.value];
    return Array.isArray(v) ? v.some((x) => wanted.includes(x)) : wanted.includes(v);
  };
  const visible = (q, depth = 0) => !q.showIf || !byId[q.showIf.q] || depth > 10 || (visible(byId[q.showIf.q], depth + 1) && matches(q.showIf));
  const isRequired = (q) => Boolean(q.required) || (q.requiredWhen ?? []).some((rule) => byId[rule.q] && visible(byId[rule.q]) && matches(rule));
  const sync = () => questions.forEach((q) => {
    const box = cardOf(q);
    if (!box) return;
    box.hidden = !visible(q);
    const mark = $('[data-req-mark]', box);
    if (mark) mark.hidden = !isRequired(q);
  });

  /* ---------- Interaction ---------- */
  let dirty = false;
  let sending = false;
  const clearError = (box) => {
    box.classList.remove('is-invalid');
    $('.gf-error', box)?.remove();
    $$('[aria-invalid]', box).forEach((input) => input.removeAttribute('aria-invalid'));
  };
  const setError = (q, message) => {
    const box = cardOf(q);
    clearError(box);
    box.classList.add('is-invalid');
    const p = document.createElement('p');
    p.className = 'gf-error';
    p.id = `${name(q)}-error`;
    p.innerHTML = icon('circle-alert', 'size-4');
    p.append(message);
    box.append(p);
    $$('input, select, textarea', box).forEach((input) => {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), p.id].filter(Boolean).join(' '));
    });
  };
  el.addEventListener('change', (event) => {
    const input = event.target;
    dirty = true;
    if (input.matches('input[type="radio"], input[type="checkbox"]')) {
      const toggle = $(`[name="${CSS.escape(input.name)}"][data-other-toggle]`, el);
      if (toggle) {
        const text = $(`[name="${CSS.escape(`${input.name}-other`)}"]`, el);
        text.disabled = !toggle.checked;
        if (toggle.checked && input === toggle) text.focus();
      }
    }
    const stars = input.closest('[data-rating]');
    if (stars) $$('.fd-star', stars).forEach((star, i) => star.classList.toggle('is-on', i < +input.value));
    if (input.type === 'file') {
      const out = $('[data-file-name]', input.closest('.gf-body'));
      const files = [...input.files];
      out.hidden = files.length === 0;
      if (files.length) $('span', out).textContent = files.map((f) => `${f.name} (${(f.size / 1048576).toFixed(1)} MB)`).join(', ');
    }
    sync();
    const box = input.closest('.gf-card.is-invalid');
    if (box) clearError(box);
  });
  el.addEventListener('input', (event) => {
    dirty = true;
    const box = event.target.closest('.gf-card.is-invalid');
    if (box) clearError(box);
  });
  // "Kosongkan form" and leaving with unsent input are confirmed in a modal.
  let resetOk = false;
  el.addEventListener('reset', (event) => {
    if (!resetOk && dirty) {
      event.preventDefault();
      ask({ title: 'Kosongkan form?', text: 'Semua jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan', danger: true }).then((yes) => {
        if (!yes) return;
        resetOk = true;
        el.reset();
        resetOk = false;
      });
      return;
    }
    window.setTimeout(() => {
      $$('.gf-card.is-invalid', el).forEach(clearError);
      $$('[data-file-name]', el).forEach((out) => { out.hidden = true; });
      $$('.fd-star.is-on', el).forEach((star) => star.classList.remove('is-on'));
      $$('.gf-option-other', el).forEach((text) => { text.disabled = true; });
      sync();
      dirty = false;
    }, 0);
  });
  document.addEventListener('click', async (event) => {
    const link = event.target.closest('a[href]');
    if (!link || !dirty || sending || link.target === '_blank' || link.closest('dialog') || !(link.closest('main, nav, header, .breadcrumb-shell') || link.hasAttribute('data-leave'))) return;
    event.preventDefault();
    if (await ask({ title: 'Tinggalkan halaman?', text: 'Jawaban yang belum dikirim akan hilang.', ok: 'Tinggalkan', danger: true })) {
      dirty = false;
      window.location.href = link.href;
    }
  });
  window.addEventListener('beforeunload', (event) => { if (dirty && !preview) { event.preventDefault(); event.returnValue = ''; } });
  sync();

  /* ---------- Validation ---------- */
  const labelOf = (q, value) => optLabel((q.options ?? []).find((o) => optValue(o) === value) ?? value);
  const read = (q) => {
    const fields = el.elements;
    const required = isRequired(q);
    const other = () => fields.namedItem(`${name(q)}-other`)?.value.trim() ?? '';
    switch (q.type) {
      case 'checkbox': {
        const values = picked(q);
        if (values.includes('__other') && !other()) return { message: 'Tuliskan jawaban untuk pilihan lainnya.' };
        return { message: required && !values.length ? 'Pertanyaan ini wajib diisi.' : '' };
      }
      case 'radio': case 'scale': case 'rating': {
        const v = picked(q);
        if (v === '__other' && !other()) return { message: 'Tuliskan jawaban untuk pilihan lainnya.' };
        return { message: required && !v ? 'Pertanyaan ini wajib diisi.' : '' };
      }
      case 'select': return { message: required && !picked(q) ? 'Pilih salah satu jawaban.' : '' };
      case 'confirm': return { message: required && !fields.namedItem(name(q)).checked ? 'Centang pernyataan untuk mengirim.' : '' };
      case 'file': {
        const files = [...fields.namedItem(name(q)).files];
        const mb = +q.maxMb || 10;
        const saved = keptFiles(q).length - $$(`[name="${CSS.escape(`${name(q)}-hapus`)}"]:checked`, el).length;
        const kept = q.multiple ? saved : (files.length ? 0 : saved);
        if (q.maxFiles && kept + files.length > q.maxFiles) return { message: `Maksimal ${q.maxFiles} file.` };
        if (files.some((f) => f.size > mb * 1048576)) return { message: `Ukuran file melebihi ${mb} MB.` };
        return { message: required && !files.length && kept <= 0 ? 'Unggah berkas.' : '' };
      }
      case 'number': {
        const text = fields.namedItem(name(q)).value.trim();
        if (!text) return { message: required ? 'Pertanyaan ini wajib diisi.' : '' };
        if (q.integer && !/^\d+$/.test(text)) return { message: 'Isi dengan angka bulat.' };
        if (!isDecimal(text)) return { message: 'Isi dengan angka, mis. 21,5.' };
        const n = decimal(text);
        if ((q.min !== undefined && n < +q.min) || (q.max !== undefined && n > +q.max)) return { message: `Isi angka antara ${q.min ?? '…'} dan ${q.max ?? '…'}.` };
        return { message: '' };
      }
      default: {
        const value = fields.namedItem(name(q))?.value.trim() ?? '';
        return { message: required && !value ? 'Pertanyaan ini wajib diisi.' : '' };
      }
    }
  };
  el.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (sending || preview) return;
    let first = null;
    questions.forEach((q) => {
      const box = cardOf(q);
      if (!box || !visible(q)) { if (box) clearError(box); return; }
      const { message } = read(q);
      if (message) { setError(q, message); first ??= q; return; }
      clearError(box);
    });
    if (first) {
      cardOf(first).scrollIntoView({ block: 'center' });
      $('input:not([disabled]), select, textarea', cardOf(first))?.focus({ preventScroll: true });
      showToast('Periksa jawaban', 'Masih ada pertanyaan yang belum diisi dengan benar.', 'error');
      return;
    }
    await send();
  });

  /* ---------- Prefill and sending ---------- */
  const optionValues = (q) => (q.options ?? []).map(optValue);
  const otherText = (q) => el.elements.namedItem(`${name(q)}-other`)?.value.trim() ?? '';
  const rawOf = (q) => {
    const input = el.elements.namedItem(name(q));
    if (['radio', 'scale', 'rating'].includes(q.type)) { const v = picked(q); return v === '__other' ? otherText(q) : v; }
    if (q.type === 'checkbox') return picked(q).map((v) => (v === '__other' ? otherText(q) : v)).filter((v) => v !== '');
    if (q.type === 'confirm') return input?.checked ? '1' : '';
    return input && 'value' in input ? input.value : '';
  };
  const setValue = (q, value) => {
    if (['radio', 'checkbox', 'scale', 'rating'].includes(q.type)) {
      const values = (Array.isArray(value) ? value : [value]).map((v) => (v === null || v === undefined ? '' : String(v))).filter((v) => v !== '');
      const known = q.options ? optionValues(q) : values;
      const other = q.other ? values.find((v) => !known.includes(v)) : undefined;
      $$(`[name="${CSS.escape(name(q))}"]`, el).forEach((input) => { input.checked = input.value === '__other' ? other !== undefined : values.includes(input.value); });
      const text = el.elements.namedItem(`${name(q)}-other`);
      if (text) { text.value = other ?? ''; text.disabled = other === undefined; }
      const stars = cardOf(q)?.querySelector('[data-rating]');
      if (stars) $$('.fd-star', stars).forEach((star, i) => star.classList.toggle('is-on', i < +(values[0] ?? 0)));
    } else if (q.type === 'confirm') {
      el.elements.namedItem(name(q)).checked = value === '1';
    } else if (q.type !== 'file') {
      const input = el.elements.namedItem(name(q));
      if (input && 'value' in input) input.value = value ?? '';
    }
  };
  Object.entries(entry.prefill ?? {}).forEach(([id, value]) => { if (byId[id]) setValue(byId[id], value); });
  sync();

  const showServerErrors = (errors) => {
    let first = null;
    Object.entries(errors ?? {}).forEach(([id, message]) => {
      const q = byId[id];
      if (!q || !cardOf(q)) return;
      setError(q, message);
      first ??= q;
    });
    return first;
  };
  const send = async () => {
    const data = new FormData();
    data.append('form_token', page.dataset.formToken ?? '');
    questions.forEach((q) => {
      if (!visible(q)) return;
      if (q.type === 'file') {
        const files = [...el.elements.namedItem(name(q)).files];
        if (q.multiple) files.forEach((f) => data.append(`${q.id}[]`, f));
        else if (files.length) data.append(q.id, files[0]);
        $$(`[name="${CSS.escape(`${name(q)}-hapus`)}"]:checked`, el).forEach((box) => data.append(`${q.id}_hapus[]`, box.value));
        return;
      }
      const value = rawOf(q);
      if ((q.type === 'radio' || q.type === 'checkbox') && q.other) {
        // The "Lainnya" text goes as <id>_lainnya next to the choice "__other".
        const known = optionValues(q);
        const values = (Array.isArray(value) ? value : [value]).map((v) => (v === '' || known.includes(v) ? v : (data.append(`${q.id}_lainnya`, v), '__other')));
        values.forEach((v) => data.append(q.type === 'checkbox' ? `${q.id}[]` : q.id, v));
        return;
      }
      (Array.isArray(value) ? value : [value]).forEach((v) => data.append(Array.isArray(value) ? `${q.id}[]` : q.id, v));
    });
    if (page.dataset.formVersion) data.append('versi', page.dataset.formVersion);
    const button = $('button[type="submit"]', el);
    sending = true;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    try {
      const answer = await api(submitUrl, data);
      dirty = false;
      const dialog = $('#fill-success');
      const text = $('[data-fill-success-text]', dialog);
      const number = document.createElement('b');
      number.textContent = answer.entry_no ?? '';
      if (editing) {
        $('#fill-success-title').textContent = answer.message === 'Tidak ada perubahan' ? 'Tidak Ada Perubahan' : 'Perubahan Tersimpan';
        text.replaceChildren(number, answer.message === 'Tidak ada perubahan' ? ' tidak berubah.' : ' sudah diperbarui.');
      } else {
        text.replaceChildren('Nomor ', number, '.');
        // The page's token is used: the next report starts from a fresh page.
        dialog.addEventListener('close', () => window.location.reload(), { once: true });
      }
      $('[data-fill-success-rekap]', dialog).href = answer.rekap_url ?? responsesUrl;
      dialog.showModal();
      $('[data-fill-success-rekap]', dialog).focus();
    } catch (error) {
      if (error.status === 409 && error.data?.entry_no) {
        showToast('Sudah terkirim', `Form ini sudah tersimpan sebagai ${error.data.entry_no}.`, 'info');
        return;
      }
      const first = error.status === 422 ? showServerErrors(error.errors) : null;
      if (first) cardOf(first).scrollIntoView({ block: 'center' });
      apiError(error);
    } finally {
      sending = false;
      button.disabled = false;
      button.removeAttribute('aria-busy');
    }
  };
})();
