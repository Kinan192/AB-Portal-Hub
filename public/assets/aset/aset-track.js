/* IT Asset Management — Lacak Perangkat (Google Forms style): lookup → info & history → update location */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, finder = null, form = null;
  var RECENT_KEY = 'as.recentTracked';

  AS.ready().then(function () {
    AU.consumeFlash();
    shell();
    var id = AU.qs('id');
    if (id) { var d = AS.get(id); if (d) open(d, location.hash === '#pindah'); else finder.find(id); }
  }).catch(AU.fail);

  function recent() { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').filter(function (x) { return AS.get(x); }); } catch (e) { return []; } }
  function pushRecent(id) { try { var r = recent().filter(function (x) { return x !== id; }); r.unshift(id); localStorage.setItem(RECENT_KEY, JSON.stringify(r.slice(0, 5))); } catch (e) {} }

  function shell() {
    var rec = recent();
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Lacak Perangkat',
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-perpindahan' }) +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset', req: true, body: '<div data-finder></div>' +
        (rec.length ? '<div class="gf-chips"><span class="gf-chips__label">Terakhir dilacak:</span>' + rec.map(function (id) { return '<button type="button" class="gf-chip" data-pick="' + esc(id) + '">' + esc(AS.get(id).name) + '</button>'; }).join('') + '</div>' : '') }) +
      '<div data-result></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, includeDisposed: true, label: 'Barcode / ID Aset', goLabel: 'Lacak', scanTitle: 'Scan Barcode untuk Melacak',
      onPick: function (d) { open(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    form.addEventListener('click', function (e) { var b = e.target.closest('[data-pick]'); if (b) finder.set(AS.get(b.getAttribute('data-pick'))); });
    form.addEventListener('submit', function (e) { e.preventDefault(); if (DEV) submitMove(); });
    if (!AU.qs('id')) setTimeout(function () { finder.input.focus(); }, 60);
  }

  function open(d, toMove) {
    DEV = d; pushRecent(d.id); finder.input.value = d.id; finder.error('');
    history.replaceState(null, '', AU.trackUrl(d.id));
    document.title = d.name + ' - Lacak Perangkat - Portal Monitoring IT RS Awal Bros Botania';
    drawResult();
    var target = toMove ? form.querySelector('[data-sec="move"]') : form.querySelector('[data-sec="info"]');
    if (target) setTimeout(function () { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 80);
  }

  function infoHtml(d) {
    var c = AU.catOf(d), moves = AS.movesOf(d.id), prev = moves[0] ? moves[0].from : null, dead = d.status === 'disposed';
    var since = moves[0] ? moves[0].at : d.createdAt;
    var openSvc = AS.openService(d.id), openDsp = AS.openDisposal(d.id), svcs = AS.servicesOf(d.id);
    var notice = '';
    if (dead) {
      var r = AS.getDisposal(d.disposalId) || {};
      notice = '<div class="gf-card as-notice as-notice--muted">' + ic('archive') + '<div><b>Perangkat sudah dihapuskan (disposed)</b> pada ' + esc(AU.fmtDate(d.disposedAt)) + (r.method ? ' — ' + esc(r.method) : '') +
        '. Tidak lagi dilacak secara aktif; seluruh riwayat tetap tersimpan sebagai arsip.</div></div>';
    } else if (openDsp) notice = '<div class="gf-card as-notice as-notice--warn">' + ic('archive') + '<div><b>Dalam proses disposal</b> — ' + esc(AS.DSP_STATUS[openDsp.status].label) + ' (' + esc(openDsp.id) + '). <a class="gf-link" href="disposal?barcode=' + encodeURIComponent(d.id) + '">Buka formulir disposal</a></div></div>';
    else if (openSvc) notice = '<div class="gf-card as-notice as-notice--info">' + ic('wrench') + '<div><b>Sedang diservis</b> — ' + esc(openSvc.type) + ', ' + esc(AS.SVC_STATUS[openSvc.status].label) + ' · ' + esc(openSvc.technician) + (openSvc.dueDate ? ' ' + AU.dueBadge(openSvc) : '') + '. <button type="button" class="gf-link as-linkbtn" data-svc-status="' + esc(openSvc.id) + '">Ubah status servis</button></div></div>';

    var specRows = (c.spec || []).filter(function (s) { return d.spec && d.spec[s.key]; }).map(function (s) { return [s.label, s.type === 'date' ? AU.fmtDate(d.spec[s.key]) : d.spec[s.key]]; });
    return GF.section(1, 'Informasi Perangkat', '', null, ' data-sec="info"') +
      '<section class="gf-card"><div class="as-devhead"><span class="as-tile">' + ic(c.icon) + '</span><div class="as-devhead__text"><b>' + esc(d.name) + '</b>' +
      '<div class="as-devhead__meta">' + AU.code(d.id) + '<span>' + esc(AU.catName(d)) + '</span>' + AU.statusPill(d.status) + '</div></div></div>' +
      GF.kv([['Departemen', d.department], ['Merek / Model', [d.brand, d.model].filter(Boolean).join(' ')], ['Serial Number', d.serial], ['IP Address', d.ip], ['MAC Address', d.mac], ['Penanggung Jawab', d.pic]].concat(specRows)
        .concat([['Tanggal Pembelian', d.purchaseDate ? AU.fmtDate(d.purchaseDate) : ''], d.invoices && d.invoices.length ? ['Invoice Pembelian', AU.docsHtml(d.invoices), true] : null, ['Terdaftar', AU.fmtDateTime(d.createdAt) + ' · ' + d.createdBy], ['Catatan', d.notes]])) +
      '<div class="as-done__links" style="margin-top:14px">' + (dead ? '' : '<a class="gf-link" href="registrasi?edit=' + encodeURIComponent(d.id) + '">' + ic('pencil') + 'Edit data</a>') +
      '</div></section>' +
      (dead ? '' : '<section class="gf-card"><h3 class="gf-q__title" style="margin-bottom:14px">Tindak Lanjut</h3>' +
        '<div style="display:flex;flex-wrap:wrap;gap:10px">' +
        '<a class="gf-btn gf-btn--primary" href="servis?barcode=' + encodeURIComponent(d.id) + '">' + ic('wrench') + (openSvc ? 'Perbarui Servis' : 'Servis Perangkat') + '</a>' +
        '<a class="gf-btn gf-btn--ghost" href="penempatan?barcode=' + encodeURIComponent(d.id) + '">' + ic('map-pin') + 'Penempatan Perangkat</a>' +
        '<a class="gf-btn gf-btn--ghost" href="disposal?barcode=' + encodeURIComponent(d.id) + '">' + ic('archive') + (openDsp ? 'Lanjutkan Disposal' : 'Ajukan Disposal') + '</a>' +
        '</div></section>') +
      notice +
      '<section class="gf-card"><h3 class="gf-q__title" style="margin-bottom:12px">Lokasi Perangkat</h3><div class="as-locpair">' +
      '<div class="as-locbox as-locbox--now"><span class="as-locs__k">' + ic('map-pin') + (dead ? 'Lokasi terakhir diketahui' : 'Lokasi saat ini') + '</span>' + (AS.placed(d) ? '<span class="as-locs__v">' + esc(d.location.dept + ' · ' + d.location.room) + '</span>' + where(d.location) + '<span class="as-locs__s rs-muted">Sejak ' + esc(AU.fmtDateTime(since)) + '</span>' : '<span class="as-locs__v">Belum ditempatkan</span><a class="gf-link" href="penempatan?barcode=' + encodeURIComponent(d.id) + '">Tentukan lokasi</a>') + '</div>' +
      '<div class="as-locbox"><span class="as-locs__k">' + ic('history') + 'Lokasi sebelumnya</span>' + (prev ? '<span class="as-locs__v">' + esc(prev.dept + ' · ' + prev.room) + '</span>' + where(prev) + '<span class="as-locs__s rs-muted">Hingga ' + esc(AU.fmtDateTime(moves[0].at)) + '</span>' : '<span class="as-locs__s rs-muted">Belum pernah dipindahkan sejak registrasi.</span>') + '</div>' +
      '</div></section>' +
      '<section class="gf-card"><div class="gf-q__head"><h3 class="gf-q__title">Riwayat Perpindahan</h3><span class="gf-q__badge">' + moves.length + ' catatan</span></div>' +
      (moves.length ? '<ol class="as-tl" style="margin-top:6px">' + moves.map(function (m) {
        return '<li><span class="as-tl__dot"></span><div class="as-tl__head"><span class="as-move"><span>' + esc(m.from.dept + ' · ' + m.from.room) + '</span>' + ic('arrow-right') + '<b>' + esc(m.to.dept + ' · ' + m.to.room) + '</b></span><span class="as-tl__time">' + esc(AU.fmtDateTime(m.at)) + '</span></div>' +
          '<div class="as-tl__body">' + esc(m.reason) + (m.note ? ' — ' + esc(m.note) : '') + (m.picTo && m.picTo !== m.picFrom ? '<br>Penanggung jawab: ' + esc(m.picFrom || '—') + ' → ' + esc(m.picTo) : '') + '</div><div class="as-tl__by">Diperbarui oleh ' + esc(m.by) + '</div></li>';
      }).join('') + '</ol>' : '<p class="gf-q__help">Belum ada perpindahan. Perangkat masih di lokasi registrasi.</p>') + '</section>' +
      (svcs.length ? '<section class="gf-card"><div class="gf-q__head"><h3 class="gf-q__title">Riwayat Servis</h3><span class="gf-q__badge">' + svcs.length + ' laporan</span></div><ul class="as-list">' + svcs.slice(0, 4).map(function (t) {
        return '<li><a class="as-list__item" href="servis?barcode=' + encodeURIComponent(d.id) + '"><span class="as-list__text"><b>' + esc(t.type) + ' · ' + esc(t.id) + '</b><small>' + esc(t.problem) + '</small></span><span class="as-list__meta">' + esc(AU.fmtDate(t.reportDate)) + '<br>' + AU.svcPill(t.status) + '</span></a></li>';
      }).join('') + '</ul></section>' : '');
  }

  /* Gedung / Lantai of an older location (no longer asked); nothing for a new one. */
  function where(l) { var t = [l.building, l.floor].filter(Boolean).join(', '); return t ? '<span class="as-locs__s">' + esc(t) + '</span>' : ''; }

  function moveHtml(d) {
    if (!AS.placed(d)) return GF.section(2, 'Lokasi Penempatan', '', null, ' data-sec="move" id="pindah"') +
      '<section class="gf-card"><p style="margin:0 0 14px">Perangkat ini belum ditempatkan.</p><a class="gf-btn gf-btn--primary" href="penempatan?barcode=' + encodeURIComponent(d.id) + '">' + ic('map-pin') + 'Tentukan Lokasi Penempatan</a></section>';
    return GF.section(2, 'Laporkan / Perbarui Lokasi', '', null, ' data-sec="move" id="pindah"') +
      GF.q({ name: 'prev', title: 'Lokasi Sebelumnya', badge: 'Otomatis', body: GF.ro(esc(d.location.dept + ' · ' + d.location.room), esc([d.location.building, d.location.floor].filter(Boolean).join(', '))) }) +
      GF.q({ name: 'quick', title: 'Isi cepat lokasi baru', badge: 'Opsional', body: '<div class="gf-chips" style="margin-top:0">' +
        '<button type="button" class="gf-chip" data-quick="gudang">Gudang IT · Rak Cadangan</button><button type="button" class="gf-chip" data-quick="workshop">Workshop IT</button><button type="button" class="gf-chip" data-quick="same">Unit sama, ganti ruangan</button></div>' }) +
      GF.locQs('to_', null) +
      GF.q({ name: 'at', title: 'Tanggal & Waktu Perpindahan', req: true, body: GF.text('at', { type: 'datetime-local', value: AU.nowLocal(), max: AU.nowLocal() }) }) +
      GF.q({ name: 'by', title: 'Diperbarui Oleh', req: true, body: GF.text('by', { value: AS.me(), list: 'dl-techs' }) + GF.datalist('dl-techs', AS.TECHNICIANS) }) +
      GF.q({ name: 'reason', title: 'Alasan Perpindahan', req: true, body: GF.radio('reason', AS.MOVE_REASONS.filter(function (r) { return r !== 'Lainnya'; }), '', { other: true }) }) +
      GF.q({ name: 'pic', title: 'Penanggung Jawab Baru', badge: 'Opsional', body: GF.text('pic', {}) }) +
      GF.q({ name: 'note', title: 'Keterangan', badge: 'Opsional', body: GF.area('note', {}) }) +
      GF.actions('Kirim Pembaruan Lokasi', { reset: 'Kosongkan bagian ini' });
  }

  function drawResult(confirmHtml) {
    var d = DEV, box = form.querySelector('[data-result]');
    box.innerHTML = infoHtml(d) + (d.status === 'disposed' ? '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a></div>'
      : confirmHtml || moveHtml(d));
    AU.render(box);
    var sv = box.querySelector('[data-svc-status]');
    if (sv) sv.addEventListener('click', function () {
      var tk = AS.openService(d.id);
      if (tk) AU.serviceStatus(tk, function () { DEV = AS.get(d.id); drawResult(); });
    });
    var rs = box.querySelector('[data-reset]');
    if (rs) rs.addEventListener('click', function () { drawResult(); box.querySelector('[data-sec="move"]').scrollIntoView({ block: 'start' }); });
    box.querySelectorAll('[data-quick]').forEach(function (b) {
      b.addEventListener('click', function () {
        var k = b.getAttribute('data-quick'), set = function (n, v) { var el = form.querySelector('[name="' + n + '"]'); if (el) { el.value = v; GF.err(form, n, ''); } };
        if (k === 'gudang') { set('to_dept', 'Gudang IT'); set('to_room', 'Rak Cadangan'); var r = form.querySelector('[name="reason"][value="Dikembalikan ke gudang"]'); if (r) r.checked = true; GF.err(form, 'reason', ''); }
        if (k === 'workshop') { set('to_dept', 'Instalasi IT'); set('to_room', 'Workshop IT'); var r2 = form.querySelector('[name="reason"][value="Dibawa untuk perbaikan"]'); if (r2) r2.checked = true; GF.err(form, 'reason', ''); }
        if (k === 'same') { set('to_dept', d.location.dept); set('to_room', ''); form.querySelector('[name="to_room"]').focus(); }
      });
    });
    box.querySelectorAll('[data-again]').forEach(function (b) { b.addEventListener('click', function () { drawResult(); form.querySelector('[data-sec="move"]').scrollIntoView({ behavior: 'smooth', block: 'start' }); }); });
    box.querySelectorAll('[data-other]').forEach(function (b) { b.addEventListener('click', function () { DEV = null; history.replaceState(null, '', 'lacak'); shell(); }); });
  }

  function submitMove() {
    var d = DEV; GF.clear(form);
    var ok = GF.need(form, ['to_dept', 'to_room', 'at', 'by', 'reason']);
    var to = GF.readLoc(form, 'to_');
    if (ok && AS.sameLoc(to, d.location)) { GF.err(form, 'to_room', 'Lokasi baru sama dengan lokasi saat ini.'); ok = false; }
    var at = GF.val(form, 'at'), atIso = at ? new Date(at).toISOString() : '';
    if (at && new Date(at).getTime() > Date.now() + 60000) { GF.err(form, 'at', 'Waktu tidak boleh di masa depan.'); ok = false; }
    var last = AS.movesOf(d.id)[0];
    if (at && last && atIso < last.at) { GF.err(form, 'at', 'Lebih awal dari perpindahan terakhir (' + AU.fmtDateTime(last.at) + ').'); ok = false; }
    if (!ok) { GF.firstErr(form); return; }
    var from = d.location, m;
    try { m = AS.move(d.id, { to: to, reason: GF.val(form, 'reason'), note: GF.val(form, 'note'), at: atIso, by: GF.val(form, 'by'), pic: GF.val(form, 'pic') }); }
    catch (err) { return AU.fail(err); }
    AS.save().then(function () {
      AU.toast('Lokasi diperbarui', d.name + ' → ' + to.dept + ' · ' + to.room, 'success');
      drawResult('<div class="gf-card gf-head as-done" data-sec="move" id="pindah"><span class="as-done__icon">' + ic('circle-check') + '</span><h1>Lokasi berhasil diperbarui</h1>' +
        '<p class="gf-head__desc">Perpindahan ' + esc(d.name) + ' telah dicatat di riwayat.</p>' +
        GF.kv([['Lokasi sebelumnya', AS.locText(m.from)], ['Lokasi baru', AS.locText(m.to)], ['Tanggal & waktu', AU.fmtDateTime(m.at)], ['Diperbarui oleh', m.by], ['Alasan', m.reason + (m.note ? ' — ' + m.note : '')]]) +
        '<div class="as-done__links"><button type="button" class="gf-link-btn" style="padding:0" data-again>' + ic('rotate-ccw') + 'Kirim pembaruan lain</button>' +
        '<button type="button" class="gf-link-btn" style="padding:0" data-other>' + ic('search') + 'Lacak perangkat lain</button>' +
        '<a class="gf-link" href="rekap-perpindahan">' + ic('chart-column') + 'Lihat rekap</a></div></div>');
      var c = form.querySelector('[data-sec="move"]'); if (c) c.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, AU.fail);
  }
})();
