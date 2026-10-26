(() => {
  'use strict';
  const form = document.getElementById('user-report-filters');
  if (!form) return;
  const byId = (id) => document.getElementById(id);
  const { records, perPage } = JSON.parse(byId('user-report-data').textContent);
  const period = byId('user-report-period');
  const status = byId('user-report-status');
  const unit = byId('user-report-unit');
  const search = byId('user-report-search');
  const defaultPeriod = period.value;
  const pages = byId('user-report-pages');
  const previous = document.querySelector('[data-user-step="previous"]');
  const next = document.querySelector('[data-user-step="next"]');
  const detail = byId('user-report-detail');
  const rows = new Map(Array.from(document.querySelectorAll('[data-user-row]'), (row) => [String(row.dataset.userRow), row]));
  const normalize = (value) => String(value).normalize('NFKC').toLocaleLowerCase('id-ID').trim();
  const searchable = new Map(records.map((record) => [record.id, normalize([
    record.username, record.name, record.unit_label, record.notes, record.inspector, record.inspector_role, record.shift,
  ].join(' '))]));
  const formatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const percentageFormatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
  const pageSize = Math.max(1, Math.trunc(Number(perPage)) || 7);
  let page = 1;
  let filtered = [];
  const render = () => {
    const words = normalize(search.value).split(/\s+/).filter(Boolean);
    filtered = records.filter((record) => (period.value === 'all' || record.at.slice(0, 7) === period.value)
      && (status.value === 'all' || (status.value === 'sesuai' ? record.compliant : !record.compliant))
      && (unit.value === 'all' || unit.value === record.unit)
      && words.every((word) => searchable.get(record.id).includes(word)));
    const count = filtered.length;
    const good = filtered.filter((record) => record.compliant).length;
    byId('user-report-total').textContent = String(count);
    byId('user-report-good').textContent = String(good);
    byId('user-report-bad').textContent = String(count - good);
    byId('user-report-percentage').textContent = count ? `${percentageFormatter.format(good / count * 100)}% Kepatuhan` : '—';
    byId('user-report-follow-up').textContent = count - good ? 'Perlu Tindak Lanjut' : 'Tidak ada temuan';
    const latest = filtered[0];
    byId('user-report-latest').textContent = latest ? formatter.format(new Date(latest.at)) : 'Belum ada sampel';
    byId('user-report-latest-inspector').textContent = latest ? `Oleh: ${latest.inspector} (${latest.inspector_role})` : 'Tidak ada data yang sesuai filter.';
    const periodLabel = period.selectedOptions[0].textContent;
    byId('user-report-period-label').textContent = periodLabel;
    byId('user-report-print-filter').textContent = [
      `Periode: ${periodLabel}`, status.selectedOptions[0].textContent, unit.selectedOptions[0].textContent,
      search.value.trim() ? `Pencarian: ${search.value.trim()}` : '', `${count} verifikasi`,
    ].filter(Boolean).join(' • ');
    const totalPages = Math.max(1, Math.ceil(count / pageSize));
    page = Math.min(Math.max(1, page), totalPages);
    const matchingIds = new Set(filtered.map((record) => String(record.id)));
    const visibleIds = new Set(filtered.slice((page - 1) * pageSize, page * pageSize).map((record) => String(record.id)));
    rows.forEach((row, id) => {
      row.hidden = !matchingIds.has(id);
      row.classList.toggle('user-report-page-hidden', matchingIds.has(id) && !visibleIds.has(id));
    });
    byId('user-report-empty').hidden = count !== 0;
    byId('user-report-count').textContent = `Menampilkan ${count ? `${(page - 1) * pageSize + 1}–${Math.min(page * pageSize, count)}` : '0'} dari ${count} data verifikasi kesesuaian user`;
    previous.disabled = !count || page === 1;
    next.disabled = !count || page === totalPages;
    document.querySelectorAll('.user-report-exports button').forEach((button) => { button.disabled = !count; });
    pages.replaceChildren();
    const windowPages = [...new Set([1, ...Array.from({ length: 5 }, (_, index) => page - 2 + index), totalPages])].filter((number) => number >= 1 && number <= totalPages).sort((a, b) => a - b);
    windowPages.forEach((number, index) => {
      if (index && number - windowPages[index - 1] > 1) {
        const dots = document.createElement('span'); dots.textContent = '…'; pages.append(dots);
      }
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'button-neutral module-page-button report-page-button'; button.textContent = String(number);
      button.dataset.userPage = String(number); button.disabled = !count;
      button.setAttribute('aria-label', `Halaman ${number}`);
      if (page === number) button.setAttribute('aria-current', 'page');
      pages.append(button);
    });
  };
  const changed = () => { page = 1; render(); };
  form.addEventListener('submit', (event) => { event.preventDefault(); changed(); });
  [period, status, unit].forEach((input) => input.addEventListener('change', changed));
  search.addEventListener('input', changed);
  previous.addEventListener('click', () => { if (!previous.disabled) { page -= 1; render(); } });
  next.addEventListener('click', () => { if (!next.disabled) { page += 1; render(); } });
  pages.addEventListener('click', (event) => {
    const button = event.target.closest('[data-user-page]');
    if (!button || button.disabled) return;
    page = Number(button.dataset.userPage); render(); pages.querySelector('[aria-current="page"]')?.focus();
  });
  document.querySelector('[data-user-reset]').addEventListener('click', () => {
    period.value = defaultPeriod; status.value = unit.value = 'all'; search.value = ''; changed(); period.focus();
  });
  document.querySelectorAll('[data-user-detail]').forEach((button) => button.addEventListener('click', () => {
    const record = records.find((item) => String(item.id) === button.dataset.userDetail);
    if (!record) return;
    const fields = byId('user-report-detail-fields'); fields.replaceChildren();
    [
      ['Nomor Verifikasi', `#${String(record.id).padStart(3, '0')}`], ['Waktu (WIB)', formatter.format(new Date(record.at))],
      ['Username HIS', record.username], ['Nama Lengkap', record.name], ['Unit / Instalasi', record.unit_label],
      ['Status Regulasi', record.compliant ? 'Sesuai Regulasi' : 'Tidak Sesuai Regulasi'],
      ['Petugas Verifikator', `${record.inspector} (${record.inspector_role})`], ['Shift', record.shift], ['Temuan Sampling', record.notes],
    ].forEach(([label, value]) => {
      const group = document.createElement('div');
      if (label === 'Temuan Sampling') group.className = 'sm:col-span-2';
      const term = document.createElement('dt'); term.className = 'text-xs text-slate-500'; term.textContent = label;
      const description = document.createElement('dd'); description.className = 'mt-1 whitespace-pre-wrap break-words font-medium leading-relaxed text-slate-800'; description.textContent = value;
      group.append(term, description); fields.append(group);
    });
    detail.showModal();
  }));
  document.querySelectorAll('[data-user-close]').forEach((button) => button.addEventListener('click', () => detail.close()));
  detail.addEventListener('click', (event) => {
    if (event.target !== detail) return;
    const bounds = detail.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) detail.close();
  });
  document.querySelector('[data-user-print]').addEventListener('click', () => { if (filtered.length) window.print(); });
  document.querySelectorAll('[data-user-export]').forEach((button) => button.addEventListener('click', () => {
    byId('dialog-title').textContent = button.dataset.userExport === 'excel' ? 'Export Excel (.xlsx)' : 'Export PDF Rekap Bulanan';
    byId('dialog-description').textContent = 'Ekspor laporan akan tersedia setelah data verifikasi terhubung ke server. Data pada halaman ini mengikuti referensi desain dan belum merupakan laporan operasional. Cetak Rekap dapat digunakan untuk mencetak hasil filter saat ini.';
    byId('portal-dialog').showModal();
  }));
  render();
})();
