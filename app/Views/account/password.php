<?php
/**
 * Change password (POST /password). The server checks the rules (App\Services\PasswordPolicy) and sends
 * the messages back in $errors; js/design.js only checks empty fields and the confirmation first.
 * @var array<string, string> $errors
 */
$fields = csrf_field();
foreach ([
    ['currentPassword', 'Password saat ini', 'current-password', 'currentError', null],
    ['newPassword', 'Password baru', 'new-password', 'newError', 'Minimal ' . \App\Services\PasswordPolicy::minLength() . ' karakter.'],
    ['confirmPassword', 'Konfirmasi password baru', 'new-password', 'confirmError', null],
] as [$id, $label, $autocomplete, $errorId, $help]) {
    $fields .= partial('field', [
        'variant' => 'account', 'label' => $label, 'type' => 'password', 'name' => $id, 'required' => true, 'toggle' => true,
        'attrs' => ['autocomplete' => $autocomplete, 'required' => true, 'maxlength' => '200'],
        'error' => ['id' => $errorId, 'text' => $errors[$id] ?? ''], 'help' => $help,
    ]);
}
$foot = partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'class' => 'button-neutral module-action rs-btn border', 'icon' => 'arrow-left'])
    . partial('button', ['label' => 'Simpan Password', 'type' => 'submit', 'class' => 'button-primary module-action rs-btn', 'icon' => 'circle-check']);
$account = '<div class="rs-person"><span class="rsab-avatar">' . e($profile['initials']) . '</span><span><b>' . e($profile['name']) . '</b><small>' . e($profile['subtitle']) . '</small></span></div>'
    . '<dl class="rs-kv"><dt>Username</dt><dd>' . e($profile['username'] !== '' ? $profile['username'] : '-') . '</dd><dt>Status</dt><dd><span class="rs-pill rs-pill--ok"><i></i>Online</span></dd></dl>';
?>
<main id="main-content" class="rs-page page-shell">
    <?= partial('page-header', ['variant' => 'account', 'eyebrow' => 'Akun & Keamanan', 'title' => 'Password', 'description' => 'Perangkat lain akan keluar setelah password diubah.']) ?>
    <div class="rs-grid">
        <?= partial('card', [
            'tag' => 'form', 'attrs' => ['id' => 'passwordForm', 'method' => 'post', 'action' => url('/password'), 'novalidate' => true, 'data-password-form' => true],
            'title' => 'Ubah Password', 'icon' => 'key-round',
            'body' => $fields, 'foot' => $foot,
        ]) ?>
        <?= partial('card', ['tag' => 'aside', 'title' => 'Akun', 'icon' => 'user', 'body' => $account]) ?>
    </div>
</main>
