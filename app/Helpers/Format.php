<?php
declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use DateTimeZone;

/** Display formats of PRD FR-F12: "15 Okt 2026, 10:18 WIB" and decimals with a comma ("21,5"). */
final class Format
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** "2026-10-01" → "Oktober 2026". */
    public static function monthName(string $ymd): string
    {
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $months[(int) substr($ymd, 5, 2) - 1] . ' ' . substr($ymd, 0, 4);
    }

    public static function decimal(float|string|null $value, int $digits = 1): string
    {
        return $value === null || $value === '' ? '' : number_format((float) $value, $digits, ',', '.');
    }

    /** A UTC "Y-m-d H:i:s" from the database as WIB: "15 Okt 2026, 10:18 WIB". */
    public static function dateTime(?string $utc): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }
        $wib = self::wib($utc);
        return $wib->format('j') . ' ' . self::MONTHS[(int) $wib->format('n') - 1] . ' ' . $wib->format('Y, H:i') . ' WIB';
    }

    /** A UTC "Y-m-d H:i:s" as the WIB time only: "10:18 WIB". */
    public static function time(string $utc): string
    {
        return self::wib($utc)->format('H:i') . ' WIB';
    }

    /** A calendar date "Y-m-d" (already WIB): "15 Okt 2026". */
    public static function date(string $ymd): string
    {
        $date = new DateTimeImmutable($ymd);
        return $date->format('j') . ' ' . self::MONTHS[(int) $date->format('n') - 1] . ' ' . $date->format('Y');
    }

    /** A UTC "Y-m-d H:i:s" as the value of a datetime-local field in WIB: "2026-10-15T10:18". */
    public static function wibInput(string $utc): string
    {
        return self::wib($utc)->format('Y-m-d\TH:i');
    }

    private static function wib(string $utc): DateTimeImmutable
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'));
    }
}
