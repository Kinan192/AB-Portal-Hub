<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Attention;
use PHPUnit\Framework\TestCase;

final class AttentionTest extends TestCase
{
    public function testReasonsNameEveryAnswerWithARedOption(): void
    {
        $definition = ['questions' => [
            ['id' => 'title', 'type' => 'section', 'title' => 'Bagian'],
            ['id' => 'cpu', 'type' => 'radio', 'title' => 'Kondisi CPU', 'options' => [['value' => 'ok', 'label' => 'Baik'], ['value' => 'bad', 'label' => 'Tidak baik', 'tone' => 'red']]],
            ['id' => 'cam', 'type' => 'checkbox', 'title' => 'Masalah', 'options' => ['Offline', ['value' => 'Buram', 'label' => 'Buram', 'tone' => 'red']]],
            ['id' => 'note', 'type' => 'short', 'title' => 'Catatan'],
            ['id' => 'ok', 'type' => 'confirm', 'title' => 'Saya menyatakan'],
        ]];
        $answers = ['cpu' => 'bad', 'cam' => ['Offline', 'Buram'], 'note' => 'teks', 'ok' => true];
        self::assertSame(['Kondisi CPU: Tidak baik', 'Masalah: Offline, Buram'], Attention::reasons($definition, $answers));
        self::assertSame([], Attention::reasons($definition, ['cpu' => 'ok', 'cam' => ['Offline']]));
        self::assertSame([], Attention::reasons([], $answers), 'a version without questions names nothing');
    }
}
