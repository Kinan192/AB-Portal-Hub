(() => {
  'use strict';

  /* Fill-in page of a form drawn from its question list, frontend only (app/Views/form-dinamis/fill.php):
     the dynamic forms and the six built-in module forms (database/dummy/builtin-forms.php). The question
     list and the reports live in the shared browser state (dashboard-store.js: dummy data + sessionStorage),
     so "Edit Isi Form" changes show here. The page validates the answers in the browser, keeps a submitted
     report there (shown by form-riwayat.js) and marks the form done today on the dashboard. Nothing is
     sent to the server. */
  const store = window.DashboardStore;
  if (!store?.ready) return;
  const { $, $$, esc, uid, icon, showToast, scheduleLabel, nowWib, MONTHS_LONG, optValue, optLabel, oneDecimal, bandsOf, tempStatus } = store;
  const BLOCKS = ['section', 'title', 'image'];
  const validUrl = (value) => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && url.hostname !== ''; } catch { return false; } };

  const page = $('[data-form-fill]');
  if (!page) return;
  const state = store.load();
  if (!state.responses || Array.isArray(state.responses)) state.responses = {};
  const formId = page.dataset.formId;
  // Built-in module forms carry their module's rekap page; dynamic forms use the responses page.
  const rekapUrl = page.dataset.rekapUrl ?? '';
  const builtinPage = rekapUrl !== '';
  const card = state.cards.find((item) => item.id === formId && item.kind === (builtinPage ? 'builtin' : 'form')) ?? null;
  // A built-in form stays usable from its original questions after its card was deleted from the dashboard.
  const form = state.forms?.[formId] ?? (builtinPage ? store.seed().forms?.[formId] : null);
  if (!form || (!builtinPage && !card)) { $('[data-fill-missing]').hidden = false; return; }
  const questions = (form.questions ?? []).filter((q) => !BLOCKS.includes(q.type));
  const responsesUrl = builtinPage ? rekapUrl : `${page.dataset.responsesUrl ?? ''}?id=${encodeURIComponent(formId)}`;
  const formTitle = form.title || card?.title || '';

  // Chips are a string or { label, text }.
  const otherOf = (q) => (q.other ? { label: 'Lainnya:', placeholder: '', ...(typeof q.other === 'object' ? q.other : {}) } : null);
  const chipOf = (c) => (typeof c === 'string' ? { label: c, text: c.replace(/^\+\s*/, '') } : { label: c.label ?? '', text: c.text ?? String(c.label ?? '').replace(/^\+\s*/, '') });

  const el = $('[data-fill-form]');
  const byId = Object.fromEntries(questions.map((q) => [q.id, q]));
  const name = (q) => `q-${q.id}`;
  const wibNow = () => {
    const { day, hm } = nowWib();
    return { ymd: day.toISOString().slice(0, 10), hm, day };
  };
  const autoMeta = (kind) => {
    const { ymd, hm, day } = wibNow();
    if (kind === 'month') return `${MONTHS_LONG[day.getUTCMonth()]} ${day.getUTCFullYear()}`;
    if (kind === 'today') return new Intl.DateTimeFormat('id-ID', { timeZone: 'UTC', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(day);
    const [y, m, d] = ymd.split('-');
    return `${d}/${m}/${y} ${hm} WIB`;
  };

  /* ---------- Temperature (status rules in dashboard-store.js) ---------- */
  const decimal = (value) => Number(String(value).trim().replace(',', '.'));
  const isDecimal = (value) => /^-?\d+([.,]\d+)?$/.test(String(value).trim());
  const bandLegend = (q) => {
    const b = bandsOf(q);
    const rows = [
      [`${oneDecimal(b.low)} – ${oneDecimal(b.high)} °C`, 'Normal', 'gf-range--ok'],
      [`< ${oneDecimal(b.low)} atau ${oneDecimal(b.high + 0.1)} – ${oneDecimal(b.critical - 0.1)} °C`, 'Peringatan', 'gf-range--warn'],
      [`≥ ${oneDecimal(b.critical)} °C`, 'Kritis', 'gf-range--bad'],
    ];
    return `<div class="gf-ranges">${rows.map(([range, label, tone]) => `<div class="gf-range ${tone}"><strong>${esc(range)}</strong><span>${label}</span></div>`).join('')}</div>`;
  };

  /* ---------- Markup per answer type ---------- */
  const help = (q) => (q.help ? `<p class="gf-help" id="${name(q)}-help">${esc(q.help)}</p>` : '');
  const image = (q) => (q.image && validUrl(q.image) ? `<img class="fd-qimg" src="${esc(q.image)}" alt="" referrerpolicy="no-referrer" loading="lazy">` : '');
  const describedBy = (q) => (q.help ? ` aria-describedby="${name(q)}-help"` : '');
  const req = (q) => `<span class="gf-req" aria-hidden="true" data-req-mark${q.required ? '' : ' hidden'}>*</span>`;
  const max = (q) => (q.maxlength ? ` maxlength="${esc(q.maxlength)}"` : '');
  const chips = (q) => (q.chips?.length
    ? `<div class="gf-chips"><span class="gf-chips-label">${esc(q.chipsLabel || 'Rekomendasi Cepat:')}</span>${q.chips.map(chipOf).map((chip) => `<button type="button" class="gf-chip" data-chip="${esc(chip.text)}" data-chip-for="${name(q)}" data-chip-mode="${esc(q.chipMode || (q.type === 'paragraph' ? 'append' : 'set'))}">${esc(chip.label)}</button>`).join('')}</div>`
    : '');
  const optionText = (o) => {
    const badge = typeof o === 'object' && o.badge ? `<span class="gf-pill${o.tone === 'red' ? ' gf-pill--red' : ''}">${esc(o.badge)}</span>` : '';
    const note = typeof o === 'object' && o.note ? `<span class="gf-option-note">${esc(o.note)}</span>` : '';
    return `<span class="gf-option-text"><span class="gf-option-label">${esc(optLabel(o))}${badge}</span>${note}</span>`;
  };
  const choice = (q) => {
    const type = q.type === 'checkbox' ? 'checkbox' : 'radio';
    const other = otherOf(q);
    return '<div class="gf-options">'
      + (q.options ?? []).map((o) => `<label class="gf-option"><input type="${type}" name="${name(q)}" value="${esc(optValue(o))}"${type === 'radio' && q.default !== undefined && optValue(o) === String(q.default) ? ' checked' : ''}>${optionText(o)}</label>`).join('')
      + (other ? `<label class="gf-option"><input type="${type}" name="${name(q)}" value="__other" data-other-toggle><span class="gf-option-text"><span class="gf-option-label">${esc(other.label)}</span><input class="gf-input gf-option-other" name="${name(q)}-other" placeholder="${esc(other.placeholder)}" maxlength="500" aria-label="${esc(`Keterangan lainnya untuk ${q.title}`)}" disabled></span></label>` : '')
      + '</div>';
  };
  const select = (q) => `<select class="gf-select" id="${name(q)}" name="${name(q)}"${describedBy(q)}>${q.default === undefined ? '<option value="">Pilih</option>' : ''}`
    + (q.options ?? []).map((o) => `<option value="${esc(optValue(o))}"${q.default !== undefined && optValue(o) === String(q.default) ? ' selected' : ''}>${esc(optLabel(o))}</option>`).join('') + '</select>';
  const scale = (q) => {
    const values = Array.from({ length: (+q.max || 5) - (+q.min || 0) + 1 }, (_, k) => (+q.min || 0) + k);
    return `<div class="fd-scale">${q.minLabel ? `<span class="fd-scale__end">${esc(q.minLabel)}</span>` : ''}<div class="fd-scale__options">`
      + values.map((v) => `<label class="fd-scale__option"><span>${v}</span><input type="radio" name="${name(q)}" value="${v}" aria-label="${v}${v === +q.min && q.minLabel ? ` (${esc(q.minLabel)})` : ''}${v === +q.max && q.maxLabel ? ` (${esc(q.maxLabel)})` : ''}"></label>`).join('')
      + `</div>${q.maxLabel ? `<span class="fd-scale__end">${esc(q.maxLabel)}</span>` : ''}</div>`;
  };
  const rating = (q) => `<div class="fd-rating" data-rating>${Array.from({ length: +q.max || 5 }, (_, k) => k + 1)
    .map((v) => `<label class="fd-star"><input type="radio" name="${name(q)}" value="${v}" class="sr-only"><span class="sr-only">${v} bintang</span>${icon('star', 'size-7')}</label>`).join('')}</div>`;
  const file = (q) => {
    const mb = +q.maxMb || 10;
    const formats = q.formats || `Mendukung format: PDF, JPG, PNG (maksimal ${mb} MB).`;
    return `<label class="gf-drop" for="${name(q)}"><span class="gf-drop-icon">${icon('file-up', 'size-6')}</span>`
      + `<span class="gf-drop-text">${esc(q.dropText || 'Pilih file bukti dari perangkat')}</span>`
      + `<span class="gf-drop-note">${esc(formats)} File tidak diunggah ke server pada data contoh.</span>`
      + `<span class="gf-file-btn">${icon('plus', 'size-4')}Tambah berkas</span>`
      + `<input class="sr-only" type="file" id="${name(q)}" name="${name(q)}" accept="${esc(q.accept || '.pdf,.jpg,.jpeg,.png')}"${q.multiple ? ' multiple' : ''}${q.capture ? ` capture="${esc(q.capture)}"` : ''}${describedBy(q)}></label>`
      + `<p class="gf-file-name" data-file-name hidden>${icon('check', 'size-4')}<span></span></p>`;
  };
  const grid = (q) => (q.rows ?? []).map((row, i) => `<div class="gf-matrix-row" role="radiogroup" aria-labelledby="${name(q)}-${esc(row.id)}-title">`
    + `<div class="gf-matrix-item"><div class="gf-matrix-title"><span class="gf-qnum">${i + 1}</span><span id="${name(q)}-${esc(row.id)}-title">${esc(row.label)}</span></div>${row.note ? `<p class="gf-matrix-note">${esc(row.note)}</p>` : ''}</div>`
    + `<div class="gf-pills">${(q.columns ?? []).map((col) => `<label class="gf-pill-radio"><input type="radio" name="${name(q)}-${esc(row.id)}" value="${esc(col.value)}"${col.value === q.default ? ' checked' : ''}><span>${esc(col.label)}</span></label>`).join('')}</div></div>`).join('');
  const body = (q) => {
    const now = wibNow();
    switch (q.type) {
      case 'short': {
        const extra = q.format === 'mac' ? ' autocapitalize="characters" spellcheck="false"' : q.format === 'ipv4' ? ' inputmode="decimal" spellcheck="false"' : '';
        return `${chips(q)}<input class="gf-input${q.mono ? ' gf-mono' : ''}" id="${name(q)}" name="${name(q)}" autocomplete="off" placeholder="${esc(q.placeholder || 'Jawaban Anda')}" value="${esc(q.default ?? '')}"${max(q)}${extra}${describedBy(q)}>`;
      }
      case 'paragraph': return `${chips(q)}<textarea class="gf-input" id="${name(q)}" name="${name(q)}" rows="${esc(q.rows || 3)}" placeholder="${esc(q.placeholder || 'Jawaban Anda')}"${max(q)}${describedBy(q)}>${esc(q.default ?? '')}</textarea>`
        + (q.hint || q.counter ? `<div class="gf-meta-line"><span>${esc(q.hint || '')}</span>${q.counter ? `<span data-counter-for="${name(q)}" aria-live="polite">0 Karakter</span>` : ''}</div>` : '');
      case 'radio': case 'checkbox': return choice(q);
      case 'select': return select(q);
      // Text fields with the decimal keypad: "21,5" and "21.5" are both accepted (a number field drops the comma).
      case 'number': return `<input class="gf-input fd-short" type="text" inputmode="decimal" autocomplete="off" id="${name(q)}" name="${name(q)}" placeholder="Jawaban Anda"${describedBy(q)}>`;
      case 'temperature': return `<div class="gf-temp"><input class="gf-input" type="text" inputmode="decimal" autocomplete="off" id="${name(q)}" name="${name(q)}" placeholder="mis. 21,5" data-temperature${describedBy(q)}><span class="gf-unit">${esc(q.unit || '°C')}</span></div>`
        + `<p class="gf-status" data-temp-status aria-live="polite" hidden></p>${bandLegend(q)}`;
      case 'date': return `<input class="gf-input fd-short" type="date" id="${name(q)}" name="${name(q)}"${describedBy(q)}>`;
      case 'time': return `<input class="gf-input fd-short" type="time" id="${name(q)}" name="${name(q)}"${describedBy(q)}>`;
      case 'datetime': return `<input class="gf-input fd-short" type="datetime-local" id="${name(q)}" name="${name(q)}" value="${now.ymd}T${now.hm}"${describedBy(q)}>`;
      case 'datetime2': return `<div class="gf-row"><div><label class="sr-only" for="${name(q)}-date">Tanggal</label><input class="gf-input" type="date" id="${name(q)}-date" name="${name(q)}-date" value="${now.ymd}"></div>`
        + `<div><label class="sr-only" for="${name(q)}-time">Jam (WIB)</label><input class="gf-input" type="time" step="60" id="${name(q)}-time" name="${name(q)}-time" value="${now.hm}"></div></div>`
        + `<button type="button" class="gf-btn gf-btn--text fd-now" data-now="${name(q)}">${icon('clock', 'size-4')}Set Jam Sekarang</button>`;
      case 'scale': return scale(q);
      case 'rating': return rating(q);
      case 'file': return file(q);
      case 'grid': return grid(q);
      default: return '';
    }
  };
  // Single controls get a real <label>; groups (options, scale, rating, grid, date + time) a fieldset with a legend.
  const GROUPS = ['radio', 'checkbox', 'scale', 'rating', 'grid', 'datetime2'];
  const questionCard = (q) => {
    const hide = q.showIf ? ' hidden' : '';
    if (q.type === 'confirm') {
      return `<div class="gf-card" data-question="${esc(q.id)}"${hide}><div class="gf-confirm"><input type="checkbox" id="${name(q)}" name="${name(q)}">`
        + `<label class="gf-confirm-label" for="${name(q)}">${esc(q.title)} ${req(q)}</label></div></div>`;
    }
    const badge = q.badge ? `<span class="gf-pill">${esc(q.badge)}</span>` : '';
    const title = `<span>${esc(q.title)}</span>${req(q)}`;
    if (GROUPS.includes(q.type)) {
      return `<fieldset class="gf-card" data-question="${esc(q.id)}"${describedBy(q)}${hide}><legend class="sr-only">${esc(q.title)}${q.required ? ' (wajib diisi)' : ''}</legend>`
        + `<div class="gf-q-head"><span class="gf-q-title" aria-hidden="true">${title}</span>${badge}</div>${help(q)}${image(q)}<div class="gf-body">${body(q)}</div></fieldset>`;
    }
    return `<div class="gf-card" data-question="${esc(q.id)}"${hide}><div class="gf-q-head"><label class="gf-q-title" for="${name(q)}">${title}</label>${badge}</div>${help(q)}${image(q)}<div class="gf-body">${body(q)}</div></div>`;
  };
  const header = () => {
    const meta = (form.meta ?? []).filter((m) => m?.label).map((m) => [m.label, m.auto ? autoMeta(m.auto) : (m.value ?? '')]);
    if (card?.schedule) {
      meta.push(['Jadwal', scheduleLabel(card.schedule).text]);
      if (card.schedule.deadline) meta.push(['Batas pengisian', `${card.schedule.deadline} WIB`]);
    }
    return '<section class="gf-card gf-card--header" aria-labelledby="fill-title"><p class="gf-eyebrow">RS AWAL BROS GROUP • IT BOTANIA</p>'
      + `<h1 class="gf-title" id="fill-title">${esc(formTitle)}</h1>${form.description ? `<p class="gf-desc">${esc(form.description)}</p>` : ''}`
      + (meta.length ? `<div class="gf-meta">${meta.map(([k, v]) => `<div class="min-w-0"><span class="gf-meta-label">${esc(k)}</span><div class="gf-meta-value">${esc(v)}</div></div>`).join('')}</div>` : '')
      + `<div class="gf-header-foot">${questions.some((q) => q.required || q.requiredWhen) ? '<span class="gf-required-note">* Wajib diisi</span>' : '<span></span>'}`
      + `<div class="gf-links"><a class="gf-rekap" href="${esc(responsesUrl)}" aria-label="${esc(`Lihat rekap ${formTitle}`)}">${icon('table-2', 'gf-rekap__icon')}Lihat Rekap</a></div></div></section>`;
  };
  const render = () => {
    let sectionNo = 0;
    const items = (form.questions ?? []).map((q) => {
      if (q.type === 'section') {
        sectionNo++;
        return `<div class="gf-card gf-section"><div class="gf-section-head"><span class="gf-section-num" aria-hidden="true">${sectionNo}</span>`
          + `<div class="gf-section-text"><h2 class="gf-section-title">${esc(q.title)}</h2>${q.help ? `<p class="gf-section-desc">${esc(q.help)}</p>` : ''}</div>`
          + `${q.chip ? `<span class="gf-pill">${esc(q.chip)}</span>` : ''}</div></div>`;
      }
      if (q.type === 'title') return `<div class="gf-card fd-title"><h2>${esc(q.title)}</h2>${q.help ? `<p class="gf-help">${esc(q.help)}</p>` : ''}</div>`;
      if (q.type === 'image') return q.image && validUrl(q.image) ? `<figure class="gf-card fd-figure"><img src="${esc(q.image)}" alt="${esc(q.title)}" referrerpolicy="no-referrer" loading="lazy">${q.title ? `<figcaption>${esc(q.title)}</figcaption>` : ''}</figure>` : '';
      return questionCard(q);
    }).join('');
    const empty = questions.length ? '' : `<div class="gf-card fd-empty">${icon('list-checks', 'size-6')}<b>Form ini belum memiliki pertanyaan</b><span>Tambahkan pertanyaan lewat menu ⋯ &gt; Edit Isi Form di dashboard.</span></div>`;
    el.innerHTML = header() + items + empty
      + `<div class="gf-actions"><div class="gf-actions-end"><button type="reset" class="gf-btn gf-btn--text">${esc(form.resetLabel || 'Kosongkan form')}</button>`
      + `<button type="submit" class="gf-btn gf-btn--primary"${questions.length ? '' : ' disabled'}>${icon('send', 'size-4')}<span>${esc(form.submitLabel || 'Kirim')}</span></button></div></div>`;
    el.hidden = false;
  };
  render();
  if (builtinPage) document.title = `${formTitle} | RS Awal Bros Botania`;

  /* ---------- Conditional questions ---------- */
  const cardOf = (q) => $(`[data-question="${CSS.escape(q.id)}"]`, el);
  // The raw answer of a question: an option value (radio, select), a list (checkbox) or the text.
  const picked = (q) => {
    if (q.type === 'radio') return $(`[name="${CSS.escape(name(q))}"]:checked`, el)?.value ?? '';
    if (q.type === 'checkbox') return $$(`[name="${CSS.escape(name(q))}"]:checked`, el).map((input) => input.value);
    return el.elements.namedItem(name(q))?.value ?? '';
  };
  const matches = (rule) => { const q = byId[rule.q]; if (!q) return false; const v = picked(q); return Array.isArray(v) ? v.includes(rule.value) : v === rule.value; };
  // A question whose condition points at a deleted question is always shown.
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
  const showTemperature = (input) => {
    const q = byId[input.name.slice(2)];
    const out = $('[data-temp-status]', input.closest('.gf-body'));
    if (!q || !out) return;
    const value = input.value.trim();
    out.hidden = value === '' || !isDecimal(value);
    if (out.hidden) return;
    const s = tempStatus(q, decimal(value));
    out.className = `gf-status is-${s.tone}`;
    out.innerHTML = icon(s.tone === 'ok' ? 'circle-check' : s.tone === 'warn' ? 'triangle-alert' : 'circle-alert', 'size-4');
    const text = document.createElement('span');
    text.innerHTML = `Status suhu: <strong></strong> · `;
    $('strong', text).textContent = s.text;
    text.append(s.note);
    out.append(text);
  };
  const showCounter = (input) => {
    const out = $(`[data-counter-for="${CSS.escape(input.name)}"]`, el);
    if (out) out.textContent = `${input.value.length.toLocaleString('id-ID')} Karakter`;
  };
  el.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-chip]');
    if (chip) {
      const input = document.getElementById(chip.dataset.chipFor);
      const text = chip.dataset.chip;
      const lines = input.value.split('\n').map((line) => line.trim()).filter(Boolean);
      if (chip.dataset.chipMode !== 'append') input.value = text;
      else if (!lines.includes(text)) input.value = [...lines, text].join('\n');
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.focus();
      return;
    }
    const now = event.target.closest('[data-now]');
    if (now) {
      const { ymd, hm } = wibNow();
      el.elements.namedItem(`${now.dataset.now}-date`).value = ymd;
      el.elements.namedItem(`${now.dataset.now}-time`).value = hm;
      el.elements.namedItem(`${now.dataset.now}-time`).dispatchEvent(new Event('input', { bubbles: true }));
    }
  });
  el.addEventListener('change', (event) => {
    const input = event.target;
    dirty = true;
    // "Lainnya" turns its text box on
    if (input.matches('input[type="radio"], input[type="checkbox"]')) {
      const group = input.name;
      const toggle = $(`[name="${CSS.escape(group)}"][data-other-toggle]`, el);
      if (toggle) {
        const text = $(`[name="${CSS.escape(`${group}-other`)}"]`, el);
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
    const input = event.target;
    dirty = true;
    if (input.matches('[data-temperature]')) showTemperature(input);
    if (input.tagName === 'TEXTAREA') showCounter(input);
    const box = input.closest('.gf-card.is-invalid');
    if (box) clearError(box);
  });
  el.addEventListener('reset', () => window.setTimeout(() => {
    $$('.gf-card.is-invalid', el).forEach(clearError);
    $$('[data-file-name]', el).forEach((out) => { out.hidden = true; });
    $$('.fd-star.is-on', el).forEach((star) => star.classList.remove('is-on'));
    $$('.gf-option-other', el).forEach((text) => { text.disabled = true; });
    $$('[data-temperature]', el).forEach(showTemperature);
    $$('textarea', el).forEach(showCounter);
    sync();
    dirty = false;
  }, 0));
  window.addEventListener('beforeunload', (event) => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
  sync();

  /* ---------- Validation and sending ---------- */
  const MAC = /^[0-9a-f]{12}$/i;
  const IPV4 = /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/;
  const labelOf = (q, value) => optLabel((q.options ?? []).find((o) => optValue(o) === value) ?? value);
  // { value, message } of one visible question; value is what the responses keep.
  const read = (q) => {
    const fields = el.elements;
    const required = isRequired(q);
    const other = () => fields.namedItem(`${name(q)}-other`)?.value.trim() ?? '';
    switch (q.type) {
      case 'checkbox': {
        const values = picked(q);
        if (values.includes('__other') && !other()) return { message: 'Tuliskan jawaban untuk pilihan lainnya.' };
        const value = values.map((v) => (v === '__other' ? other() : labelOf(q, v)));
        return { value, message: required && !value.length ? 'Pertanyaan ini wajib diisi.' : '' };
      }
      case 'radio': case 'scale': case 'rating': {
        const v = picked(q);
        if (v === '__other' && !other()) return { message: 'Tuliskan jawaban untuk pilihan lainnya.' };
        const value = v === '__other' ? other() : (q.type === 'radio' ? (v ? labelOf(q, v) : '') : v);
        return { value, message: required && !value ? 'Pertanyaan ini wajib diisi.' : '' };
      }
      case 'select': {
        const v = picked(q);
        return { value: v ? labelOf(q, v) : '', message: required && !v ? 'Pilih salah satu jawaban.' : '' };
      }
      case 'grid': {
        const value = {};
        const missing = (q.rows ?? []).some((row) => {
          const v = $(`[name="${CSS.escape(`${name(q)}-${row.id}`)}"]:checked`, el)?.value;
          if (v) value[row.label] = (q.columns ?? []).find((col) => col.value === v)?.label ?? v;
          return !v;
        });
        return { value, message: required && missing ? 'Pilih status untuk setiap baris.' : '' };
      }
      case 'datetime2': {
        const date = fields.namedItem(`${name(q)}-date`).value;
        const time = fields.namedItem(`${name(q)}-time`).value;
        if ((date || time) && !(date && time)) return { message: 'Isi tanggal dan jam.' };
        return { value: date && time ? `${date} ${time}` : '', message: required && !date ? 'Isi tanggal dan jam.' : '' };
      }
      case 'confirm': {
        const checked = fields.namedItem(name(q)).checked;
        return { value: checked ? 'Ya' : '', message: required && !checked ? 'Centang pernyataan untuk mengirim.' : '' };
      }
      case 'file': {
        const files = [...fields.namedItem(name(q)).files];
        const mb = +q.maxMb || 10;
        if (q.maxFiles && files.length > q.maxFiles) return { message: `Maksimal ${q.maxFiles} file.` };
        if (files.some((f) => f.size > mb * 1048576)) return { message: `Ukuran file melebihi ${mb} MB.` };
        const value = files.map((f) => f.name);
        return { value: q.multiple ? value : (value[0] ?? ''), message: required && !files.length ? 'Unggah berkas bukti.' : '' };
      }
      case 'number': case 'temperature': {
        const text = fields.namedItem(name(q)).value.trim();
        if (!text) return { value: '', message: required ? 'Pertanyaan ini wajib diisi.' : '' };
        if (!isDecimal(text)) return { message: 'Isi dengan angka, mis. 21,5.' };
        const n = decimal(text);
        if (q.type === 'temperature' && (n < 0 || n > 60)) return { message: 'Isi suhu antara 0 dan 60 °C.' };
        return { value: q.type === 'temperature' ? Math.round(n * 10) / 10 : n, message: '' };
      }
      default: {
        let value = fields.namedItem(name(q))?.value.trim() ?? '';
        if (!value) return { value, message: required ? 'Pertanyaan ini wajib diisi.' : '' };
        if (q.format === 'mac') {
          const hex = value.replace(/[\s:.-]/g, '');
          if (!MAC.test(hex)) return { message: 'Format MAC address tidak valid, mis. 00:1A:2B:3C:4D:5E.' };
          value = hex.toUpperCase().match(/../g).join(':');
        }
        if (q.format === 'ipv4' && !IPV4.test(value)) return { message: 'Alamat IP tidak valid, mis. 192.168.10.45.' };
        return { value, message: '' };
      }
    }
  };
  el.addEventListener('submit', (event) => {
    event.preventDefault();
    const answers = {};
    let first = null;
    questions.forEach((q) => {
      const box = cardOf(q);
      if (!box || !visible(q)) { if (box) clearError(box); return; }
      const { value, message } = read(q);
      if (message) { setError(q, message); first ??= q; return; }
      clearError(box);
      const empty = value === '' || (Array.isArray(value) && !value.length);
      if (!empty) answers[q.id] = value;
    });
    if (first) {
      const box = cardOf(first);
      box.scrollIntoView({ block: 'center' });
      $('input:not([disabled]):not([type="hidden"]), select, textarea', box)?.focus({ preventScroll: true });
      showToast('Periksa jawaban', 'Masih ada pertanyaan yang belum diisi dengan benar.', 'error');
      return;
    }
    (state.responses[formId] ??= []).push({ id: uid('respons'), at: new Date().toISOString(), answers });
    // The dashboard card now has a report and counts as done today.
    if (card) {
      card.done_today = true;
      card.has_data = true;
      if (!builtinPage) card.rekap = responsesUrl;
    }
    store.save(state);
    el.reset();
    dirty = false;
    const dialog = $('#fill-success');
    const text = $('[data-fill-success-text]', dialog);
    const title = document.createElement('b');
    title.textContent = formTitle;
    text.replaceChildren('Laporan ', title, ' tersimpan di riwayat (data contoh di tab browser ini).');
    $('[data-fill-success-rekap]', dialog).href = responsesUrl;
    dialog.showModal();
    $('[data-fill-success-rekap]', dialog).focus();
  });
})();
