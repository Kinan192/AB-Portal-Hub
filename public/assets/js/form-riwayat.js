(() => {
  'use strict';

  /* History of a form (app/Views/form-dinamis/respons.php), frontend only: the reports in the browser state
     (dashboard-store.js: sample reports of the built-in forms + the ones sent in this tab by form-dinamis.js).
     One column per question of the form as it is now, so the history always matches the form; a report
     sent before a question existed shows "—" there. Search, period filter, 25 rows per page, a card list
     on phones and a detail dialog with every question and its answer. Nothing is sent to the server. */
  const store = window.DashboardStore;
  if (!store?.ready) return;
  const { $, $$, esc, icon, MONTHS_LONG, nowWib, optLabel, oneDecimal, tempStatus } = store;
  const page = $('[data-form-history]');
  if (!page) return;

  const state = store.load();
  const formId = page.dataset.formId;
  const builtin = page.dataset.builtin === '1';
  const card = state.cards.find((item) => item.id === formId && item.kind === (builtin ? 'builtin' : 'form')) ?? null;
  // A built-in form keeps its history after its card was deleted from the dashboard.
  const form = state.forms?.[formId] ?? (builtin ? store.seed().forms?.[formId] : null);
  if (!form || (!builtin && !card)) { $('[data-fill-missing]').hidden = false; return; }

  const BLOCKS = ['section', 'title', 'image'];
  const questions = (form.questions ?? []).filter((q) => !BLOCKS.includes(q.type));
  const columns = questions.filter((q) => q.type !== 'confirm'); // the declaration is always "Ya": detail only
  const reports = [...(state.responses?.[formId] ?? [])].sort((a, b) => String(b.at).localeCompare(String(a.at)));
  const title = form.title || card?.title || '';
  const PER_PAGE = 25;

  /* ---------- Values ---------- */
  const when = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
  const monthParts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit' });
  const monthOf = (iso) => { const p = Object.fromEntries(monthParts.formatToParts(new Date(iso)).map((x) => [x.type, x.value])); return `${p.year}-${p.month}`; };
  const monthName = (key) => { const [y, m] = key.split('-'); return `${MONTHS_LONG[+m - 1]} ${y}`; };
  // "2026-10-27", "2026-10-27T08:12" and "2026-10-27 08:12" (WIB, as typed) → "27 Okt 2026, 08.12"
  const SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const localDate = (text) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?$/.exec(String(text));
    if (!m) return String(text);
    return `${+m[3]} ${SHORT[+m[2] - 1]} ${m[1]}${m[4] ? `, ${m[4]}.${m[5]}` : ''}`;
  };
  const isEmpty = (v) => v === undefined || v === null || v === '' || (Array.isArray(v) && !v.length) || (typeof v === 'object' && !Array.isArray(v) && !Object.keys(v).length);
  const redLabels = (q) => (q.options ?? []).filter((o) => typeof o === 'object' && o.tone === 'red').map(optLabel);
  /* One answer as { text, tone, chip, rows } (tone: ok | warn | bad, for the chip and "Perlu perhatian"). */
  const show = (q, value) => {
    if (isEmpty(value)) return null;
    switch (q.type) {
      case 'temperature': {
        const s = tempStatus(q, +value);
        return { text: `${oneDecimal(+value)} °C`, chip: s.text, tone: s.tone };
      }
      case 'number': return { text: (+value).toLocaleString('id-ID') };
      case 'grid': {
        // Every row, plus a summary: a row with another choice than the first column counts as a finding.
        const first = q.columns?.[0]?.label;
        const rows = Object.entries(value);
        // Counted in column order ("Patuh 2 · Temuan 1"); values outside the columns come last.
        const order = [...(q.columns ?? []).map((col) => col.label), ...rows.map(([, v]) => v)];
        const counts = [...new Set(order)].map((label) => [label, rows.filter(([, v]) => v === label).length]).filter(([, n]) => n);
        const off = rows.some(([, v]) => v !== first);
        return { text: counts.map(([k, n]) => `${k} ${n}`).join(' · '), tone: off ? 'warn' : 'ok', rows };
      }
      case 'date': case 'datetime': case 'datetime2': return { text: localDate(value) };
      case 'confirm': return { text: 'Dicentang' };
      default: {
        const text = Array.isArray(value) ? value.join(', ') : String(value);
        const red = redLabels(q);
        return red.length && (Array.isArray(value) ? value : [value]).some((v) => red.includes(v)) ? { text, tone: 'bad', chip: text } : { text };
      }
    }
  };
  const attention = (report) => columns.some((q) => ['warn', 'bad'].includes(show(q, report.answers?.[q.id])?.tone));
  const searchText = (report) => [report.id, when.format(new Date(report.at)), ...questions.map((q) => {
    const s = show(q, report.answers?.[q.id]);
    return s ? `${s.text} ${s.chip ?? ''} ${(s.rows ?? []).flat().join(' ')}` : '';
  })].join(' ').toLowerCase();

  /* ---------- Header and filters ---------- */
  const tile = $('[data-fr-tile]');
  tile.className = `card-icon tone-${card?.color ?? 'teal'} fr-head__icon`;
  tile.innerHTML = icon(card?.icon ?? 'clipboard-list');
  $('[data-fr-title]').textContent = title;
  $('[data-fr-root]').hidden = false;
  const monthSelect = $('[data-fr-month]');
  [...new Set(reports.map((r) => monthOf(r.at)))].forEach((key) => monthSelect.add(new Option(monthName(key), key)));
  const search = $('[data-fr-search]');
  const reset = $('[data-fr-reset]');
  let pageNo = 0;

  /* ---------- Drawing ---------- */
  const cellHtml = (s, clamp = true) => {
    if (!s) return '<span class="fr-muted">—</span>';
    if (s.chip && s.chip !== s.text) return `<span class="fr-value">${esc(s.text)}</span> <span class="fr-chip fr-chip--${s.tone}">${esc(s.chip)}</span>`;
    if (s.chip || s.rows) return `<span class="fr-chip fr-chip--${s.tone ?? 'plain'}">${esc(s.text)}</span>`;
    return `<span class="${clamp ? 'fr-clamp' : 'fr-value'}">${esc(s.text)}</span>`;
  };
  const tableHtml = (list, offset) => '<div class="fr-table-wrap" role="region" aria-label="Tabel riwayat" tabindex="0"><table class="fr-table"><thead><tr>'
    + '<th scope="col" class="fr-col-no">No</th><th scope="col">Waktu kirim (WIB)</th>'
    + columns.map((q) => `<th scope="col">${esc(q.title)}</th>`).join('')
    + '<th scope="col"><span class="sr-only">Detail</span></th></tr></thead><tbody>'
    + list.map((r, i) => `<tr data-open="${esc(r.id)}"><td class="fr-col-no">${offset + i + 1}</td>`
      + `<td class="fr-nowrap">${esc(when.format(new Date(r.at)))}${attention(r) ? ' <span class="fr-dot" title="Perlu perhatian" aria-label="Perlu perhatian"></span>' : ''}</td>`
      + columns.map((q) => `<td>${cellHtml(show(q, r.answers?.[q.id]))}</td>`).join('')
      + `<td><button type="button" class="dash-btn dash-btn--ghost dash-btn--sm fr-open" data-open="${esc(r.id)}" aria-label="${esc(`Lihat detail laporan ${when.format(new Date(r.at))}`)}">${icon('eye', 'size-4')}Detail</button></td></tr>`).join('')
    + '</tbody></table></div>';
  // Phones: one card per report with its first answers.
  const listHtml = (list) => `<ul class="fr-list">${list.map((r) => {
    const lines = columns.map((q) => [q, show(q, r.answers?.[q.id])]).filter(([, s]) => s).slice(0, 3);
    return `<li><button type="button" class="fr-item" data-open="${esc(r.id)}"><span class="fr-item__time">${esc(when.format(new Date(r.at)))}${attention(r) ? '<span class="fr-chip fr-chip--warn">Perlu perhatian</span>' : ''}</span>`
      + lines.map(([q, s]) => `<span class="fr-item__row"><span class="fr-item__q">${esc(q.title)}</span><span class="fr-item__a">${esc(s.chip && s.chip !== s.text ? `${s.text} · ${s.chip}` : s.text)}</span></span>`).join('')
      + `<span class="fr-item__more">${icon('eye', 'size-4')}Lihat semua jawaban</span></button></li>`;
  }).join('')}</ul>`;
  const render = () => {
    const term = search.value.trim().toLowerCase();
    const month = monthSelect.value;
    const shown = reports.filter((r) => (!month || monthOf(r.at) === month) && (!term || searchText(r).includes(term)));
    const pages = Math.max(1, Math.ceil(shown.length / PER_PAGE));
    pageNo = Math.min(pageNo, pages - 1);
    const from = pageNo * PER_PAGE;
    const list = shown.slice(from, from + PER_PAGE);
    reset.hidden = !term && !month;
    $('[data-fr-count]').textContent = shown.length ? `Menampilkan ${from + 1}–${from + list.length} dari ${shown.length} laporan` : '0 laporan';
    $('[data-fr-body]').innerHTML = shown.length
      ? tableHtml(list, from) + listHtml(list)
      : `<div class="fr-empty">${icon('history', 'size-6')}<b>${reports.length ? 'Tidak ada laporan untuk filter ini' : 'Belum ada laporan'}</b>`
        + `<span>${reports.length ? 'Ubah kata kunci atau periode, atau reset filter.' : 'Laporan akan tampil di sini setelah form dikirim.'}</span></div>`;
    const pager = $('[data-fr-pager]');
    pager.hidden = pages < 2;
    $('[data-fr-page-info]').textContent = `Halaman ${pageNo + 1} dari ${pages}`;
    $('[data-fr-page="-1"]').disabled = pageNo === 0;
    $('[data-fr-page="1"]').disabled = pageNo >= pages - 1;
    // Figures follow the filter, except "Bulan ini" (the current WIB month) and the last report.
    const thisMonth = monthOf(nowWib().day.toISOString().slice(0, 10) + 'T05:00:00Z');
    $('[data-stat="shown"]').textContent = shown.length.toLocaleString('id-ID');
    $('[data-stat="month"]').textContent = reports.filter((r) => monthOf(r.at) === thisMonth).length.toLocaleString('id-ID');
    $('[data-stat="attention"]').textContent = shown.filter(attention).length.toLocaleString('id-ID');
    $('[data-stat="last"]').textContent = reports.length ? when.format(new Date(reports[0].at)) : '–';
  };
  render();
  let timer = 0;
  search.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(() => { pageNo = 0; render(); }, 300); });
  monthSelect.addEventListener('change', () => { pageNo = 0; render(); });
  reset.addEventListener('click', () => { search.value = ''; monthSelect.value = ''; pageNo = 0; render(); search.focus(); });
  $('[data-fr-pager]').addEventListener('click', (event) => {
    const step = event.target.closest('[data-fr-page]');
    if (!step || step.disabled) return;
    pageNo += +step.dataset.frPage;
    render();
    $('#fr-table-title').scrollIntoView({ block: 'start' });
  });

  /* ---------- Detail: every question of the form with its answer ---------- */
  const dialog = $('#fr-detail');
  let opener = null;
  const detail = (report) => {
    let sectionNo = 0;
    const parts = (form.questions ?? []).map((q) => {
      if (q.type === 'section') { sectionNo++; return `<h3 class="fr-detail__section"><span>${sectionNo}</span>${esc(q.title)}</h3>`; }
      if (BLOCKS.includes(q.type)) return '';
      const s = show(q, report.answers?.[q.id]);
      const label = q.type === 'confirm' ? 'Pernyataan' : q.title;
      let answer = cellHtml(s, false);
      if (s?.rows) answer = `<ul class="fr-detail__rows">${s.rows.map(([row, v]) => `<li><span>${esc(row)}</span><span class="fr-chip fr-chip--${v === q.columns?.[0]?.label ? 'ok' : 'warn'}">${esc(v)}</span></li>`).join('')}</ul>`;
      return `<div class="fr-qa"><dt>${esc(label)}</dt><dd>${answer}</dd></div>`;
    }).join('');
    $('[data-detail-title]', dialog).textContent = title;
    $('[data-detail-sub]', dialog).textContent = `Dikirim ${when.format(new Date(report.at))} WIB · ${report.id}`;
    $('[data-detail-body]', dialog).innerHTML = `<dl class="fr-detail__list">${parts}</dl>`;
  };
  page.addEventListener('click', (event) => {
    const target = event.target.closest('[data-open]');
    if (!target || event.target.closest('a')) return;
    const report = reports.find((r) => r.id === target.dataset.open);
    if (!report) return;
    opener = event.target.closest('button') ?? $(`button[data-open="${CSS.escape(report.id)}"]:not([hidden])`, page);
    detail(report);
    dialog.showModal();
    $('[data-detail-close]', dialog).focus();
  });
  $$('[data-detail-close]', dialog).forEach((button) => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const r = dialog.getBoundingClientRect();
    if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
  });
  dialog.addEventListener('close', () => opener?.focus({ preventScroll: true }));
})();
