<?php
/**
 * Pengaturan Email (POST /pengaturan-email): the SMTP account of the Aset IT vendor-deadline reminders and their
 * recipients (App\Services\MailSettings). The stored password is never sent to the page; empty keeps it.
 * @var array $settings
 * @var array<string, string> $errors
 */
$f = static fn (string $name, string $label, array $o = []): string => partial('field', [
    'variant' => 'account', 'label' => $label, 'name' => $name, 'type' => $o['type'] ?? 'text', 'value' => $o['value'] ?? null,
    'control' => $o['control'] ?? 'input', 'options' => $o['options'] ?? null, 'selected' => $o['selected'] ?? null,
    'placeholder' => $o['ph'] ?? null, 'required' => $o['required'] ?? false, 'toggle' => $o['toggle'] ?? false,
    'attrs' => ($o['attrs'] ?? []) + ['autocomplete' => $o['autocomplete'] ?? 'off'],
    'error' => ['id' => $name . 'Error', 'text' => $errors[$name] ?? ''], 'help' => $o['help'] ?? null,
]);
$s = $settings;
$ready = ($s['host'] ?? '') !== '' && (($s['from_address_set'] ?? '') !== '' || filter_var($s['username'] ?? '', FILTER_VALIDATE_EMAIL) !== false);

$fields = csrf_field()
    . '<h3 class="rs-subhead">Akun pengirim (SMTP)</h3>'
    . $f('host', 'Server SMTP', ['value' => $s['host'], 'ph' => 'smtp.gmail.com', 'help' => 'Kosongkan untuk mematikan email pengingat.', 'attrs' => ['maxlength' => '190', 'spellcheck' => 'false']])
    . '<div class="rs-field-row">'
    . $f('port', 'Port', ['type' => 'number', 'value' => (string) $s['port'], 'required' => true, 'attrs' => ['min' => '1', 'max' => '65535', 'inputmode' => 'numeric']])
    . $f('encryption', 'Keamanan', ['control' => 'select', 'options' => \App\Services\MailSettings::ENCRYPTIONS, 'selected' => $s['encryption']])
    . '</div>'
    . $f('username', 'Username', ['value' => $s['username'], 'ph' => 'akun@gmail.com', 'autocomplete' => 'username', 'help' => 'Biasanya alamat email pengirim.', 'attrs' => ['maxlength' => '190', 'spellcheck' => 'false']])
    . $f('password', 'Password', ['type' => 'password', 'toggle' => true, 'autocomplete' => 'new-password',
        'ph' => !empty($s['has_password']) ? '•••••••• (tersimpan)' : '',
        'help' => (!empty($s['has_password']) ? 'Password sudah tersimpan (terenkripsi). Kosongkan bila tidak diubah. ' : '') . 'Gmail: pakai App Password 16 huruf, bukan password Gmail biasa.',
        'attrs' => ['maxlength' => '200']])
    . (!empty($s['has_password']) ? '<label class="rs-check"><input type="checkbox" name="clear_password" value="1"> Hapus password tersimpan</label>' : '')
    . '<div class="rs-field-row">'
    . $f('from_address', 'Email pengirim', ['type' => 'email', 'value' => $s['from_address_set'] ?? '', 'ph' => 'Sama dengan username', 'attrs' => ['maxlength' => '190']])
    . $f('from_name', 'Nama pengirim', ['value' => $s['from_name'], 'attrs' => ['maxlength' => '100']])
    . '</div>'
    . '<h3 class="rs-subhead">Penerima pengingat</h3>'
    . $f('alert_to', 'Email penerima', ['control' => 'textarea', 'value' => $s['service_alert_to'], 'ph' => 'nama@rs.id, kepala.it@rs.id',
        'help' => 'Pisahkan dengan koma. Kosong = email semua akun portal yang aktif.', 'attrs' => ['rows' => '2', 'maxlength' => '1000', 'spellcheck' => 'false']]);

$foot = partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'class' => 'button-neutral module-action rs-btn border', 'icon' => 'arrow-left'])
    . partial('button', ['label' => 'Simpan Pengaturan', 'type' => 'submit', 'class' => 'button-primary module-action rs-btn', 'icon' => 'circle-check']);

$noPassword = $ready && ($s['username'] ?? '') !== '' && empty($s['has_password']);
$status = '<dl class="rs-kv"><dt>Status</dt><dd>' . ($ready
        ? (!empty($s['password_unreadable']) ? '<span class="rs-pill rs-pill--warn"><i></i>Isi ulang password</span>'
            : ($noPassword ? '<span class="rs-pill rs-pill--warn"><i></i>Password belum diisi</span>' : '<span class="rs-pill rs-pill--ok"><i></i>Siap dites</span>'))
        : '<span class="rs-pill rs-pill--muted"><i></i>Belum diatur</span>') . '</dd>'
    . '<dt>Password</dt><dd>' . (!empty($s['has_password']) ? 'Tersimpan (terenkripsi)' : '<span style="color:#b42318">Belum ada</span>') . '</dd>'
    . (!empty($s['updated_at']) ? '<dt>Diubah</dt><dd>' . e(\App\Helpers\Format::dateTime((string) $s['updated_at'])) . (!empty($s['updated_by']) ? '<br><small>' . e((string) $s['updated_by']) . '</small>' : '') . '</dd>' : '')
    . '</dl>'
    . (!empty($s['password_unreadable']) ? '<p class="rs-help">Password tersimpan tidak bisa dibaca (kunci enkripsi berbeda, mis. setelah pindah komputer). Isi password lagi lalu simpan.</p>' : '')
    . '<form method="post" action="' . e(url('/pengaturan-email/tes')) . '" class="rs-mail-test">' . csrf_field()
    . partial('button', ['label' => 'Kirim Email Tes', 'type' => 'submit', 'class' => 'button-neutral module-action rs-btn border', 'icon' => 'send', 'attrs' => $ready ? [] : ['disabled' => true]])
    . '<p class="rs-help">Dikirim dengan pengaturan yang <b>sudah disimpan</b> ke penerima pengingat.</p></form>';

$guide = '<ol class="rs-steps">'
    . '<li>Gmail: aktifkan <b>Verifikasi 2 Langkah</b> di akun Google.</li>'
    . '<li>Buka <b>myaccount.google.com/apppasswords</b>, buat App Password, salin 16 hurufnya.</li>'
    . '<li>Isi: server <code>smtp.gmail.com</code>, port <code>587</code>, TLS, username = alamat Gmail, password = App Password.</li>'
    . '<li>Simpan, lalu klik <b>Kirim Email Tes</b>.</li></ol>'
    . '<p class="rs-help">Pengingat dikirim H-1 sebelum <b>Tenggat Vendor</b> servis Aset IT, oleh tugas terjadwal <code>bin/service-alerts.php</code>.</p>';
?>
<main id="main-content" class="rs-page page-shell">
    <?= partial('page-header', ['variant' => 'account', 'eyebrow' => 'Pengaturan', 'title' => 'Pengaturan Email', 'description' => 'Akun email untuk mengirim pengingat tenggat vendor Aset IT.']) ?>
    <div class="rs-grid">
        <?= partial('card', [
            'tag' => 'form', 'attrs' => ['id' => 'mailForm', 'method' => 'post', 'action' => url('/pengaturan-email'), 'autocomplete' => 'off'],
            'title' => 'SMTP & Penerima', 'icon' => 'mail',
            'body' => $fields, 'foot' => $foot,
        ]) ?>
        <div class="rs-stack">
            <?= partial('card', ['tag' => 'aside', 'title' => 'Status', 'icon' => 'circle-check', 'body' => $status]) ?>
            <?= partial('card', ['tag' => 'aside', 'title' => 'Cara memakai Gmail', 'icon' => 'info', 'body' => $guide]) ?>
        </div>
    </div>
</main>
