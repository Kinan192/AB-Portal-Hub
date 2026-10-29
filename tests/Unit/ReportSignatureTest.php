<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReportSignature;
use PHPUnit\Framework\TestCase;

final class ReportSignatureTest extends TestCase
{
    public function testSavedTextsGoOverTheDefaults(): void
    {
        $values = ReportSignature::fromJson('{"right_role":"Manajer IT","left_title":"","other":"x"}');
        self::assertSame('Manajer IT', $values['right_role']);
        self::assertSame('', $values['left_title'], 'a text cleared on purpose stays empty');
        self::assertSame('Diketahui oleh,', $values['right_title']);
        self::assertSame(ReportSignature::FIELDS, array_keys($values));
        self::assertSame(ReportSignature::defaults(), ReportSignature::fromJson(null));
        self::assertSame(ReportSignature::defaults(), ReportSignature::fromJson('not json'));
    }

    public function testCheckMakesOneLineTextsAndLimitsLength(): void
    {
        $check = ReportSignature::check(['left_title' => "  Dibuat \n oleh, ", 'right_name' => ['x'], 'left_role' => str_repeat('a', 81)]);
        self::assertSame('Dibuat oleh,', $check['values']['left_title']);
        self::assertSame('', $check['values']['right_name']);
        self::assertSame(['left_role'], array_keys($check['errors']));
        self::assertSame(ReportSignature::FIELDS, array_keys($check['values']));
    }
}
