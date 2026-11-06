<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/** Sends email over SMTP with the "Pengaturan Email" settings (MailSettings), via PHPMailer. */
final class Mailer
{
    public function __construct(private readonly array $config)
    {
    }

    public function configured(): bool
    {
        return $this->config['host'] !== '' && $this->config['from_address'] !== '';
    }

    /**
     * @param list<string> $to
     * @throws RuntimeException when email is not set up or the SMTP server refuses the message
     */
    public function send(array $to, string $subject, string $html, string $text): void
    {
        if (!$this->configured()) {
            throw new RuntimeException('Email belum diatur: isi Server SMTP dan email pengirim di menu Pengaturan Email.');
        }
        if ($to === []) {
            throw new RuntimeException('Tidak ada alamat email penerima: isi Email penerima di menu Pengaturan Email.');
        }
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->SMTPAuth = $this->config['username'] !== '';
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = match ($this->config['encryption']) {
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                'none', '' => '',
                default => PHPMailer::ENCRYPTION_STARTTLS,
            };
            $mail->SMTPAutoTLS = $this->config['encryption'] !== 'none';
            $mail->Timeout = 20;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($this->config['from_address'], $this->config['from_name']);
            foreach ($to as $address) {
                $mail->addAddress($address);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            // PHPMailer's message never contains the password.
            throw new RuntimeException(self::explain($mail->ErrorInfo), 0, $e);
        }
    }

    /** The SMTP error in plain words, with what to check. */
    public static function explain(string $error): string
    {
        $hint = match (true) {
            stripos($error, 'authenticate') !== false =>
                'Server menolak username / password. Untuk Gmail: Password harus App Password 16 huruf dari akun yang sama dengan Username (bukan password Gmail biasa), lalu Simpan sebelum tes.',
            stripos($error, 'connect') !== false =>
                'Tidak bisa terhubung ke server SMTP. Cek nama server, port (587 TLS / 465 SSL), dan koneksi internet komputer ini.',
            stripos($error, 'sender') !== false || stripos($error, 'from') !== false =>
                'Alamat pengirim ditolak. Untuk Gmail, kosongkan Email pengirim atau samakan dengan Username.',
            default => '',
        };
        return 'Email gagal dikirim. ' . ($hint !== '' ? $hint . ' ' : '') . '(' . $error . ')';
    }

    /** "a@x.id, b@y.id" → valid, unique addresses. */
    public static function addresses(string $list): array
    {
        $out = [];
        foreach (preg_split('/[,;\s]+/', $list) ?: [] as $a) {
            if ($a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL) !== false) {
                $out[strtolower($a)] ??= $a;
            }
        }
        return array_values($out);
    }
}
