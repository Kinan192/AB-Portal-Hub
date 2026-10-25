(() => {
  'use strict';

  const filterForm = document.getElementById('report-filters');
  if (!filterForm) return;
  const { records, perPage } = JSON.parse(document.getElementById('hardware-report-data').textContent);
  const period = document.getElementById('report-period');
  const rack = document.getElementById('report-rack');
  const status = document.getElementById('report-status');
  const search = document.getElementById('report-search');
  const resultCount = document.getElementById('report-result-count');
  const empty = document.getElementById('report-empty');
  const numbers = document.getElementById('report-page-numbers');
  const previous = document.querySelector('[data-report-step="previous"]');
  const next = document.querySelector('[data-report-step="next"]');
  const detailDialog = document.getElementById('report-detail-dialog');
  const tableRows = new Map(Array.from(document.querySelectorAll('[data-report-id]'), (row) => [row.dataset.reportId, row]));
  const pageSize = Math.max(1, Number(perPage) || 7);
  const defaultPeriod = period.value;
  const formatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const rackName = (value) => ({ 'rack-1': 'Rak Server 1', 'rack-2': 'Rak Server 2', 'rack-backup': 'Rak Server Backup' }[value] || value);
  const normalize = (value) => String(value).normalize('NFKC').toLocaleLowerCase('id-ID').trim();
  const searchText = new Map(records.map((record) => [record.id, normalize([
    record.id, record.at, record.shift, rackName(record.rack), record.cpu, record.monitor,
    record.environment, record.notes, record.inspector, record.nik, record.verification,
  ].join(' '))]));
  let page = 1;
  let filtered = [];

  const render = () => {
    const words = normalize(search.value).split(/\s+/).filter(Boolean);
    filtered = records.filter((record) => (period.value === 'all' || record.at.slice(0, 7) === period.value)
      && (rack.value === 'all' || record.rack === rack.value)
      && (status.value === 'all' || record.status === status.value)
      && words.every((word) => searchText.get(record.id).includes(word)));
    const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
    page = Math.min(Math.max(1, page), totalPages);
    const matching = new Set(filtered.map((record) => record.id));
    const visible = new Set(filtered.slice((page - 1) * pageSize, page * pageSize).map((record) => record.id));
    tableRows.forEach((row, id) => {
      row.hidden = !matching.has(id);
      row.classList.toggle('report-page-hidden', matching.has(id) && !visible.has(id));
    });
    empty.hidden = filtered.length !== 0;
    const start = filtered.length ? (page - 1) * pageSize + 1 : 0;
    const end = Math.min(page * pageSize, filtered.length);
    const periodLabel = period.value === 'all' ? 'Semua Arsip' : period.selectedOptions[0].textContent.replace(' (Aktif)', '');
    resultCount.textContent = `Menampilkan ${start ? `${start}–${end}` : '0'} dari ${filtered.length} data log monitoring (${periodLabel})`;
    document.getElementById('report-print-filter').textContent = [
      `Periode: ${periodLabel}`, rack.selectedOptions[0].textContent, status.selectedOptions[0].textContent,
      search.value.trim() ? `Kata kunci: ${search.value.trim()}` : '', `${filtered.length} laporan`,
    ].filter(Boolean).join(' • ');
    previous.disabled = page === 1 || filtered.length === 0;
    next.disabled = page === totalPages || filtered.length === 0;
    document.querySelectorAll('.report-export-actions button').forEach((button) => { button.disabled = filtered.length === 0; });
    numbers.replaceChildren();
    const pages = [...new Set([1, ...Array.from({ length: 5 }, (_, index) => page - 2 + index), totalPages])].filter((number) => number >= 1 && number <= totalPages).sort((a, b) => a - b);
    pages.forEach((number, index) => {
      if (index && number - pages[index - 1] > 1) {
        const dots = document.createElement('span');
        dots.textContent = '…';
        dots.className = 'px-1 text-xs text-slate-400';
        numbers.append(dots);
      }
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = String(number);
      button.dataset.reportPage = String(number);
      button.className = 'report-page-button';
      button.disabled = filtered.length === 0;
      button.setAttribute('aria-label', `Halaman ${number}`);
      if (number === page) button.setAttribute('aria-current', 'page');
      numbers.append(button);
    });
  };
  const resetPageAndRender = () => { page = 1; render(); };
  filterForm.addEventListener('submit', (event) => { event.preventDefault(); resetPageAndRender(); });
  [period, rack, status].forEach((input) => input.addEventListener('change', resetPageAndRender));
  search.addEventListener('input', resetPageAndRender);
  numbers.addEventListener('click', (event) => {
    const button = event.target.closest('[data-report-page]');
    if (!button || button.disabled) return;
    page = Number(button.dataset.reportPage);
    render();
    numbers.querySelector('[aria-current="page"]')?.focus();
  });
  previous.addEventListener('click', () => { if (!previous.disabled) { page -= 1; render(); } });
  next.addEventListener('click', () => { if (!next.disabled) { page += 1; render(); } });
  document.querySelector('[data-report-reset]').addEventListener('click', () => {
    period.value = defaultPeriod;
    rack.value = status.value = 'all';
    search.value = '';
    resetPageAndRender();
    period.focus();
  });

  const openDetail = (id) => {
    const record = records.find((item) => item.id === id);
    if (!record) return;
    document.getElementById('report-detail-id').textContent = record.id;
    const fields = document.getElementById('report-detail-fields');
    fields.replaceChildren();
    [
      ['Waktu Inspeksi', `${formatter.format(new Date(record.at))} WIB`], ['Shift', record.shift],
      ['Rak Server', rackName(record.rack)], ['Kondisi CPU', record.cpu], ['Monitor Console', record.monitor],
      ['Parameter Lingkungan', record.environment], ['Petugas', `${record.inspector} • NIK: ${record.nik}`],
      ['Verifikasi', record.verification], ['Catatan Pemeriksaan', record.notes],
    ].forEach(([label, value]) => {
      const group = document.createElement('div');
      if (label === 'Catatan Pemeriksaan') group.className = 'sm:col-span-2';
      const term = document.createElement('dt');
      term.className = 'text-xs text-slate-500';
      term.textContent = label;
      const description = document.createElement('dd');
      description.className = 'mt-1 whitespace-pre-wrap break-words font-medium leading-relaxed text-slate-800';
      description.textContent = value;
      group.append(term, description);
      fields.append(group);
    });
    detailDialog.showModal();
  };
  document.querySelectorAll('[data-report-detail]').forEach((button) => button.addEventListener('click', () => openDetail(button.dataset.reportDetail)));
  document.querySelectorAll('[data-close-report-detail]').forEach((button) => button.addEventListener('click', () => detailDialog.close()));
  detailDialog.addEventListener('click', (event) => {
    if (event.target !== detailDialog) return;
    const bounds = detailDialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) detailDialog.close();
  });
  document.querySelector('[data-report-print]').addEventListener('click', () => {
    if (filtered.length) window.print();
  });
  document.querySelectorAll('[data-report-export]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById('portal-dialog');
      document.getElementById('dialog-title').textContent = {
        excel: 'Export Excel (.xlsx)', pdf: 'Export PDF Rekap Bulanan', sheet: 'Unduh Lembar Pemeriksaan',
      }[button.dataset.reportExport];
      document.getElementById('dialog-description').textContent = 'Ekspor laporan akan tersedia setelah data pemeriksaan terhubung ke server. Data pada halaman ini mengikuti referensi desain dan belum merupakan laporan operasional. Gunakan Cetak Rekap untuk mencetak tampilan yang sudah difilter.';
      dialog.showModal();
    });
  });
  render();
})();
