<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Helpers\Format;
use App\Models\AttentionList;
use Throwable;

/**
 * "Perlu perhatian" (PRD FR-P6): reports with an answer marked red in the editor. The header bell lists the
 * ones of the last DAYS days (older ones leave the notification); the "Perlu perhatian" page lists them all.
 * Each item names the marked answers ("Kondisi CPU: Tidak baik") and opens the report on its recap page.
 */
final class Attention
{
    public const DAYS = 7;
    public const BELL_LIMIT = 8;

    /** @var array{total: int, unread: int, items: list<array>}|null */
    private static ?array $bell = null;

    /**
     * The header bell of the signed-in account, read once per request; empty when the database is down. The
     * badge counts the reports this account has not seen yet (opened from the bell or its detail on a recap).
     */
    public static function notifications(): array
    {
        if (self::$bell !== null) {
            return self::$bell;
        }
        self::$bell = ['total' => 0, 'unread' => 0, 'items' => []];
        $userId = self::userId();
        if ($userId === null) {
            return self::$bell;
        }
        try {
            $recent = (new AttentionList(Database::connection()))->recent($userId, self::DAYS, self::BELL_LIMIT);
            self::$bell = ['total' => $recent['total'], 'unread' => $recent['unread'], 'items' => array_map([self::class, 'item'], $recent['rows'])];
        } catch (Throwable $exception) {
            error_log('Notifikasi perlu perhatian: ' . $exception->getMessage());
        }
        return self::$bell;
    }

    /** The signed-in account, or null. */
    public static function userId(): ?int
    {
        $id = Auth::check() ? (int) (Auth::user()['id'] ?? 0) : 0;
        return $id > 0 ? $id : null;
    }

    /** One report row of App\Models\AttentionList as shown in the bell and on the page. */
    public static function item(array $row): array
    {
        $definition = json_decode((string) ($row['definition_json'] ?? ''), true);
        $answers = json_decode((string) ($row['answers_json'] ?? ''), true);
        return [
            'id' => (int) $row['id'],
            'entry_no' => (string) $row['entry_no'],
            'form' => (string) ($row['form_title'] ?? $row['slug']),
            'when' => Format::dateTime($row['submitted_at']),
            'read' => ($row['read_at'] ?? null) !== null,
            'reasons' => self::reasons(is_array($definition) ? $definition : [], is_array($answers) ? $answers : []),
            'url' => url('/form-dinamis/respons') . '?' . http_build_query(['id' => $row['slug'], 'buka' => (int) $row['id']]),
        ];
    }

    /** "Question: answer" for every answer with a red option. @return list<string> */
    public static function reasons(array $definition, array $answers): array
    {
        $reasons = [];
        foreach (FormDefinition::answerItems($definition) as $q) {
            if (!array_key_exists($q['id'] ?? '', $answers) || $q['type'] === 'confirm') {
                continue;
            }
            $shown = FormAnswers::display($q, $answers[$q['id']]);
            if (in_array($shown['tone'] ?? '', ['warn', 'bad'], true)) {
                $reasons[] = $q['title'] . ': ' . $shown['text'];
            }
        }
        return $reasons;
    }
}
