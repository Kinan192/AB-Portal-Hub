<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/** Entry numbers <prefix>-<YYYYMM>-<NNNN> (PRD FR-F4) from number_sequences, one counter per prefix and month. */
final class NumberSequence
{
    /** The next number for the month of $date (YYYY-MM-…); the counter never goes back, so a number is never reused. */
    public static function next(PDO $pdo, string $prefix, string $date): string
    {
        $period = str_replace('-', '', substr($date, 0, 7));
        $pdo->prepare(
            'INSERT INTO number_sequences (prefix, period, last_no) VALUES (?, ?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_no = LAST_INSERT_ID(last_no + 1)'
        )->execute([$prefix, $period]);
        $number = (int) $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        return sprintf('%s-%s-%04d', $prefix, $period, $number);
    }
}
