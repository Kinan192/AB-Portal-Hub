<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FormDefinition;
use PHPUnit\Framework\TestCase;

/** What "Edit Isi Form" may save as a form version (only known types and settings, limits). */
final class FormDefinitionTest extends TestCase
{
    private static function form(array $questions, array $extra = []): array
    {
        return ['title' => 'Form Uji', 'description' => 'Keterangan', 'questions' => $questions] + $extra;
    }

    public function testRefusedItems(): void
    {
        $cases = [
            'unknown type' => [['id' => 'q-1', 'type' => 'script', 'title' => 'X'], 'Item 1: tipe pertanyaan tidak dikenal.'],
            'bad id' => [['id' => 'q 1"', 'type' => 'short', 'title' => 'X'], 'Item 1: kode item tidak valid atau ganda. Muat ulang editor.'],
            'empty title' => [['id' => 'q-1', 'type' => 'short', 'title' => '   '], 'Item 1: isi teks pertanyaan, judul, atau bagian.'],
            'choice without options' => [['id' => 'q-1', 'type' => 'radio', 'title' => 'X', 'options' => ['  ', '']], 'Item 1: pertanyaan pilihan perlu minimal satu opsi.'],
            'javascript image' => [['id' => 'q-1', 'type' => 'short', 'title' => 'X', 'image' => 'javascript:alert(1)'], 'Item 1: URL gambar harus diawali http:// atau https://.'],
            'image block without URL' => [['id' => 'i-1', 'type' => 'image', 'title' => ''], 'Item 1: URL gambar harus diawali http:// atau https://.'],
            'removed special type' => [['id' => 'g-1', 'type' => 'grid', 'title' => 'X'], 'Item 1: tipe pertanyaan tidak dikenal.'],
        ];
        foreach ($cases as $name => [$item, $message]) {
            self::assertSame($message, FormDefinition::check(self::form([$item]))['errors']['questions'] ?? null, $name);
        }
        $r = FormDefinition::check(self::form([['id' => 'q-1', 'type' => 'short', 'title' => 'A'], ['id' => 'q-1', 'type' => 'short', 'title' => 'B']]));
        self::assertSame('Item 2: kode item tidak valid atau ganda. Muat ulang editor.', $r['errors']['questions']);
        self::assertSame('Isi judul form.', FormDefinition::check(['title' => '  ', 'questions' => []])['errors']['title']);
        self::assertSame('Judul form maksimal 150 karakter.', FormDefinition::check(['title' => str_repeat('a', 151), 'questions' => []])['errors']['title']);
        self::assertSame('Maksimal 200 item per form.', FormDefinition::check(self::form(array_map(static fn (int $i): array => ['id' => 'q-' . $i, 'type' => 'short', 'title' => 'Q' . $i], range(1, 201))))['errors']['questions']);
        self::assertSame('Isi judul form.', FormDefinition::check('bukan form')['errors']['title']);
    }

    public function testCleaning(): void
    {
        $r = FormDefinition::check(self::form([
            ['id' => 'q-1', 'type' => 'short', 'title' => "  Nama\x07 lokasi  ", 'help' => 'Bantuan', 'required' => true, 'onclick' => 'alert(1)', 'maxlength' => 99999, 'format' => 'sql'],
            ['id' => 'q-2', 'type' => 'radio', 'title' => 'Pilih', 'options' => ['A', 'A', ' B ', ['value' => 'c', 'label' => 'C', 'tone' => 'green', 'style' => 'x']], 'other' => true, 'default' => 'Z'],
            ['id' => 'q-3', 'type' => 'file', 'title' => 'Berkas', 'accept' => '.exe,.pdf,.php', 'maxMb' => 50],
            ['id' => 'q-4', 'type' => 'scale', 'title' => 'Skala', 'min' => 7, 'max' => 99],
            ['id' => 'q-5', 'type' => 'select', 'title' => 'Tergantung', 'options' => ['x'], 'showIf' => ['q' => 'q-2', 'value' => 'A']],
            ['id' => 'q-6', 'type' => 'short', 'title' => 'Hilang', 'showIf' => ['q' => 'q-9', 'value' => 'A'], 'requiredWhen' => [['q' => 'q-2', 'value' => 'tidak-ada']]],
            ['id' => 'q-7', 'type' => 'short', 'title' => 'Ke bawah', 'showIf' => ['q' => 'q-8', 'value' => 'x']],
            ['id' => 'q-8', 'type' => 'select', 'title' => 'Sesudahnya', 'options' => ['x']],
        ], ['meta' => [['label' => 'Waktu', 'auto' => 'now'], ['label' => 'Lokasi', 'value' => 'Ruang Server'], ['label' => '']], 'submitLabel' => 'Kirim', 'evil' => 1]));
        self::assertSame([], $r['errors']);
        $q = array_column($r['definition']['questions'], null, 'id');
        self::assertSame(['id' => 'q-1', 'type' => 'short', 'title' => 'Nama lokasi', 'help' => 'Bantuan', 'required' => true], $q['q-1']);
        self::assertSame(['A', 'B', ['value' => 'c', 'label' => 'C']], $q['q-2']['options']);
        self::assertArrayNotHasKey('default', $q['q-2']);
        self::assertSame(['.pdf', 10], [$q['q-3']['accept'], $q['q-3']['maxMb']]);
        self::assertSame([1, 5], [$q['q-4']['min'], $q['q-4']['max']]);
        self::assertSame(['q' => 'q-2', 'value' => 'A'], $q['q-5']['showIf']);
        // A condition on a missing question, on a later question, or on an answer that does not exist is dropped.
        self::assertArrayNotHasKey('showIf', $q['q-6']);
        self::assertArrayNotHasKey('requiredWhen', $q['q-6']);
        self::assertArrayNotHasKey('showIf', $q['q-7']);
        self::assertArrayNotHasKey('meta', $r['definition']);
        self::assertSame('Kirim', $r['definition']['submitLabel']);
        self::assertArrayNotHasKey('evil', $r['definition']);
    }

    public function testAnswerItemsSkipBlocks(): void
    {
        $items = FormDefinition::answerItems(FormDefinition::check(self::form([
            ['id' => 's-1', 'type' => 'section', 'title' => 'Bagian'], ['id' => 'a', 'type' => 'short', 'title' => 'A'],
            ['id' => 't-1', 'type' => 'title', 'title' => 'Judul'], ['id' => 'k', 'type' => 'confirm', 'title' => 'Setuju'],
        ]))['definition']);
        self::assertSame(['a', 'k'], array_column($items, 'id'));
    }

    public function testNoPreselectedAnswerIsKept(): void
    {
        // A valid default is dropped too, so every answer starts empty; option notes and badges are dropped.
        $r = FormDefinition::check(self::form([
            ['id' => 'r', 'type' => 'radio', 'title' => 'Rak', 'options' => [['value' => 'r1', 'label' => 'RAK 1', 'note' => 'Rack utama', 'badge' => 'OK']], 'default' => 'r1', 'badge' => 'x', 'placeholder' => 'panjang'],
            ['id' => 't', 'type' => 'short', 'title' => 'Teks', 'chips' => ['UPS Normal'], 'format' => 'mac'],
            ['id' => 's', 'type' => 'select', 'title' => 'Pilih', 'options' => ['x', 'y'], 'default' => 'y'],
        ]));
        self::assertSame([], $r['errors']);
        foreach ($r['definition']['questions'] as $q) {
            self::assertArrayNotHasKey('default', $q, $q['id']);
        }
        $q = array_column($r['definition']['questions'], null, 'id');
        self::assertSame([['value' => 'r1', 'label' => 'RAK 1']], $q['r']['options']);
        self::assertSame(['id' => 't', 'type' => 'short', 'title' => 'Teks', 'required' => false], $q['t']);
        self::assertArrayNotHasKey('badge', $q['r']);
        self::assertArrayNotHasKey('placeholder', $q['r']);
    }
}
