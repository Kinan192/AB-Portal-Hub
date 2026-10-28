(() => {
  'use strict';

  /* Question markup of the fill-in page (form-dinamis.js), also used by the editor's "Pratinjau" tab.
     QuestionUI.create({ prefix, editing }): prefix starts the field names ("q-"); editing is the report being
     changed (its saved files), or null. No answer is preselected. */
  const store = window.DashboardStore;
  if (!store) return;
  const { esc, icon, optValue, optLabel } = store;
  const BLOCKS = ['section', 'title', 'image'];
  const validUrl = (value) => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && url.hostname !== ''; } catch { return false; } };
  const otherOf = (q) => (q.other ? { label: 'Lainnya:', ...(typeof q.other === 'object' ? q.other : {}) } : null);
  const decimal = (value) => Number(String(value).trim().replace(',', '.'));
  const isDecimal = (value) => /^-?\d+([.,]\d+)?$/.test(String(value).trim());

  const create = (ctx = {}) => {
    const editing = ctx.editing ?? null;
    const name = (q) => `${ctx.prefix ?? 'q-'}${q.id}`;
    const help = (q) => (q.help ? `<p class="gf-help" id="${name(q)}-help">${esc(q.help)}</p>` : '');
    const image = (q) => (q.image && validUrl(q.image) ? `<img class="fd-qimg" src="${esc(q.image)}" alt="" referrerpolicy="no-referrer" loading="lazy">` : '');
    const describedBy = (q) => (q.help ? ` aria-describedby="${name(q)}-help"` : '');
    const req = (q) => `<span class="gf-req" aria-hidden="true" data-req-mark${q.required ? '' : ' hidden'}>*</span>`;
    const max = (q) => (q.maxlength ? ` maxlength="${esc(q.maxlength)}"` : '');
    const choice = (q) => {
      const type = q.type === 'checkbox' ? 'checkbox' : 'radio';
      const other = otherOf(q);
      return '<div class="gf-options">'
        + (q.options ?? []).map((o) => `<label class="gf-option"><input type="${type}" name="${name(q)}" value="${esc(optValue(o))}"><span class="gf-option-text"><span class="gf-option-label">${esc(optLabel(o))}</span></span></label>`).join('')
        + (other ? `<label class="gf-option"><input type="${type}" name="${name(q)}" value="__other" data-other-toggle><span class="gf-option-text"><span class="gf-option-label">${esc(other.label)}</span>`
          + `<input class="gf-input gf-option-other" name="${name(q)}-other" aria-label="Jawaban lainnya" maxlength="200" disabled></span></label>` : '')
        + '</div>';
    };
    const select = (q) => `<select class="gf-select" id="${name(q)}" name="${name(q)}"${describedBy(q)}><option value="">Pilih</option>`
      + (q.options ?? []).map((o) => `<option value="${esc(optValue(o))}">${esc(optLabel(o))}</option>`).join('') + '</select>';
    const scale = (q) => {
      const values = Array.from({ length: (+q.max || 5) - (+q.min || 0) + 1 }, (_, k) => (+q.min || 0) + k);
      return `<div class="fd-scale">${q.minLabel ? `<span class="fd-scale__end">${esc(q.minLabel)}</span>` : ''}<div class="fd-scale__options">`
        + values.map((v) => `<label class="fd-scale__option"><span>${v}</span><input type="radio" name="${name(q)}" value="${v}" aria-label="${v}"></label>`).join('')
        + `</div>${q.maxLabel ? `<span class="fd-scale__end">${esc(q.maxLabel)}</span>` : ''}</div>`;
    };
    const rating = (q) => `<div class="fd-rating" data-rating>${Array.from({ length: +q.max || 5 }, (_, k) => k + 1)
      .map((v) => `<label class="fd-star"><input type="radio" name="${name(q)}" value="${v}" class="sr-only"><span class="sr-only">${v} bintang</span>${icon('star', 'size-7')}</label>`).join('')}</div>`;
    // Files already saved with the report being changed.
    const keptFiles = (q) => editing?.fileMap?.[q.id] ?? [];
    const file = (q) => {
      const mb = +q.maxMb || 10;
      const types = [...new Set((q.accept || '.pdf,.jpg,.png').split(',').map((e) => e.trim().replace(/^\./, '').toUpperCase().replace('JPEG', 'JPG')).filter(Boolean))];
      const kept = keptFiles(q);
      const current = kept.length
        ? `<ul class="gf-file-list" aria-label="Berkas saat ini">${kept.map((f) => `<li class="gf-file-name">${icon('paperclip', 'size-4')}`
          + `<a href="${esc(f.url)}" target="_blank" rel="noopener">${esc(f.name)}</a>`
          + `<label class="gf-file-remove"><input type="checkbox" name="${name(q)}-hapus" value="${esc(f.id)}">Hapus</label></li>`).join('')}</ul>`
        : '';
      return `<label class="gf-drop" for="${name(q)}"><span class="gf-drop-icon">${icon('file-up', 'size-6')}</span>`
        + `<span class="gf-drop-note">${esc(types.join(', '))} · maks. ${mb} MB</span>`
        + `<span class="gf-file-btn">${icon('plus', 'size-4')}Tambah berkas</span>`
        + `<input class="sr-only" type="file" id="${name(q)}" name="${name(q)}" accept="${esc(q.accept || '.pdf,.jpg,.jpeg,.png')}"${q.multiple ? ' multiple' : ''}${describedBy(q)}></label>`
        + `<p class="gf-file-name" data-file-name hidden>${icon('check', 'size-4')}<span></span></p>${current}`;
    };
    const body = (q) => {
      switch (q.type) {
        case 'short': return `<input class="gf-input${q.unit ? ' fd-short' : ''}" id="${name(q)}" name="${name(q)}" autocomplete="off" placeholder="Jawaban Anda"${max(q)}${describedBy(q)}>`
          + (q.unit ? `<span class="gf-unit">${esc(q.unit)}</span>` : '');
        case 'paragraph': return `<textarea class="gf-input" id="${name(q)}" name="${name(q)}" rows="${esc(q.rows || 3)}" placeholder="Jawaban Anda"${max(q)}${describedBy(q)}></textarea>`;
        case 'radio': case 'checkbox': return choice(q);
        case 'select': return select(q);
        case 'number': return `<input class="gf-input fd-short" type="text" inputmode="${q.integer ? 'numeric' : 'decimal'}" autocomplete="off" id="${name(q)}" name="${name(q)}" placeholder="Jawaban Anda"${describedBy(q)}>`
          + (q.unit ? `<span class="gf-unit">${esc(q.unit)}</span>` : '');
        case 'date': return `<input class="gf-input fd-short" type="date" id="${name(q)}" name="${name(q)}"${describedBy(q)}>`;
        case 'time': return `<input class="gf-input fd-short" type="time" id="${name(q)}" name="${name(q)}"${describedBy(q)}>`;
        case 'scale': return scale(q);
        case 'rating': return rating(q);
        case 'file': return file(q);
        default: return '';
      }
    };
    // Single controls get a real <label>; groups (options, scale, rating) a fieldset with a legend.
    const GROUPS = ['radio', 'checkbox', 'scale', 'rating'];
    const questionCard = (q) => {
      const hide = q.showIf ? ' hidden' : '';
      if (q.type === 'confirm') {
        return `<div class="gf-card" data-question="${esc(q.id)}"${hide}><div class="gf-confirm"><input type="checkbox" id="${name(q)}" name="${name(q)}">`
          + `<label class="gf-confirm-label" for="${name(q)}">${esc(q.title)} ${req(q)}</label></div></div>`;
      }
      const title = `<span>${esc(q.title)}</span>${req(q)}`;
      if (GROUPS.includes(q.type)) {
        return `<fieldset class="gf-card" data-question="${esc(q.id)}"${describedBy(q)}${hide}><legend class="sr-only">${esc(q.title)}${q.required ? ' (wajib diisi)' : ''}</legend>`
          + `<div class="gf-q-head"><span class="gf-q-title" aria-hidden="true">${title}</span></div>${help(q)}${image(q)}<div class="gf-body">${body(q)}</div></fieldset>`;
      }
      return `<div class="gf-card" data-question="${esc(q.id)}"${hide}><div class="gf-q-head"><label class="gf-q-title" for="${name(q)}">${title}</label></div>${help(q)}${image(q)}<div class="gf-body">${body(q)}</div></div>`;
    };
    // Section header with its number, a title, or an image.
    const block = (q, sectionNo) => {
      if (q.type === 'section') {
        return `<div class="gf-card gf-section"><div class="gf-section-head"><span class="gf-section-num" aria-hidden="true">${sectionNo}</span>`
          + `<div class="gf-section-text"><h2 class="gf-section-title">${esc(q.title)}</h2>${q.help ? `<p class="gf-section-desc">${esc(q.help)}</p>` : ''}</div></div></div>`;
      }
      if (q.type === 'title') return `<div class="gf-card fd-title"><h2>${esc(q.title)}</h2>${q.help ? `<p class="gf-help">${esc(q.help)}</p>` : ''}</div>`;
      if (q.type === 'image') return q.image && validUrl(q.image) ? `<figure class="gf-card fd-figure"><img src="${esc(q.image)}" alt="${esc(q.title)}" referrerpolicy="no-referrer" loading="lazy">${q.title ? `<figcaption>${esc(q.title)}</figcaption>` : ''}</figure>` : '';
      return '';
    };
    return { name, body, questionCard, block, keptFiles };
  };

  window.QuestionUI = { create, BLOCKS, validUrl, otherOf, decimal, isDecimal };
})();
