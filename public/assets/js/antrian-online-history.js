(() => {
  'use strict';
  const form = document.getElementById('queue-report-filters');
  if (!form) return;
  const byId = (id) => document.getElementById(id);
  const { records, perPage, shifts } = JSON.parse(byId('queue-report-data').textContent);
  const period = byId('queue-report-period');
  const queue = byId('queue-report-queue');
  const bed = byId('queue-report-bed');
  const shift = byId('queue-report-shift');
  const search = byId('queue-report-search');
  const defaultPeriod = period.value;
  const pages = byId('queue-report-pages');
  const previous = document.querySelector('[data-queue-step="previous"]');
  const next = document.querySelector('[data-queue-step="next"]');
  const detail = byId('queue-report-detail');
  const rows = new Map(Array.from(document.querySelectorAll('[data-queue-row]'), (row) => [String(row.dataset.queueRow), row]));
  const pageSize = Math.max(1, Math.trunc(Number(perPage)) || 7);
  const formatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  });
  const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
  const normalize = (value) => String(value).normalize('NFKC').toLocaleLowerCase('id-ID').trim();
  const shiftName = (key) => shifts[key]?.short || key;
  const searchable = new Map(records.map((record) => [record.id, normalize([
    record.id, record.at, formatter.format(new Date(record.at)), shiftName(record.shift), record.queue_http,
    record.queue_sent ? 'Terkirim' : 'Tidak Terkirim', record.bed_sent ? 'Terkirim Update' : 'Tidak Terkirim',
    record.attachment, record.notes, record.inspector, record.inspector_role, record.tickets, record.tasks, record.task_note,
  ].join(' '))]));
  let page = 1;
  let filtered = [];
  const matchesStatus = (choice, value) => choice === 'all' || (choice === 'sent' ? value : !value);
  const render = () => {
    const query = normalize(search.value);
    filtered = records.filter((record) => (period.value === 'all' || record.at.slice(0, 7) === period.value)
      && matchesStatus(queue.value, record.queue_sent) && matchesStatus(bed.value, record.bed_sent)
      && (shift.value === 'all' || shift.value === record.shift)
      && searchable.get(record.id).includes(query));
    const count = filtered.length;
    const queueSent = filtered.filter((record) => record.queue_sent).length;
    const bedSent = filtered.filter((record) => record.bed_sent).length;
    byId('queue-report-total').textContent = String(count);
    byId('queue-report-queue-rate').textContent = count ? `${number.format(queueSent / count * 100)}%` : '—';
    byId('queue-report-bed-rate').textContent = count ? `${number.format(bedSent / count * 100)}%` : '—';
    byId('queue-report-queue-count').textContent = `Terkirim: ${queueSent} • Gagal/Timeout: ${count - queueSent}`;
    byId('queue-report-bed-count').textContent = `Terkirim: ${bedSent} • Gagal: ${count - bedSent}`;
    const bedLabel = byId('queue-report-bed-label');
    bedLabel.textContent = !count ? 'Belum ada data' : bedSent === count ? 'Optimal' : 'Perlu Perhatian';
    bedLabel.className = `rounded-full px-2 py-0.5 font-mono text-[9px] ${!count ? 'bg-slate-100 text-slate-600' : bedSent === count ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`;
    const latest = filtered[0];
    byId('queue-report-latest').textContent = latest ? `${formatter.format(new Date(latest.at))} WIB` : 'Belum ada audit';
    byId('queue-report-latest-shift').textContent = latest ? shiftName(latest.shift) : '—';
    byId('queue-report-latest-inspector').textContent = latest ? `Oleh: ${latest.inspector} (${latest.inspector_role})` : 'Tidak ada data yang sesuai filter.';
    const periodLabel = period.selectedOptions[0].textContent.replace(' (Aktif)', '');
    byId('queue-report-period-label').textContent = periodLabel;
    byId('queue-report-print-filter').textContent = [
      `Periode: ${periodLabel}`, `Cron antrian: ${queue.selectedOptions[0].textContent}`, `Cron bed: ${bed.selectedOptions[0].textContent}`,
      shift.selectedOptions[0].textContent, search.value.trim() ? `Pencarian: ${search.value.trim()}` : '', `${count} sesi`,
    ].filter(Boolean).join(' • ');
    const totalPages = Math.max(1, Math.ceil(count / pageSize));
    page = Math.min(Math.max(1, page), totalPages);
    const matching = new Set(filtered.map((record) => String(record.id)));
    const visible = new Set(filtered.slice((page - 1) * pageSize, page * pageSize).map((record) => String(record.id)));
    rows.forEach((row, id) => {
      row.hidden = !matching.has(id);
      row.classList.toggle('queue-report-page-hidden', matching.has(id) && !visible.has(id));
    });
    byId('queue-report-empty').hidden = count !== 0;
    byId('queue-report-count').textContent = `Menampilkan ${count ? `${(page - 1) * pageSize + 1}–${Math.min(page * pageSize, count)}` : '0'} dari ${count} data rekaman kepatuhan`;
    previous.disabled = !count || page === 1;
    next.disabled = !count || page === totalPages;
    document.querySelectorAll('.queue-report-exports button').forEach((button) => { button.disabled = !count; });
    pages.replaceChildren();
    const windowPages = [...new Set([1, ...Array.from({ length: 5 }, (_, index) => page - 2 + index), totalPages])].filter((value) => value >= 1 && value <= totalPages).sort((a, b) => a - b);
    windowPages.forEach((value, index) => {
      if (index && value - windowPages[index - 1] > 1) { const dots = document.createElement('span'); dots.textContent = '…'; pages.append(dots); }
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'button-neutral module-page-button report-page-button queue-report-page'; button.textContent = String(value);
      button.dataset.queuePage = String(value); button.disabled = !count; button.setAttribute('aria-label', `Halaman ${value}`);
      if (value === page) button.setAttribute('aria-current', 'page');
      pages.append(button);
    });
  };
  const changed = () => { page = 1; render(); };
  form.addEventListener('submit', (event) => { event.preventDefault(); changed(); });
  [period, queue, bed, shift].forEach((input) => input.addEventListener('change', changed));
  search.addEventListener('input', changed);
  previous.addEventListener('click', () => { if (!previous.disabled) { page -= 1; render(); } });
  next.addEventListener('click', () => { if (!next.disabled) { page += 1; render(); } });
  pages.addEventListener('click', (event) => {
    const button = event.target.closest('[data-queue-page]');
    if (!button || button.disabled) return;
    page = Number(button.dataset.queuePage); render(); pages.querySelector('[aria-current="page"]')?.focus();
  });
  document.querySelector('[data-queue-reset-filter]').addEventListener('click', () => {
    period.value = defaultPeriod; queue.value = bed.value = shift.value = 'all'; search.value = ''; changed(); period.focus();
  });
  document.querySelectorAll('[data-queue-detail]').forEach((button) => button.addEventListener('click', () => {
    const record = records.find((item) => String(item.id) === button.dataset.queueDetail);
    if (!record) return;
    const fields = byId('queue-report-detail-fields'); fields.replaceChildren();
    [
      ['Nomor Audit', `#${String(record.id).padStart(3, '0')}`], ['Waktu Audit (WIB)', formatter.format(new Date(record.at))],
      ['Shift', shiftName(record.shift)], ['Petugas', `${record.inspector} (${record.inspector_role})`],
      ['Cron Antrian', `${record.queue_sent ? 'Terkirim' : 'Tidak Terkirim'}${record.queue_http === null ? '' : ` (HTTP ${record.queue_http})`}`],
      ['Cron Bed MJKN', record.bed_sent ? 'Terkirim (Update)' : 'Tidak Terkirim'],
      ['Jumlah Tiket', number.format(record.tickets)], ['Task ID', record.tasks === null ? record.task_note : number.format(record.tasks)],
      ['Bukti Log', `${record.attachment} — berkas belum tersedia`], ['Catatan Operasional', record.notes],
    ].forEach(([label, value]) => {
      const group = document.createElement('div'); if (label === 'Catatan Operasional') group.className = 'sm:col-span-2';
      const term = document.createElement('dt'); term.className = 'text-xs text-slate-500'; term.textContent = label;
      const description = document.createElement('dd'); description.className = 'mt-1 whitespace-pre-wrap break-words font-medium leading-relaxed text-slate-800'; description.textContent = value;
      group.append(term, description); fields.append(group);
    });
    detail.showModal();
  }));
  document.querySelectorAll('[data-queue-close-detail]').forEach((button) => button.addEventListener('click', () => detail.close()));
  detail.addEventListener('click', (event) => {
    if (event.target !== detail) return;
    const bounds = detail.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) detail.close();
  });
  const showInfo = (title, description) => {
    byId('dialog-title').textContent = title; byId('dialog-description').textContent = description; byId('portal-dialog').showModal();
  };
  document.querySelector('[data-queue-print]').addEventListener('click', () => { if (filtered.length) window.print(); });
  document.querySelectorAll('[data-queue-export]').forEach((button) => button.addEventListener('click', () => {
    showInfo(button.dataset.queueExport === 'excel' ? 'Export Excel (.xlsx)' : 'Export PDF Rekap Bulanan', 'Ekspor operasional belum tersedia karena laporan belum terhubung ke server. Data pada halaman ini berasal dari referensi desain. Cetak Rekap dapat digunakan untuk mencetak seluruh hasil filter.');
  }));
  document.querySelectorAll('[data-queue-log]').forEach((button) => button.addEventListener('click', () => {
    showInfo('Bukti Log Monitoring', `Nama lampiran: ${button.dataset.queueLog}. Berkas ini belum tersedia; referensi desain hanya memuat nama lampiran. Unduhan akan dihubungkan setelah penyimpanan bukti server tersedia.`);
  }));
  document.querySelector('[data-queue-sop]').addEventListener('click', () => {
    showInfo('Dokumen SOP Integrasi BPJS', 'Dokumen SOP belum tersedia pada project. Nama SOP pada halaman mengikuti referensi desain. Unduhan akan dihubungkan setelah dokumen resmi ditambahkan oleh pengelola.');
  });
  render();
})();
