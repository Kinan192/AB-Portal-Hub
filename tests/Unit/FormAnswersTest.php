<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FormAnswers;
use App\Services\FormDefinition;
use PHPUnit\Framework\TestCase;

/** The server checks of a form's answers (showIf / requiredWhen included) and how answers are shown. */
final class FormAnswersTest extends TestCase
{
    private static function items(array $questions): array
    {
        return FormDefinition::answerItems(FormDefinition::check(['title' => 'Uji', 'questions' => $questions])['definition']);
    }

    private static function check(array $items, array $input, array $files = [], array $existing = []): array
    {
        return FormAnswers::check($items, $input, $files, $existing, static fn (): bool => true);
    }

    private static function userForm(): array
    {
        return self::items([
            ['id' => 'username', 'type' => 'short', 'title' => 'Username', 'required' => true],
            ['id' => 'sesuai', 'type' => 'radio', 'title' => 'Sesuai?', 'required' => true, 'options' => [['value' => 'ya', 'label' => 'Ya'], ['value' => 'tidak', 'label' => 'Tidak', 'tone' => 'red']]],
            ['id' => 'alasan', 'type' => 'select', 'title' => 'Alasan', 'required' => true, 'showIf' => ['q' => 'sesuai', 'value' => 'tidak'], 'options' => [['value' => 'resign', 'label' => 'Resign'], ['value' => 'lainnya', 'label' => 'Lainnya']]],
            ['id' => 'catatan', 'type' => 'paragraph', 'title' => 'Catatan', 'showIf' => ['q' => 'sesuai', 'value' => 'tidak'], 'requiredWhen' => [['q' => 'alasan', 'value' => 'lainnya']]],
            ['id' => 'konfirmasi', 'type' => 'confirm', 'title' => 'Saya menyatakan benar.', 'required' => true],
        ]);
    }

    public function testShowIfAndRequiredWhenOnTheServer(): void
    {
        $items = self::userForm();
        $base = ['username' => 'dr.andi', 'sesuai' => 'ya', 'konfirmasi' => '1'];
        // Hidden questions are neither checked nor stored, even when sent.
        $r = self::check($items, $base + ['alasan' => 'resign', 'catatan' => 'x']);
        self::assertSame([], $r['errors']);
        self::assertSame(['username' => 'dr.andi', 'sesuai' => 'ya', 'konfirmasi' => 'Ya'], $r['answers']);
        $r = self::check($items, ['sesuai' => 'tidak'] + $base);
        self::assertSame('Pilih salah satu jawaban.', $r['errors']['alasan']);
        self::assertArrayNotHasKey('catatan', $r['errors']);
        $r = self::check($items, ['sesuai' => 'tidak', 'alasan' => 'lainnya'] + $base);
        self::assertSame('Pertanyaan ini wajib diisi.', $r['errors']['catatan']);
        $r = self::check($items, ['sesuai' => 'tidak', 'alasan' => 'lainnya', 'catatan' => "Akun dipakai\r\nbersama"] + $base);
        self::assertSame([], $r['errors']);
        self::assertSame("Akun dipakai\nbersama", $r['answers']['catatan']);
        self::assertTrue(FormAnswers::attention($items, $r['answers'])); // "Tidak" is marked red
        // Unknown option values and missing required answers are refused.
        $r = self::check($items, ['sesuai' => 'mungkin', 'username' => '']);
        self::assertSame(['Pertanyaan ini wajib diisi.', 'Pilih salah satu jawaban.', 'Centang pernyataan untuk mengirim.'],
            [$r['errors']['username'], $r['errors']['sesuai'], $r['errors']['konfirmasi']]);
    }

    public function testEveryAnswerType(): void
    {
        $items = self::items([
            ['id' => 'nama', 'type' => 'short', 'title' => 'Nama', 'maxlength' => 10],
            ['id' => 'pilih', 'type' => 'checkbox', 'title' => 'Pilih', 'options' => ['A', 'B'], 'other' => true],
            ['id' => 'angka', 'type' => 'number', 'title' => 'Angka', 'min' => 0, 'max' => 10],
            ['id' => 'bulat', 'type' => 'number', 'title' => 'Bulat', 'integer' => true],
            ['id' => 'tgl', 'type' => 'date', 'title' => 'Tanggal'],
            ['id' => 'jam', 'type' => 'time', 'title' => 'Jam'],
            ['id' => 'skala', 'type' => 'scale', 'title' => 'Skala', 'min' => 1, 'max' => 5],
            ['id' => 'bintang', 'type' => 'rating', 'title' => 'Rating', 'max' => 5],
        ]);
        $r = self::check($items, ['nama' => ' Andi ', 'pilih' => ['B', '__other'], 'pilih_lainnya' => 'C', 'angka' => '2,5', 'bulat' => '3', 'tgl' => '2026-10-27', 'jam' => '08:15', 'skala' => '4', 'bintang' => '5']);
        self::assertSame([], $r['errors']);
        self::assertSame(['nama' => 'Andi', 'pilih' => ['B', 'C'], 'angka' => 2.5, 'bulat' => 3, 'tgl' => '2026-10-27', 'jam' => '08:15', 'skala' => 4, 'bintang' => 5], $r['answers']);
        $r = self::check($items, ['nama' => str_repeat('a', 11), 'pilih' => ['Z'], 'angka' => '11', 'bulat' => '2.5', 'tgl' => '2026-02-30', 'jam' => '25:00', 'skala' => '9', 'bintang' => '0']);
        self::assertSame(['nama', 'pilih', 'angka', 'bulat', 'tgl', 'jam', 'skala', 'bintang'], array_keys($r['errors']));
        $byId = array_column($items, null, 'id');
        self::assertSame(['2,5', '3', '27 Okt 2026', '5 dari 5'], [FormAnswers::display($byId['angka'], 2.5)['text'], FormAnswers::display($byId['bulat'], 3)['text'], FormAnswers::display($byId['tgl'], '2026-10-27')['text'], FormAnswers::display($byId['bintang'], 5)['text']]);
    }

    public function testFiles(): void
    {
        $items = [['id' => 'bukti', 'type' => 'file', 'title' => 'Bukti', 'required' => true, 'accept' => '.pdf', 'maxMb' => 1]];
        self::assertSame('Unggah berkas bukti.', self::check($items, [])['errors']['bukti']);
        $pdf = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($pdf, "%PDF-1.4\n%%EOF\n");
        $upload = ['name' => 'log.pdf', 'tmp_name' => $pdf, 'size' => filesize($pdf), 'error' => UPLOAD_ERR_OK];
        $r = self::check($items, [], ['bukti' => [$upload]]);
        self::assertSame([], $r['errors']);
        self::assertSame('log.pdf', $r['uploads']['bukti'][0]['name']);
        $existing = ['bukti' => [['id' => 7, 'name' => 'lama.pdf']]];
        $r = self::check($items, [], [], $existing);
        self::assertSame([[], [['id' => 7, 'name' => 'lama.pdf']], []], [$r['errors'], $r['answers']['bukti'], $r['removed']]);
        self::assertSame('Unggah berkas bukti.', self::check($items, ['bukti_hapus' => ['7']], [], $existing)['errors']['bukti']);
        $r = self::check($items, [], ['bukti' => [$upload]], $existing);
        self::assertSame([[], [7]], [$r['errors'], $r['removed']]);
        $exe = tempnam(sys_get_temp_dir(), 'exe');
        file_put_contents($exe, "MZ\x90\x00 This program cannot be run in DOS mode.");
        self::assertSame('Format file tidak didukung. Gunakan PDF.', self::check($items, [], ['bukti' => [['name' => 'virus.pdf', 'tmp_name' => $exe, 'size' => filesize($exe), 'error' => UPLOAD_ERR_OK]]])['errors']['bukti']);
        @unlink($pdf);
        @unlink($exe);
    }
}
