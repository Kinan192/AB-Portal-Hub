/* IT Asset Management — Penempatan Perangkat: set location + person in charge for a registered device. ?barcode=<ID> preselects. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, finder = null, form = null;

  AS.ready().then(function () {
    AU.consumeFlash();
    render();
    var b = AU.qs('barcode');
    if (b) { var d = AS.get(b); if (d && d.status !== 'disposed') finder.set(d); else finder.find(b); }
  }).catch(AU.fail);

  function render() {
    DEV = null;
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Penempatan Perangkat', meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-perangkat' }) +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset', req: true, body: '<div data-finder></div>' }) +
      '<div data-body></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, goLabel: 'Pilih', scanTitle: 'Scan Barcode Perangkat',
      filter: function (d) { return d.status === 'disposal' ? d.name + ' sedang dalam proses disposal.' : null; },
      onPick: function (d) { pick(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
    setTimeout(function () { if (!DEV) finder.input.focus(); }, 60);
  }

  function pick(d) {
    DEV = d;
    history.replaceState(null, '', 'penempatan?barcode=' + encodeURIComponent(d.id));
    var placed = AS.placed(d), c = AU.catOf(d);
    var html = GF.section(1, 'Data Perangkat') +
      GF.q({ name: 'devname', title: 'Nama Perangkat', badge: 'Otomatis', body: GF.ro(esc(d.name), AU.code(d.id) + ' · ' + esc(AU.catName(d)) + (d.brand ? ' · ' + esc(d.brand + ' ' + d.model) : '')) }) +
      GF.q({ name: 'devloc', title: 'Lokasi Saat Ini', badge: 'Otomatis', body: GF.ro(esc(AS.locText(d.location))) }) +
      GF.section(2, 'Lokasi Penempatan') +
      GF.locQs('loc_', placed ? d.location : null) +
      GF.q({ name: 'pic', title: 'Penanggung Jawab / Pengguna', badge: 'Opsional', body: GF.text('pic', { value: d.pic }) }) +
      GF.q({ name: 'at', title: 'Tanggal & Waktu Penempatan', req: true, body: GF.text('at', { type: 'datetime-local', value: AU.nowLocal(), max: AU.nowLocal() }) }) +
      GF.actions('Simpan Penempatan', { icon: 'save' });
    var box = form.querySelector('[data-body]');
    box.innerHTML = html; AU.render(box);
    box.querySelector('[data-reset]').addEventListener('click', function () {
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { history.replaceState(null, '', 'penempatan'); render(); } });
    });
    setTimeout(function () { form.querySelector('.gf-section').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
  }

  function submit() {
    GF.clear(form);
    if (!DEV) { GF.err(form, 'barcode', 'Scan atau pilih perangkat terlebih dahulu.'); GF.firstErr(form); return; }
    var d = DEV, ok = GF.need(form, ['loc_dept', 'loc_room', 'at']);
    var loc = GF.readLoc(form, 'loc_'), pic = GF.val(form, 'pic'), at = GF.val(form, 'at');
    if (at && new Date(at).getTime() > Date.now() + 60000) { GF.err(form, 'at', 'Waktu tidak boleh di masa depan.'); ok = false; }
    if (ok && AS.placed(d) && AS.sameLoc(loc, d.location) && pic === (d.pic || '')) { GF.err(form, 'loc_room', 'Lokasi dan penanggung jawab sama dengan data saat ini.'); ok = false; }
    if (!ok) { GF.firstErr(form); return; }
    try { AS.place(d.id, { location: loc, pic: pic, at: new Date(at).toISOString() }); } catch (err) { return AU.fail(err); }
    AS.save().then(function () {
      history.replaceState(null, '', 'penempatan');
      GF.done(app, {
        title: 'Penempatan tersimpan',
        text: d.name + ' ditempatkan di ' + AS.locText(loc) + '.',
        html: GF.kv([['ID Aset', AU.code(d.id), true], ['Lokasi', AS.locText(loc)], ['Penanggung Jawab', pic], ['Tanggal', AU.fmtDateTime(new Date(at).toISOString())]]),
        links: [{ id: 'again', label: 'Tempatkan perangkat lain', icon: 'rotate-ccw', fn: render },
          { href: AU.trackUrl(d.id), label: 'Lacak perangkat ini', icon: 'search' },
          { href: 'rekap-perangkat', label: 'Lihat rekap', icon: 'chart-column' }]
      });
    }, AU.fail);
  }
})();
