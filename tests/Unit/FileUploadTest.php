<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FileUpload;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/** PRD FR-F11: a 12 MB file or a .exe renamed to .pdf is refused; real evidence files pass. */
final class FileUploadTest extends TestCase
{
    private const EXT = ['pdf', 'csv', 'xlsx', 'jpg', 'jpeg', 'png'];
    private const MAX = 10 * 1048576;
    /** A 1 × 1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function file(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $bytes);
        return $this->files[] = $path;
    }

    private function check(string $name, string $path, ?int $size = null): array
    {
        $upload = ['name' => $name, 'tmp_name' => $path, 'size' => $size ?? filesize($path), 'error' => UPLOAD_ERR_OK];
        return FileUpload::check($upload, self::EXT, self::MAX, static fn (): bool => true);
    }

    public function testRealFilesPass(): void
    {
        self::assertSame(['ext' => 'pdf', 'mime' => 'application/pdf'], $this->check('log.pdf', $this->file("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n")));
        self::assertSame(['ext' => 'png', 'mime' => 'image/png'], $this->check('foto.PNG', $this->file(base64_decode(self::PNG))));
        self::assertSame(['ext' => 'csv', 'mime' => 'text/csv'], $this->check('log.csv', $this->file("tanggal;suhu\n2026-10-01;21,5\n")));
    }

    public function testExeRenamedToPdfIsRefused(): void
    {
        $exe = $this->file("MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF\x00\x00" . str_repeat("\x00", 64) . 'This program cannot be run in DOS mode.');
        self::assertSame(['error' => 'Format file tidak didukung. Gunakan PDF, CSV, XLSX, JPG, atau PNG.'], $this->check('laporan.pdf', $exe));
        self::assertArrayHasKey('error', $this->check('laporan.csv', $exe));
        // The message names the field's own types: Clear Desk photos are JPG or PNG only (a PDF is refused too).
        $photo = static fn (string $name, string $path): array => FileUpload::check(['name' => $name, 'tmp_name' => $path, 'size' => filesize($path), 'error' => UPLOAD_ERR_OK], ['jpg', 'jpeg', 'png'], self::MAX, static fn (): bool => true);
        self::assertSame(['error' => 'Format file tidak didukung. Gunakan JPG atau PNG.'], $photo('foto.jpg', $exe));
        self::assertSame(['error' => 'Format file tidak didukung. Gunakan JPG atau PNG.'], $photo('surat.pdf', $this->file("%PDF-1.4\n%%EOF\n")));
        self::assertArrayHasKey('error', $this->check('laporan.exe', $exe));
    }

    public function testTwelveMegabytesIsRefusedWithTheLimit(): void
    {
        $path = $this->file('%PDF-1.4');
        self::assertSame(['error' => 'Ukuran file melebihi 10 MB.'], $this->check('besar.pdf', $path, 12 * 1048576));
        self::assertSame(['error' => 'Ukuran file melebihi 10 MB.'], FileUpload::check(['name' => 'b.pdf', 'tmp_name' => '', 'size' => 0, 'error' => UPLOAD_ERR_FORM_SIZE], self::EXT, self::MAX));
    }

    public function testOtherRefusals(): void
    {
        self::assertSame(['empty' => true], FileUpload::check(['error' => UPLOAD_ERR_NO_FILE], self::EXT, self::MAX));
        self::assertSame(['error' => 'File gagal diunggah. Coba lagi.'], FileUpload::check(['name' => 'a.pdf', 'error' => UPLOAD_ERR_PARTIAL], self::EXT, self::MAX));
        self::assertSame(['error' => 'File kosong. Pilih file lain.'], $this->check('a.pdf', $this->file('')));
        // Not a file PHP received in this request.
        self::assertSame(['error' => 'File gagal diunggah. Coba lagi.'], FileUpload::check(['name' => 'a.pdf', 'tmp_name' => $this->file('%PDF-1.4'), 'size' => 8, 'error' => UPLOAD_ERR_OK], self::EXT, self::MAX, static fn (): bool => false));
        // SVG and HTML are never accepted, whatever they are called.
        self::assertArrayHasKey('error', $this->check('logo.svg', $this->file('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')));
        self::assertArrayHasKey('error', $this->check('logo.png', $this->file('<svg xmlns="http://www.w3.org/2000/svg"></svg>')));
        // A JPEG header without a picture behind it.
        self::assertArrayHasKey('error', $this->check('foto.jpg', $this->file("\xFF\xD8\xFF\xE0" . str_repeat('x', 40))));
        // A zip that is not a workbook, and CSV with NUL bytes.
        self::assertArrayHasKey('error', $this->check('data.xlsx', $this->file("PK\x03\x04" . str_repeat("\x00", 30))));
        self::assertArrayHasKey('error', $this->check('data.csv', $this->file("a;b\n\x00\x01")));
    }

    public function testWorkbook(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('ZipArchive tidak aktif di PHP ini.');
        }
        $path = $this->file('');
        unlink($path);
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<Types/>');
        $zip->addFromString('xl/workbook.xml', '<workbook/>');
        $zip->close();
        self::assertSame('xlsx', FileUpload::sniff($path));
        self::assertSame(['ext' => 'xlsx', 'mime' => FileUpload::TYPES['xlsx']], $this->check('log.xlsx', $path));
    }

    public function testSizes(): void
    {
        self::assertSame(2097152, FileUpload::iniBytes('2M'));
        self::assertSame(1073741824, FileUpload::iniBytes('1G'));
        self::assertSame('10 MB', FileUpload::megabytes(10485760));
        self::assertSame('0,5 MB', FileUpload::megabytes(524288));
    }
}
