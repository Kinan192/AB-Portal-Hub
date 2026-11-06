/* IT Asset Management — Disposal Perangkat (Google Forms style).
 * The form follows the device's disposal state: request → approval → execution → archived. Nothing is deleted. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, R = null, MODE = '', finder = null, form = null, picker = null;
  var ROLES = ['Manajer IT', 'Kepala Bagian Umum & Aset', 'Manajer Keuangan', 'Direktur'];

  AS.ready().then(function () {
    AU.consumeFlash();
    render();
    var b = AU.qs('barcode') || AU.qs('new');
    var rid = AU.qs('id'); if (!b && rid) { var r = AS.getDisposal(rid); if (r) b = r.deviceId; }
    if (b) { var d = AS.get(b); if (d) finder.set(d); else finder.find(b); }
  }).catch(AU.fail);

  function chips() {
    var S = AS.state(), grp = function (k, label) {
      var l = S.disposals.filter(function (r) { return r.status === k; }); if (!l.length) return '';
      return '<div class="as-chip-group"><span>' + label + ':</span>' + l.map(function (r) { return '<button type="button" class="gf-chip" data-pick="' + esc(r.deviceId) + '" title="' + esc(r.id) + '">' + esc(r.deviceName) + '</button>'; }).join('') + '</div>';
    };
    var html = grp('pending', 'Menunggu persetujuan') + grp('approved', 'Disetujui, menunggu pelaksanaan');
    return html ? GF.q({ name: 'open', title: 'Pengajuan yang sedang diproses', body: '<div class="as-chip-list">' + html + '</div>' }) : '';
  }

  function render() {
    DEV = null; R = null; MODE = ''; picker = null;
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Disposal Perangkat',
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-disposal' }) +
      chips() +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset Perangkat', req: true, body: '<div data-finder></div>' }) +
      '<div data-body></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, includeDisposed: true, goLabel: 'Pilih', scanTitle: 'Scan Barcode Perangkat',
      onPick: function (d) { pick(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    form.addEventListener('click', function (e) { var b = e.target.closest('[data-pick]'); if (b) finder.set(AS.get(b.getAttribute('data-pick'))); });
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
    setTimeout(function () { if (!DEV) finder.input.focus(); }, 60);
  }

  function flowHtml(r) {
    var steps = [['pending', 'Pengajuan'], ['approved', 'Persetujuan'], ['disposed', 'Dihapuskan']], idx = r ? { pending: 0, approved: 1, disposed: 2, rejected: 1 }[r.status] : -1;
    return '<div class="as-flow">' + steps.map(function (s, i) {
      var label = r && r.status === 'rejected' && i === 1 ? 'Ditolak' : s[1];
      return i === idx ? '<b class="is-now">' + label + '</b>' : '<span' + (i < idx ? '' : ' style="opacity:.7"') + '>' + (i < idx ? '✓ ' : '') + label + '</span>';
    }).join(ic('chevron-right')) + '</div>';
  }
  function requestSummary(r) {
    return GF.kv([['No. Pengajuan', AU.code(r.id), true], ['Status Disposal', AU.dspPill(r.status), true], ['Alasan', r.reason], ['Penjelasan', r.reasonDetail], ['Kondisi', r.condition],
      ['Diajukan', AU.fmtDateTime(r.requestedAt) + ' · ' + r.requestedBy], ['Rencana Disposal', r.disposalDate ? AU.fmtDate(r.disposalDate) : ''],
      ['Disetujui', r.approvedAt && r.status !== 'rejected' ? AU.fmtDate(r.approvedAt) : ''],
      ['Ditolak', r.status === 'rejected' ? AU.fmtDate(r.approvedAt) : ''], ['Catatan Keputusan', r.decisionNote],
      ['Tanggal Dihapuskan', r.disposedAt ? AU.fmtDate(r.disposedAt) : ''], ['Metode', r.method], ['No. Berita Acara', r.reference], ['Lampiran', AU.docsHtml(r.docs), true]]);
  }
  // Documents are uploaded to the portal when the form is sent (AU.filePicker), and open from the archive.
  function dropHtml(name, title) {
    return GF.q({ name: name, title: title, badge: 'Opsional', help: 'Maks. 6 file.', body: AU.fileField(name, { max: 6 }) });
  }
  function wireDrop() {
    var name = form.querySelector('[data-drop]') && form.querySelector('[data-drop]').getAttribute('data-drop');
    picker = name ? AU.filePicker(form, name, { max: 6 }) : null;
  }
  function docsThen(fn, btn) {
    (picker ? picker.upload('disposal') : Promise.resolve([])).then(fn, function (err) { btn.disabled = false; AU.fail(err); });
  }

  function pick(d) {
    DEV = d; picker = null;
    history.replaceState(null, '', 'disposal?barcode=' + encodeURIComponent(d.id));
    var all = AS.disposalsOf(d.id), svc = AS.openService(d.id);
    R = AS.openDisposal(d.id) || (d.status === 'disposed' ? AS.getDisposal(d.disposalId) : null);
    MODE = d.status === 'disposed' ? 'archived' : R ? R.status : svc ? 'blocked' : 'request';
    var c = AU.catOf(d), today = AU.today();
    var html = GF.section(1, 'Data Perangkat') +
      GF.q({ name: 'devname', title: 'Nama Perangkat', badge: 'Otomatis', body: GF.ro(esc(d.name), AU.code(d.id) + (d.brand ? ' · ' + esc(d.brand + ' ' + d.model) : '')) }) +
      GF.q({ name: 'devcat', title: 'Kategori Perangkat', badge: 'Otomatis', body: GF.ro(esc(AU.catName(d))) }) +
      GF.q({ name: 'devloc', title: 'Lokasi Terakhir Diketahui', badge: 'Otomatis', body: GF.ro(esc(AS.locText(d.location))) }) +
      GF.q({ name: 'dstatus', title: 'Status Disposal', badge: 'Otomatis', body: (R ? '<div style="display:flex;flex-direction:column;gap:10px">' + AU.dspPill(R.status) + flowHtml(R) + '</div>' : GF.ro('Belum ada pengajuan aktif')) });

    if (MODE === 'archived') {
      html += GF.section(2, 'Arsip Disposal', 'Perangkat sudah dihapuskan dan keluar dari pelacakan aktif.') +
        '<section class="gf-card">' + requestSummary(R || {}) + '<div class="as-done__links" style="margin-top:14px"><a class="gf-link" href="' + AU.trackUrl(d.id) + '">' + ic('history') + 'Lihat riwayat lengkap perangkat</a></div></section>' +
        '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a><span class="gf-actions__spacer"></span><button type="button" class="gf-btn gf-btn--text" data-reset>Pilih perangkat lain</button></div>';
    } else if (MODE === 'blocked') {
      html += '<div class="gf-card as-notice as-notice--warn">' + ic('wrench') + '<div><b>Perangkat masih dalam servis</b> (' + esc(svc.id) + ' · ' + esc(AS.SVC_STATUS[svc.status].label) + '). Selesaikan laporan servis terlebih dahulu sebelum mengajukan disposal. ' +
        '<a class="gf-link" href="servis?barcode=' + encodeURIComponent(d.id) + '">Buka formulir servis</a></div></div>' +
        '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a><span class="gf-actions__spacer"></span><button type="button" class="gf-btn gf-btn--text" data-reset>Pilih perangkat lain</button></div>';
    } else if (MODE === 'request') {
      var lastSvc = AS.servicesOf(d.id)[0];
      html += GF.section(2, 'Pengajuan Disposal', 'Alasan dan kondisi perangkat yang akan dihapuskan.', 'Pengajuan baru') +
        (lastSvc ? '<div class="gf-card as-notice as-notice--muted">' + ic('wrench') + '<div>Servis terakhir: <b>' + esc(lastSvc.id + ' · ' + lastSvc.type) + '</b> (' + esc(AS.SVC_STATUS[lastSvc.status].label) + ')' + (lastSvc.notes ? ' — ' + esc(lastSvc.notes) : '') + '</div></div>' : '') +
        GF.q({ name: 'reason', title: 'Alasan Disposal', req: true, body: GF.radio('reason', AS.DSP_REASONS.filter(function (x) { return x !== 'Lainnya'; }), '', { other: true }) }) +
        GF.q({ name: 'reasonDetail', title: 'Penjelasan', req: true, body: GF.area('reasonDetail', { rows: 3 }) }) +
        GF.q({ name: 'condition', title: 'Kondisi Perangkat', req: true, body: GF.radio('condition', AS.DSP_CONDITIONS, '') }) +
        GF.q({ name: 'disposalDate', title: 'Rencana Tanggal Disposal', badge: 'Opsional', body: GF.text('disposalDate', { type: 'date' }) }) +
        dropHtml('docs', 'Dokumen / Foto Pendukung') +
        (all.length ? '<section class="gf-card"><h3 class="gf-q__title">Pengajuan sebelumnya</h3><ul class="as-list">' + all.map(function (r) { return '<li><div class="as-list__item"><span class="as-list__text"><b>' + esc(r.id + ' · ' + r.reason) + '</b><small>' + esc(r.decisionNote || r.reasonDetail) + '</small></span><span class="as-list__meta">' + esc(AU.fmtDate(r.requestedAt)) + '<br>' + AU.dspPill(r.status) + '</span></div></li>'; }).join('') + '</ul></section>' : '') +
        GF.actions('Kirim Pengajuan');
    } else if (MODE === 'pending') {
      html += GF.section(2, 'Persetujuan Disposal', '', R.id) +
        '<section class="gf-card"><span class="as-mode">' + ic('badge-check') + 'Menunggu keputusan</span>' + requestSummary(R) + '</section>' +
        GF.q({ name: 'decision', title: 'Keputusan', req: true, body: GF.radio('decision', [{ value: 'approve', label: 'Setujui disposal' }, { value: 'reject', label: 'Tolak pengajuan' }], '') }) +
        GF.q({ name: 'disposalDate', title: 'Jadwal Disposal', badge: 'Opsional', body: GF.text('disposalDate', { type: 'date', value: R.disposalDate }) }) +
        GF.q({ name: 'note', title: 'Catatan Keputusan', help: 'Wajib diisi jika pengajuan ditolak.', body: GF.area('note', {}) }) +
        GF.actions('Simpan Keputusan', { icon: 'save' });
    } else if (MODE === 'approved') {
      html += GF.section(2, 'Pelaksanaan Disposal', '', R.id) +
        '<section class="gf-card"><span class="as-mode">' + ic('archive') + 'Disetujui · siap dilaksanakan</span>' + requestSummary(R) + '</section>' +
        GF.q({ name: 'date', title: 'Tanggal Disposal', req: true, body: GF.text('date', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'method', title: 'Metode Disposal', req: true, body: GF.radio('method', AS.DSP_METHODS, '') }) +
        GF.q({ name: 'reference', title: 'No. Berita Acara', badge: 'Opsional', body: GF.text('reference', { mono: true }) }) +
        dropHtml('bafiles', 'Berita Acara / Foto Pelaksanaan') +
        '<div class="gf-card as-notice as-notice--info">' + ic('info') + '<div>Setelah dikirim, status perangkat menjadi <b>Disposed</b> dan perangkat keluar dari daftar aset aktif. Data dan seluruh riwayatnya <b>tetap tersimpan</b> sebagai arsip.</div></div>' +
        GF.actions('Tandai Sudah Dihapuskan', { icon: 'archive', danger: true });
    }
    var box = form.querySelector('[data-body]');
    box.innerHTML = html; AU.render(box); wireDrop();
    var rs = box.querySelector('[data-reset]');
    if (rs) rs.addEventListener('click', function () {
      if (MODE === 'archived' || MODE === 'blocked') { history.replaceState(null, '', 'disposal'); render(); return; }
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { history.replaceState(null, '', 'disposal'); render(); } });
    });
    setTimeout(function () { form.querySelector('.gf-section').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
  }

  function finish(title, text, r) {
    history.replaceState(null, '', 'disposal');
    GF.done(app, { title: title, text: text, html: requestSummary(r),
      links: [{ id: 'again', label: 'Isi formulir lain', icon: 'rotate-ccw', fn: render }, { href: AU.trackUrl(r.deviceId), label: 'Lacak perangkat', icon: 'map-pin' }, { href: 'rekap-disposal', label: 'Lihat rekap disposal', icon: 'chart-column' }] });
  }

  function submit() {
    GF.clear(form);
    if (!DEV) { GF.err(form, 'barcode', 'Scan atau pilih perangkat terlebih dahulu.'); GF.firstErr(form); return; }
    var d = DEV, btn = form.querySelector('button[type="submit"]');
    if (MODE === 'request') {
      if (!GF.need(form, ['reason', 'reasonDetail', 'condition'])) return GF.firstErr(form);
      btn.disabled = true;
      docsThen(function (docs) {
        var r;
        try { r = AS.requestDisposal({ deviceId: d.id, reason: GF.val(form, 'reason'), reasonDetail: GF.val(form, 'reasonDetail'), condition: GF.val(form, 'condition'), disposalDate: GF.val(form, 'disposalDate'), docs: docs }); }
        catch (err) { btn.disabled = false; return AU.fail(err); }
        AS.save().then(function () { finish('Pengajuan disposal terkirim', r.id + ' — ' + d.name + ' menunggu persetujuan. Perangkat berstatus Proses Disposal.', r); }, function (err) { btn.disabled = false; AU.fail(err); });
      }, btn);
    } else if (MODE === 'pending') {
      var ok = GF.need(form, ['decision']), dec = GF.val(form, 'decision');
      if (dec === 'reject' && !GF.val(form, 'note')) { GF.err(form, 'note', 'Jelaskan alasan penolakan.'); ok = false; }
      if (!ok) return GF.firstErr(form);
      var input = { approvedBy: AS.me(), role: '', note: GF.val(form, 'note'), disposalDate: GF.val(form, 'disposalDate') };
      try { if (dec === 'approve') AS.approveDisposal(R.id, input); else AS.rejectDisposal(R.id, input); } catch (err) { return AU.fail(err); }
      AS.save().then(function () {
        finish(dec === 'approve' ? 'Disposal disetujui' : 'Pengajuan ditolak', dec === 'approve' ? R.id + ' siap dilaksanakan. Catat pelaksanaannya di formulir ini setelah perangkat dihapuskan.' : R.id + ' ditolak. ' + d.name + ' kembali berstatus ' + AS.STATUS[AS.get(d.id).status].label + '.', R);
      }, AU.fail);
    } else if (MODE === 'approved') {
      var ok2 = GF.need(form, ['date', 'method']);
      if (GF.val(form, 'date') > AU.today()) { GF.err(form, 'date', 'Tanggal tidak boleh di masa depan.'); ok2 = false; }
      if (!ok2) return GF.firstErr(form);
      AU.confirm({ title: 'Hapuskan ' + d.name + '?', danger: true, ok: 'Ya, tandai dihapuskan',
        text: 'Perangkat <code class="as-code">' + esc(d.id) + '</code> akan berstatus <b>Disposed</b> dan dikeluarkan dari daftar aset aktif. Data dan riwayatnya tetap tersimpan di arsip dan tidak dapat diubah lagi.' })
        .then(function (yes) {
          if (!yes) return;
          btn.disabled = true;
          docsThen(function (docs) {
            try { AS.completeDisposal(R.id, { date: GF.val(form, 'date'), method: GF.val(form, 'method'), reference: GF.val(form, 'reference'), docs: docs }); }
            catch (err) { btn.disabled = false; return AU.fail(err); }
            AS.save().then(function () { finish('Perangkat telah dihapuskan', d.name + ' kini berstatus Disposed dan diarsipkan. Riwayat lokasi, servis, dan disposal tetap dapat dibuka.', R); }, function (err) { btn.disabled = false; AU.fail(err); });
          }, btn);
        });
    }
  }
})();
