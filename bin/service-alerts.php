<?php
declare(strict_types=1);

// Aset IT vendor-deadline reminders: emails open services whose "Tenggat Vendor" is tomorrow (H-1), or already
// today / past when the reminder could not go out earlier. Each service + deadline is emailed once
// (asset_service_alerts). Safe to run often; Windows Task Scheduler runs it every hour (README "Pengingat tenggat").
//
// Usage: php bin/service-alerts.php            send due reminders
//        php bin/service-alerts.php --dry-run  only list what would be sent (no email, nothing recorded)
//        php bin/service-alerts.php --test     send one test email to check the SMTP settings

use App\Core\Database;
use App\Models\AssetRepository;
use App\Services\Mailer;
use App\Services\MailSettings;
use App\Services\ServiceDueAlert;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$args = array_slice($argv, 1);
$dry = in_array('--dry-run', $args, true);
$test = in_array('--test', $args, true);
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$today = $now->format('Y-m-d');
$stamp = $now->format('Y-m-d H:i');

try {
    $pdo = Database::connection();
    // SMTP account and recipients: the portal's "Pengaturan Email" page (app_settings).
    $config = MailSettings::load($pdo);
    $mailer = new Mailer($config);
    $to = MailSettings::recipients($pdo, $config);
    // Links in the email: APP_URL, or http://localhost/<folder> when the portal sits in XAMPP htdocs.
    $url = (string) config('app')['url'];
    if (!preg_match('#^https?://#', $url)) {
        $url = preg_match('#[\\\\/]htdocs[\\\\/]#i', BASE_PATH) ? 'http://localhost/' . basename(BASE_PATH) : 'http://localhost' . $url;
    }

    if ($test) {
        $mailer->send($to, 'Tes email Portal Monitoring IT', '<p>Email pengingat tenggat vendor Aset IT sudah tersambung.</p><p>Dikirim ' . htmlspecialchars($stamp) . ' WIB.</p>',
            "Email pengingat tenggat vendor Aset IT sudah tersambung.\nDikirim {$stamp} WIB.");
        echo "[{$stamp}] Email tes terkirim ke " . implode(', ', $to) . PHP_EOL;
        exit(0);
    }

    $state = (new AssetRepository($pdo))->load();
    $sent = [];
    foreach ($pdo->query('SELECT service_id, due_date FROM asset_service_alerts')->fetchAll() as $r) {
        $sent[$r['service_id'] . '|' . $r['due_date']] = true;
    }
    $due = ServiceDueAlert::due($state['services'], $today, $sent);
    if ($due === []) {
        echo "[{$stamp}] Tidak ada tenggat vendor yang perlu diingatkan." . PHP_EOL;
        exit(0);
    }
    $list = implode(', ', array_map(static fn (array $t): string => $t['id'] . ' (' . $t['dueDate'] . ')', $due));
    if ($dry) {
        echo "[{$stamp}] Akan dikirim ke " . (implode(', ', $to) ?: '(belum ada penerima)') . ": {$list}" . PHP_EOL;
        exit(0);
    }
    $mail = ServiceDueAlert::email($due, $today, rtrim($url, '/'));
    $mailer->send($to, $mail['subject'], $mail['html'], $mail['text']);
    $record = $pdo->prepare('INSERT IGNORE INTO asset_service_alerts (service_id, due_date, sent_at, recipients) VALUES (?, ?, UTC_TIMESTAMP(), ?)');
    foreach ($due as $t) {
        $record->execute([$t['id'], $t['dueDate'], substr(implode(', ', $to), 0, 1000)]);
    }
    echo "[{$stamp}] Pengingat terkirim ke " . implode(', ', $to) . ": {$list}" . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, "[{$stamp}] Gagal: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
