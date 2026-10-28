<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Schedule;
use PDO;

/**
 * Dashboard sections and cards, and the forms behind the form cards (tables dashboard_sections,
 * dashboard_cards, forms, form_versions, form_schedules, form_schedule_dates).
 *
 * load() returns the shape the views and scripts already use (formerly database/dummy/dashboard.php):
 * a section's 'id' is its slug, a form card's 'id' is its form's slug (the ?id= of the form pages),
 * a link card's 'id' is "link-<row id>"; 'ref' is the row id the JSON API takes (/dashboard/cards/{id}).
 * Nothing is removed for good: sections, cards and forms get deleted_at.
 */
final class Dashboard
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{sections: list<array>, cards: list<array>, forms: array<string, array>} */
    public function load(bool $withQuestions = false): array
    {
        $sections = [];
        $slugs = [];
        foreach ($this->pdo->query('SELECT id, slug, title, icon, kind, is_system FROM dashboard_sections WHERE deleted_at IS NULL ORDER BY sort_order, id') as $row) {
            $sections[] = ['id' => $row['slug'], 'ref' => (int) $row['id'], 'title' => $row['title'], 'icon' => $row['icon'], 'kind' => $row['kind'], 'custom' => !(bool) $row['is_system']];
            $slugs[(int) $row['id']] = $row['slug'];
        }

        $dates = [];
        foreach ($this->pdo->query('SELECT form_id, due_date FROM form_schedule_dates ORDER BY due_date') as $row) {
            $dates[(int) $row['form_id']][] = $row['due_date'];
        }
        // Done when a report is dated today (WIB), with data once any report exists.
        $entryStatus = (new FormEntries($this->pdo))->cardStatus(date('Y-m-d'));
        $cards = [];
        $forms = [];
        $rows = $this->pdo->query(
            'SELECT c.id, c.section_id, c.kind, c.title, c.description, c.icon, c.color, c.image_url, c.url, c.is_hidden,
                    f.id AS form_id, f.slug, v.title AS form_title, v.description AS form_description, v.definition_json,
                    s.form_id AS schedule_form, s.type, s.weekday_mask, s.month_day, s.month_last_day, s.yearly_month, s.yearly_day,
                    s.start_date, s.end_date, s.deadline_time, s.reminder
             FROM dashboard_cards c
             JOIN dashboard_sections sec ON sec.id = c.section_id AND sec.deleted_at IS NULL
             LEFT JOIN forms f ON f.id = c.form_id
             LEFT JOIN form_versions v ON v.id = f.current_version_id
             LEFT JOIN form_schedules s ON s.form_id = f.id
             WHERE c.deleted_at IS NULL
             ORDER BY sec.sort_order, sec.id, c.sort_order, c.id'
        )->fetchAll();
        foreach ($rows as $row) {
            $card = [
                'ref' => (int) $row['id'],
                'section' => $slugs[(int) $row['section_id']],
                'title' => $row['title'],
                'description' => $row['description'],
                'icon' => $row['icon'],
                'color' => $row['color'],
                'image' => $row['image_url'],
                'hidden' => (bool) $row['is_hidden'],
            ];
            if ($row['kind'] === 'link') {
                $cards[] = ['id' => 'link-' . $row['id'], 'kind' => 'link', 'url' => $row['url'], 'rekap' => null] + $card;
                continue;
            }
            $slug = $row['slug'];
            $cards[] = [
                'id' => $slug,
                'form_ref' => (int) $row['form_id'],
                'kind' => 'form',
                'url' => '/form-dinamis?id=' . rawurlencode($slug),
                'rekap' => '/form-dinamis/respons?id=' . rawurlencode($slug),
                'schedule' => Schedule::fromRow($row['schedule_form'] !== null ? $row : ['type' => 'daily'], $dates[(int) $row['form_id']] ?? []),
            ] + ($entryStatus[(int) $row['form_id']] ?? ['done_today' => false, 'has_data' => false]) + $card;
            $definition = json_decode((string) $row['definition_json'], true) ?: [];
            if (!$withQuestions) {
                $definition = [];
            }
            $forms[$slug] = ['title' => $row['form_title'], 'description' => $row['form_description']] + $definition + ($withQuestions ? ['questions' => []] : []);
        }
        return ['sections' => $sections, 'cards' => $cards, 'forms' => $forms];
    }

    public function section(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, slug, title, icon, kind, is_system FROM dashboard_sections WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function sectionBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, slug, title, icon, kind, is_system FROM dashboard_sections WHERE slug = ? AND deleted_at IS NULL');
        $statement->execute([$slug]);
        return $statement->fetch() ?: null;
    }

    /** Titles of the sections not deleted (for "Nama kategori sudah dipakai"). @return list<string> */
    public function sectionTitles(): array
    {
        return $this->pdo->query('SELECT title FROM dashboard_sections WHERE deleted_at IS NULL')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function formsSection(): ?array
    {
        $row = $this->pdo->query("SELECT id, slug, title, icon, kind, is_system FROM dashboard_sections WHERE kind = 'forms' AND deleted_at IS NULL ORDER BY sort_order, id LIMIT 1")->fetch();
        return $row ?: null;
    }

    public function createSection(string $title, string $icon): int
    {
        $sort = (int) $this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM dashboard_sections')->fetchColumn() + 10;
        $this->pdo->prepare("INSERT INTO dashboard_sections (slug, title, icon, kind, is_system, sort_order) VALUES (?, ?, ?, 'links', 0, ?)")
            ->execute([$this->uniqueSlug('dashboard_sections', $title, 'kategori'), $title, $icon, $sort]);
        return (int) $this->pdo->lastInsertId();
    }

    public function activeCardCount(int $sectionId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM dashboard_cards WHERE section_id = ? AND deleted_at IS NULL');
        $statement->execute([$sectionId]);
        return (int) $statement->fetchColumn();
    }

    public function deleteSection(int $id): void
    {
        $this->pdo->prepare('UPDATE dashboard_sections SET deleted_at = UTC_TIMESTAMP() WHERE id = ? AND deleted_at IS NULL')->execute([$id]);
    }

    /** A card that is not deleted, with its form's slug, kind and current version. */
    public function card(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.section_id, c.kind, c.form_id, c.title, c.description, c.icon, c.color, c.image_url, c.url, c.is_hidden, c.sort_order,
                    f.slug, f.kind AS form_kind, f.current_version_id, v.version_no, v.title AS form_title, v.description AS form_description, v.definition_json
             FROM dashboard_cards c
             JOIN dashboard_sections sec ON sec.id = c.section_id AND sec.deleted_at IS NULL
             LEFT JOIN forms f ON f.id = c.form_id
             LEFT JOIN form_versions v ON v.id = f.current_version_id
             WHERE c.id = ? AND c.deleted_at IS NULL'
        );
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    /**
     * A new dynamic form (version 1, entry prefix F<id>), its schedule and its card at the end of
     * $sectionId (or at $sortOrder). @return array{form: int, card: int}
     */
    public function createForm(int $sectionId, array $card, string $formTitle, string $formDescription, array $definition, array $schedule, ?string $slugFrom = null, ?int $sortOrder = null): array
    {
        $this->pdo->prepare("INSERT INTO forms (slug, kind) VALUES (?, 'dynamic')")
            ->execute([$this->uniqueSlug('forms', $slugFrom ?? $card['title'], 'form')]);
        $formId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare('UPDATE forms SET entry_prefix = ? WHERE id = ?')->execute(['F' . $formId, $formId]);
        $this->addVersion($formId, 1, $formTitle, $formDescription, $definition);
        $this->saveSchedule($formId, $schedule);
        $cardId = $this->insertCard($sectionId, 'form', $formId, $card, $sortOrder ?? $this->nextSort($sectionId));
        return ['form' => $formId, 'card' => $cardId];
    }

    public function addVersion(int $formId, int $versionNo, string $title, string $description, array $definition): int
    {
        $this->pdo->prepare('INSERT INTO form_versions (form_id, version_no, title, description, definition_json) VALUES (?, ?, ?, ?, ?)')
            ->execute([$formId, $versionNo, $title, $description, json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]);
        $versionId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare('UPDATE forms SET current_version_id = ? WHERE id = ?')->execute([$versionId, $formId]);
        return $versionId;
    }

    public function saveSchedule(int $formId, array $schedule): void
    {
        [$row, $dates] = Schedule::toRow($schedule);
        $columns = array_keys($row);
        $this->pdo->prepare(
            'INSERT INTO form_schedules (form_id, ' . implode(', ', $columns) . ') VALUES (?' . str_repeat(', ?', count($columns)) . ')
             ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(static fn (string $c): string => "{$c} = VALUES({$c})", $columns))
        )->execute([$formId, ...array_values($row)]);
        $this->pdo->prepare('DELETE FROM form_schedule_dates WHERE form_id = ?')->execute([$formId]);
        $insert = $this->pdo->prepare('INSERT INTO form_schedule_dates (form_id, due_date) VALUES (?, ?)');
        foreach ($dates as $date) {
            $insert->execute([$formId, $date]);
        }
    }

    /** @return array schedule array of a form (app/Helpers/Schedule.php) */
    public function schedule(int $formId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM form_schedules WHERE form_id = ?');
        $statement->execute([$formId]);
        $row = $statement->fetch() ?: ['type' => 'daily'];
        $statement = $this->pdo->prepare('SELECT due_date FROM form_schedule_dates WHERE form_id = ?');
        $statement->execute([$formId]);
        return Schedule::fromRow($row, $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function createLink(int $sectionId, array $card): int
    {
        return $this->insertCard($sectionId, 'link', null, $card, $this->nextSort($sectionId));
    }

    /** $fields: title, description, icon, color, image_url, url (only the keys given are changed). */
    public function updateCard(int $id, array $fields): void
    {
        $allowed = array_intersect_key($fields, array_flip(['title', 'description', 'icon', 'color', 'image_url', 'url']));
        if ($allowed === []) {
            return;
        }
        $this->pdo->prepare('UPDATE dashboard_cards SET ' . implode(', ', array_map(static fn (string $c): string => "{$c} = ?", array_keys($allowed))) . ' WHERE id = ?')
            ->execute([...array_values($allowed), $id]);
    }

    public function setHidden(int $id, bool $hidden): void
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET is_hidden = ? WHERE id = ?')->execute([$hidden ? 1 : 0, $id]);
    }

    /** Puts the card at the end of another section. */
    public function moveCard(int $id, int $sectionId): void
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET section_id = ?, sort_order = ? WHERE id = ?')->execute([$sectionId, $this->nextSort($sectionId), $id]);
    }

    /** Makes room right after $sortOrder in a section and returns the free sort value. */
    public function placeAfter(int $sectionId, int $sortOrder): int
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET sort_order = sort_order + 10 WHERE section_id = ? AND sort_order > ?')->execute([$sectionId, $sortOrder]);
        return $sortOrder + 10;
    }

    public function insertCard(int $sectionId, string $kind, ?int $formId, array $card, int $sortOrder): int
    {
        $this->pdo->prepare(
            'INSERT INTO dashboard_cards (section_id, kind, form_id, title, description, icon, color, image_url, url, is_hidden, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $sectionId, $kind, $formId, $card['title'], $card['description'] ?? '', $card['icon'], $card['color'] ?? 'teal',
            $card['image_url'] ?? null, $card['url'] ?? null, !empty($card['is_hidden']) ? 1 : 0, $sortOrder,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** The card leaves the dashboard; a form card's form is marked deleted too (its entries stay). */
    public function deleteCard(int $id, ?int $formId, string $reason): void
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET deleted_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
        if ($formId !== null) {
            $this->pdo->prepare('UPDATE forms SET deleted_at = UTC_TIMESTAMP(), delete_reason = ? WHERE id = ? AND deleted_at IS NULL')->execute([$reason, $formId]);
        }
    }

    /** A form that is not deleted, with its current version and its card (id and title, when it has one). */
    public function formBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT f.id, f.slug, f.kind, f.module_key, f.entry_prefix, f.current_version_id, v.version_no, v.title, v.description, v.definition_json,
                    c.id AS card_id, c.title AS card_title
             FROM forms f LEFT JOIN form_versions v ON v.id = f.current_version_id
             LEFT JOIN dashboard_cards c ON c.id = (SELECT MIN(x.id) FROM dashboard_cards x WHERE x.form_id = f.id AND x.deleted_at IS NULL)
             WHERE f.slug = ? AND f.deleted_at IS NULL'
        );
        $statement->execute([$slug]);
        return $statement->fetch() ?: null;
    }

    private function nextSort(int $sectionId): int
    {
        $statement = $this->pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM dashboard_cards WHERE section_id = ? AND deleted_at IS NULL');
        $statement->execute([$sectionId]);
        return (int) $statement->fetchColumn() + 10;
    }

    /** "Cek UPS Ruang Server" → cek-ups-ruang-server, then -2, -3 … while taken (deleted rows count too). */
    private function uniqueSlug(string $table, string $text, string $fallback): string
    {
        $base = self::slug($text, $fallback);
        $statement = $this->pdo->prepare("SELECT 1 FROM {$table} WHERE slug = ?");
        for ($n = 1; ; $n++) {
            $slug = $n === 1 ? $base : substr($base, 0, 60) . '-' . $n;
            $statement->execute([$slug]);
            if ($statement->fetchColumn() === false) {
                return $slug;
            }
        }
    }

    /** Lower-case letters, digits and "-" only, at most 56 characters. */
    public static function slug(string $text, string $fallback): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
        $slug = rtrim(substr($slug, 0, 56), '-');
        // "link-…" is the id of a link card in the browser.
        return $slug === '' ? $fallback : (str_starts_with($slug, 'link-') ? $fallback . '-' . $slug : $slug);
    }
}
