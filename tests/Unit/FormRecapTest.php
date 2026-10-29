<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FormDefinition;
use App\Services\FormRecap;
use PHPUnit\Framework\TestCase;

/** B6: history of a dynamic form; a report keeps the questions of its own version (DoD: v1 reports keep their titles). */
final class FormRecapTest extends TestCase
{
    private const V1 = ['questions' => [
        ['id' => 'sec', 'type' => 'section', 'title' => 'Bagian A'],
        ['id' => 'kondisi', 'type' => 'radio', 'title' => 'Kondisi lama', 'options' => [['value' => 'b', 'label' => 'Baik (v1)'], ['value' => 't', 'label' => 'Rusak (v1)', 'tone' => 'red']]],
        ['id' => 'lama', 'type' => 'short', 'title' => 'Pertanyaan yang dihapus di v2'],
        ['id' => 'bukti', 'type' => 'file', 'title' => 'Bukti'],
        ['id' => 'ok', 'type' => 'confirm', 'title' => 'Saya menyatakan'],
    ]];

    private static function v2Items(): array
    {
        return FormDefinition::answerItems(['questions' => [
            ['id' => 'kondisi', 'type' => 'radio', 'title' => 'Kondisi baru', 'options' => [['value' => 'b', 'label' => 'Baik (v2)'], ['value' => 't', 'label' => 'Rusak (v2)']]],
            ['id' => 'baru', 'type' => 'short', 'title' => 'Pertanyaan baru di v2'],
            ['id' => 'ok', 'type' => 'confirm', 'title' => 'Saya menyatakan'],
        ]]);
    }

    private static function entry(): array
    {
        return ['id' => 5, 'entry_no' => 'HWS-202610-0001', 'submitted_at' => '2026-10-27 01:00:00', 'attention' => 1, 'definition_json' => json_encode(self::V1),
            'answers_json' => json_encode(['kondisi' => 't', 'lama' => 'Jawaban lama', 'bukti' => [['id' => 9, 'name' => 'foto.png']], 'ok' => 'Ya'])];
    }

    public function testFiltersDefaultToEveryPeriod(): void
    {
        $f = FormRecap::filters([]);
        self::assertSame([null, null, null, 'waktu', 'desc', 25], [$f['from'], $f['to'], $f['month'], $f['sort'], $f['dir'], $f['per']]);
        $f = FormRecap::filters(['periode' => '2026-02', 'perhatian' => 'ya', 'urut' => 'x; DROP']);
        self::assertSame(['2026-02-01', '2026-02-28', 'ya', 'waktu'], [$f['from'], $f['to'], $f['attention'], $f['sort']]);
        self::assertSame('Rentang maksimal 366 hari', FormRecap::filters(['dari' => '2025-01-01', 'sampai' => '2026-02-05'])['error']);
        self::assertSame('', FormRecap::filters(['perhatian' => 'mungkin'])['attention']);
    }

    public function testColumnsFollowTheCurrentVersionAnswersTheirOwn(): void
    {
        self::assertSame(['entry_no', 'waktu', 'q:kondisi', 'q:baru'], array_column(FormRecap::columns(self::v2Items()), 'key'));
        $row = FormRecap::row(self::entry(), self::v2Items());
        // The v1 report shows its own option label, not the v2 one.
        self::assertSame(['text' => 'Rusak (v1)', 'chip' => 'Rusak (v1)', 'tone' => 'bad', 'clamp' => true], $row['cells']['q:kondisi']);
        self::assertNull($row['cells']['q:baru']);
        self::assertSame(['27 Okt 2026, 08:00 WIB', 'Perlu perhatian'], [$row['cells']['waktu']['text'], $row['cells']['waktu']['chip']]);
    }

    public function testDetailUsesTheOwnVersion(): void
    {
        $fields = FormRecap::fields(self::V1, json_decode(self::entry()['answers_json'], true));
        self::assertSame(['heading' => 'Bagian A'], $fields[0]);
        self::assertSame(['Kondisi lama', 'Pertanyaan yang dihapus di v2', 'Bukti', 'Pernyataan'], array_column(array_slice($fields, 1), 'label'));
        self::assertSame(['foto.png', '/berkas/9'], [$fields[3]['text'], $fields[3]['link']]);
        self::assertSame('Dicentang', $fields[4]['text']);
    }

    public function testPrefillForChangingAReport(): void
    {
        $items = FormDefinition::answerItems(['questions' => [
            ['id' => 'w', 'type' => 'date', 'title' => 'W'], ['id' => 'n', 'type' => 'number', 'title' => 'N'], ['id' => 'c', 'type' => 'confirm', 'title' => 'C'], ['id' => 'f', 'type' => 'file', 'title' => 'F'],
        ]]);
        self::assertSame(['w' => '2026-10-27', 'n' => '3,25', 'c' => '1'], FormRecap::prefill($items, ['w' => '2026-10-27', 'n' => 3.25, 'c' => 'Ya', 'f' => [['id' => 1, 'name' => 'x']]]));
    }
}
