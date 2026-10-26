<?php
declare(strict_types=1);

// Creates the single admin account, or changes its password when the email already exists.
// Email, name and password are asked in the terminal; the password is typed hidden and twice,
// and is never taken from arguments, files or environment variables.
// Usage: php bin/create-admin.php   (or: composer admin:create)

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

const MIN_PASSWORD_LENGTH = 12; // PRD SEC-5
const MAX_PASSWORD_BYTES = 72;  // bcrypt ignores anything longer

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

/** @return resource */
function input()
{
    static $input = null;
    if ($input === null) {
        $input = STDIN;
        // Composer on Windows runs scripts with an empty pipe as STDIN; the console is still
        // attached, so read it directly.
        if (PHP_OS_FAMILY === 'Windows' && !stream_isatty(STDIN) && getenv('COMPOSER_BINARY') !== false) {
            $console = @fopen('CONIN$', 'r');
            if ($console !== false && stream_isatty($console)) {
                $input = $console;
            }
        }
    }
    return $input;
}

/** One line of input without its line ending; input piped from PowerShell may start with a UTF-8 BOM. */
function inputLine(): string
{
    $line = fgets(input());
    if ($line === false) {
        fail(PHP_EOL . 'Input terhenti. Tidak ada perubahan.');
    }
    return rtrim((string) preg_replace('/^\xEF\xBB\xBF/', '', $line), "\r\n");
}

function ask(string $prompt): string
{
    echo $prompt;
    return trim(inputLine());
}

function confirm(string $prompt): bool
{
    return in_array(strtolower(ask($prompt . ' (y/N): ')), ['y', 'ya', 'yes'], true);
}

/** Reads one line without showing it on screen. */
function askHidden(string $prompt): string
{
    echo $prompt;
    if (!stream_isatty(input())) {
        // Piped input is never shown. Git Bash (mintty) is not a TTY to PHP either, but it
        // does echo what is typed, so refuse there instead of showing the password.
        if (getenv('MSYSTEM') !== false) {
            fail(PHP_EOL . 'Terminal ini menampilkan ketikan password. Jalankan dari PowerShell/CMD, atau: winpty php bin/create-admin.php');
        }
        return inputLine();
    }
    if (PHP_OS_FAMILY === 'Windows') {
        $value = readHiddenWindows();
    } else {
        shell_exec('stty -echo');
        // inputLine() may exit on end of input, which skips finally blocks; restore echo at shutdown too.
        register_shutdown_function(static fn () => shell_exec('stty echo'));
        $value = inputLine();
        shell_exec('stty echo');
    }
    echo PHP_EOL;
    return $value;
}

/** PHP has no hidden console input on Windows; PowerShell's Read-Host -AsSecureString does it. */
function readHiddenWindows(): string
{
    $script = '[Console]::OutputEncoding = New-Object System.Text.UTF8Encoding $false;'
        . '$secure = Read-Host -AsSecureString;'
        . '$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure);'
        . 'try { [Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)) }'
        . ' finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }';
    // The value comes back through a pipe, never through arguments or a file.
    $process = proc_open(['powershell.exe', '-NoProfile', '-Command', $script], [0 => input(), 1 => ['pipe', 'w'], 2 => STDERR], $pipes);
    if (!is_resource($process)) {
        fail('PowerShell tidak dapat dijalankan untuk membaca password.');
    }
    $value = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    if (proc_close($process) !== 0 || $value === false) {
        fail(PHP_EOL . 'Input password dibatalkan. Tidak ada perubahan.');
    }
    return $value;
}

function askNewPassword(string $email): string
{
    while (true) {
        $password = askHidden('Password baru (tidak terlihat saat diketik): ');
        $problem = match (true) {
            mb_strlen($password) < MIN_PASSWORD_LENGTH => 'Password minimal ' . MIN_PASSWORD_LENGTH . ' karakter.',
            strlen($password) > MAX_PASSWORD_BYTES => 'Password maksimal ' . MAX_PASSWORD_BYTES . ' byte.',
            strcasecmp($password, $email) === 0 => 'Password tidak boleh sama dengan email.',
            default => null,
        };
        if ($problem !== null) {
            fwrite(STDERR, $problem . PHP_EOL);
            continue;
        }
        if (!hash_equals($password, askHidden('Ulangi password: '))) {
            fwrite(STDERR, 'Konfirmasi password tidak sama. Coba lagi.' . PHP_EOL);
            continue;
        }
        return $password;
    }
}

function audit(PDO $pdo, int $userId, string $action): void
{
    $statement = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, user_agent, meta_json) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([$userId, $action, 'user', $userId, 'bin/create-admin.php', json_encode(['source' => 'cli', 'host' => gethostname()])]);
}

$database = config('database')['database'];
try {
    $pdo = Database::connection();
    $table = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $table->execute([$database, 'users']);
    if ((int) $table->fetchColumn() === 0) {
        fail("Tabel users belum ada di database {$database}. Jalankan php bin/migrate.php terlebih dahulu.");
    }
} catch (Throwable $exception) {
    fail('Koneksi database gagal: ' . $exception->getMessage());
}

echo "Akun admin portal (database {$database})" . PHP_EOL;
echo 'Login mencocokkan kolom email: isi kolom Username di halaman login dengan email ini.' . PHP_EOL . PHP_EOL;

while (true) {
    $email = strtolower(ask('Email: '));
    if (strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
        break;
    }
    fwrite(STDERR, 'Email tidak valid.' . PHP_EOL);
}

$find = $pdo->prepare('SELECT id, name, role, is_active FROM users WHERE email = ?');
$find->execute([$email]);
$user = $find->fetch();

try {
    if ($user !== false) {
        $status = $user['is_active'] ? 'aktif' : 'nonaktif';
        echo "Akun sudah ada: {$user['name']} (role {$user['role']}, {$status})." . PHP_EOL;
        $note = $user['role'] !== 'admin' || !$user['is_active'] ? ' Akun juga akan diaktifkan dengan role admin.' : '';
        if (!confirm('Ganti password akun ini?' . $note)) {
            echo 'Tidak ada perubahan.' . PHP_EOL;
            exit(0);
        }
        $hash = password_hash(askNewPassword($email), PASSWORD_DEFAULT, ['cost' => 12]);
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ?, role = ?, is_active = 1 WHERE id = ?')->execute([$hash, 'admin', $user['id']]);
        audit($pdo, (int) $user['id'], 'admin_password_reset');
        $pdo->commit();
        echo 'Password diganti. Login dengan email ' . $email . '.' . PHP_EOL;
        exit(0);
    }

    // The portal has exactly one admin; a second email would create a second account.
    $other = $pdo->prepare('SELECT email FROM users WHERE role = ? LIMIT 1');
    $other->execute(['admin']);
    $existing = $other->fetchColumn();
    if ($existing !== false) {
        fail("Sudah ada akun admin lain ({$existing}). Portal hanya memakai satu admin; jalankan ulang dengan email tersebut untuk mengganti passwordnya.");
    }

    while (true) {
        $name = ask('Nama: ');
        if ($name !== '' && mb_strlen($name) <= 150) {
            break;
        }
        fwrite(STDERR, 'Nama wajib diisi (maksimal 150 karakter).' . PHP_EOL);
    }
    $hash = password_hash(askNewPassword($email), PASSWORD_DEFAULT, ['cost' => 12]);
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)')->execute([$name, $email, $hash, 'admin']);
    $userId = (int) $pdo->lastInsertId();
    audit($pdo, $userId, 'admin_create');
    $pdo->commit();
    echo "Akun admin dibuat untuk {$name}. Login dengan email {$email}." . PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail('Akun gagal disimpan: ' . $exception->getMessage());
}
