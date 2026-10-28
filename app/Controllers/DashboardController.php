<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Helpers\DashboardData;
use App\Models\Dashboard;
use App\Services\CardInput;
use PDO;
use Throwable;

/**
 * JSON API of the dashboard ("/"), called by public/assets/js/dashboard.js with fetch(). Every route is
 * a POST with a JSON body and the X-CSRF-Token header (App\Core\VerifyCsrf) from a signed-in session
 * (App\Core\RequireLogin answers 401). Each change is one transaction together with its audit_logs row.
 *
 * Answers: 200 {message, state, id?} with the new dashboard state (sections, cards, forms without
 * questions) · 404 unknown section or card · 422 {message, errors: {field: message}} · 500 on a
 * database error (nothing is saved).
 */
final class DashboardController
{
    private ?Dashboard $dashboard = null;

    /** POST /dashboard/sections {title, icon}: a new link category at the end of the dashboard. */
    public function createSection(): void
    {
        $check = CardInput::category($this->input(), $this->icons(), $this->model()->sectionTitles());
        if ($check['errors'] !== []) {
            $this->invalid($check['errors']);
            return;
        }
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($check): array {
            $id = $dashboard->createSection($check['values']['title'], $check['values']['icon']);
            Audit::log('create', 'dashboard_section', $id, ['title' => $check['values']['title']], pdo: $pdo);
            return ['message' => 'Kategori dibuat', 'id' => $dashboard->section($id)['slug']];
        });
    }

    /** POST /dashboard/sections/{id}/delete: only an added category, and only while it has no cards. */
    public function deleteSection(array $params): void
    {
        $section = $this->model()->section((int) $params['id']);
        if ($section === null) {
            $this->missing('Kategori tidak ditemukan. Muat ulang halaman.');
            return;
        }
        if ((bool) $section['is_system'] || $this->model()->activeCardCount((int) $section['id']) > 0) {
            $this->invalid([], 'Hanya kategori tambahan yang kosong yang dapat dihapus.');
            return;
        }
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($section): array {
            $dashboard->deleteSection((int) $section['id']);
            Audit::log('delete', 'dashboard_section', (int) $section['id'], ['title' => $section['title']], pdo: $pdo);
            return ['message' => 'Kategori dihapus'];
        });
    }

    /**
     * POST /dashboard/forms {title, description, keterangan, icon, color} ("+ Tambah", Form tab): a new
     * dynamic form without questions, scheduled every day from today (WIB), its card after the last form card.
     */
    public function createForm(): void
    {
        $check = CardInput::formCard($this->input(), $this->icons(), array_keys(config('dashboard-cards')['colors']));
        $section = $this->model()->formsSection();
        if ($check['errors'] !== [] || $section === null) {
            $this->invalid($check['errors'], $section === null ? 'Bagian Monitoring Harian tidak ditemukan.' : null);
            return;
        }
        $values = $check['values'];
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($values, $section): array {
            $schedule = ['type' => 'daily', 'start' => date('Y-m-d')];
            $ids = $dashboard->createForm((int) $section['id'], $values, $values['title'], $values['keterangan'], ['questions' => []], $schedule);
            Audit::log('create', 'form', $ids['form'], ['title' => $values['title'], 'card_id' => $ids['card']], pdo: $pdo);
            return ['message' => 'Kartu berhasil ditambahkan', 'id' => $dashboard->card($ids['card'])['slug']];
        });
    }

    /** POST /dashboard/links {section_id, title, description, url, icon_source, icon, icon_url}. */
    public function createLink(): void
    {
        $input = $this->input();
        $check = CardInput::link($input, $this->icons());
        $section = $this->linkSection($input['section_id'] ?? null);
        $errors = $check['errors'] + ($section === null ? ['section_id' => 'Pilih kategori.'] : []);
        if ($errors !== []) {
            $this->invalid($errors);
            return;
        }
        $values = $check['values'];
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($values, $section): array {
            $id = $dashboard->createLink((int) $section['id'], $values + ['color' => 'teal']);
            Audit::log('create', 'dashboard_card', $id, ['title' => $values['title'], 'url' => $values['url'], 'section' => $section['title']], pdo: $pdo);
            return ['message' => 'Kartu berhasil ditambahkan', 'id' => 'link-' . $id];
        });
    }

    /**
     * POST /dashboard/cards/{id}: "Edit Tautan" (fields of createLink; another category puts the card
     * at its end) or "Edit Kartu" of a form card (fields of createForm). A changed title or keterangan
     * is saved as a new form version; a form with its own pages keeps its page title.
     */
    public function updateCard(array $params): void
    {
        $card = $this->model()->card((int) $params['id']);
        if ($card === null) {
            $this->missing();
            return;
        }
        $input = $this->input();
        if ($card['kind'] === 'link') {
            $check = CardInput::link($input, $this->icons());
            $section = $this->linkSection($input['section_id'] ?? null);
            $errors = $check['errors'] + ($section === null ? ['section_id' => 'Pilih kategori.'] : []);
            if ($errors !== []) {
                $this->invalid($errors);
                return;
            }
            $values = $check['values'];
            $this->save(function (Dashboard $dashboard, PDO $pdo) use ($card, $values, $section): array {
                $changes = Audit::changes($card, $values, ['title', 'description', 'url', 'icon', 'image_url']);
                $dashboard->updateCard((int) $card['id'], $values);
                if ((int) $section['id'] !== (int) $card['section_id']) {
                    $dashboard->moveCard((int) $card['id'], (int) $section['id']);
                    $changes[] = ['field' => 'section_id', 'old' => (int) $card['section_id'], 'new' => (int) $section['id']];
                }
                if ($changes !== []) {
                    Audit::log('update', 'dashboard_card', (int) $card['id'], ['changes' => $changes], pdo: $pdo);
                }
                return ['message' => 'Tautan diperbarui', 'id' => 'link-' . $card['id']];
            });
            return;
        }

        $check = CardInput::formCard($input, $this->icons(), array_keys(config('dashboard-cards')['colors']));
        if ($check['errors'] !== []) {
            $this->invalid($check['errors']);
            return;
        }
        $values = $check['values'];
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($card, $values): array {
            $fields = ['title' => $values['title'], 'description' => $values['description'], 'icon' => $values['icon'], 'color' => $values['color']];
            $changes = Audit::changes($card, $fields, array_keys($fields));
            $dashboard->updateCard((int) $card['id'], $fields);
            $formTitle = $values['title'];
            if ($formTitle !== $card['form_title'] || $values['keterangan'] !== $card['form_description']) {
                $versionNo = (int) $card['version_no'] + 1;
                $definition = json_decode((string) $card['definition_json'], true) ?: ['questions' => []];
                $dashboard->addVersion((int) $card['form_id'], $versionNo, $formTitle, $values['keterangan'], $definition);
                $formChanges = Audit::changes(
                    ['title' => $card['form_title'], 'description' => $card['form_description']],
                    ['title' => $formTitle, 'description' => $values['keterangan']],
                    ['title', 'description']
                );
                Audit::log('update', 'form', (int) $card['form_id'], ['version' => $versionNo, 'changes' => $formChanges], pdo: $pdo);
            }
            if ($changes !== []) {
                Audit::log('update', 'dashboard_card', (int) $card['id'], ['changes' => $changes], pdo: $pdo);
            }
            return ['message' => 'Kartu diperbarui', 'id' => $card['slug']];
        });
    }

    /** POST /dashboard/cards/{id}/hide */
    public function hide(array $params): void
    {
        $this->setHidden((int) $params['id'], true);
    }

    /** POST /dashboard/cards/{id}/show */
    public function show(array $params): void
    {
        $this->setHidden((int) $params['id'], false);
    }

    /** POST /dashboard/cards/{id}/move {section_id}: a link card goes to the end of another link category. */
    public function move(array $params): void
    {
        $card = $this->model()->card((int) $params['id']);
        if ($card === null) {
            $this->missing();
            return;
        }
        $section = $this->linkSection($this->input()['section_id'] ?? null);
        if ($card['kind'] !== 'link' || $section === null) {
            $this->invalid(['section_id' => 'Pilih kategori.'], $card['kind'] !== 'link' ? 'Hanya tautan yang dapat dipindah kategori.' : null);
            return;
        }
        if ((int) $section['id'] === (int) $card['section_id']) {
            $this->respond(['message' => 'Kartu sudah di kategori ini', 'id' => 'link-' . $card['id']]);
            return;
        }
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($card, $section): array {
            $dashboard->moveCard((int) $card['id'], (int) $section['id']);
            Audit::log('move', 'dashboard_card', (int) $card['id'], ['changes' => [['field' => 'section_id', 'old' => (int) $card['section_id'], 'new' => (int) $section['id']]]], pdo: $pdo);
            return ['message' => 'Kartu dipindahkan', 'id' => 'link-' . $card['id']];
        });
    }

    /**
     * POST /dashboard/cards/{id}/duplicate: a form card's copy, right after it. The copy is a new dynamic
     * form with the same questions, keterangan and schedule (a module form's copy too), titled "… (Salinan)".
     */
    public function duplicate(array $params): void
    {
        $card = $this->model()->card((int) $params['id']);
        if ($card === null) {
            $this->missing();
            return;
        }
        if ($card['kind'] !== 'form') {
            $this->invalid([], 'Hanya kartu form yang dapat diduplikat.');
            return;
        }
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($card): array {
            $copy = [
                'title' => mb_substr($card['title'] . ' (Salinan)', 0, 60),
                'description' => $card['description'],
                'icon' => $card['icon'],
                'color' => $card['color'],
                'image_url' => $card['image_url'],
            ];
            $definition = json_decode((string) $card['definition_json'], true) ?: ['questions' => []];
            $ids = $dashboard->createForm(
                (int) $card['section_id'],
                $copy,
                mb_substr($card['form_title'] . ' (Salinan)', 0, 150),
                (string) $card['form_description'],
                $definition,
                $dashboard->schedule((int) $card['form_id']),
                $card['slug'] . '-salinan',
                $dashboard->placeAfter((int) $card['section_id'], (int) $card['sort_order']),
            );
            Audit::log('duplicate', 'form', $ids['form'], ['from_form_id' => (int) $card['form_id'], 'card_id' => $ids['card'], 'title' => $copy['title']], pdo: $pdo);
            return ['message' => 'Form diduplikat', 'id' => $dashboard->card($ids['card'])['slug']];
        });
    }

    /**
     * POST /dashboard/cards/{id}/delete {confirm_title}: a form card only with its exact title typed
     * (its form is marked deleted; saved reports stay for audit). Nothing is removed for good.
     */
    public function delete(array $params): void
    {
        $card = $this->model()->card((int) $params['id']);
        if ($card === null) {
            $this->missing();
            return;
        }
        $isForm = $card['kind'] === 'form';
        $typed = $this->input()['confirm_title'] ?? '';
        if ($isForm && (!is_string($typed) || trim($typed) !== trim((string) $card['title']))) {
            $this->invalid(['confirm_title' => 'Ketik judul form persis seperti tertulis.'], 'Judul yang diketik tidak sama. Form tidak dihapus.');
            return;
        }
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($card, $isForm): array {
            $dashboard->deleteCard((int) $card['id'], $isForm ? (int) $card['form_id'] : null, 'Kartu dihapus dari dashboard');
            Audit::log('delete', 'dashboard_card', (int) $card['id'], ['title' => $card['title'], 'form_id' => $isForm ? (int) $card['form_id'] : null], pdo: $pdo);
            return ['message' => $isForm ? 'Form dihapus' : 'Tautan dihapus'];
        });
    }

    private function setHidden(int $id, bool $hidden): void
    {
        $card = $this->model()->card($id);
        if ($card === null) {
            $this->missing();
            return;
        }
        $this->save(function (Dashboard $dashboard, PDO $pdo) use ($card, $hidden): array {
            $dashboard->setHidden((int) $card['id'], $hidden);
            Audit::log($hidden ? 'hide' : 'show', 'dashboard_card', (int) $card['id'], ['title' => $card['title']], pdo: $pdo);
            return ['message' => $hidden ? 'Kartu disembunyikan' : 'Kartu ditampilkan lagi', 'id' => $card['kind'] === 'link' ? 'link-' . $card['id'] : $card['slug']];
        });
    }

    /** Runs $change in one transaction, then answers with its message and the new dashboard state. */
    private function save(callable $change): void
    {
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $answer = $change($this->model(), $pdo);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Dashboard: ' . $exception->getMessage());
            json_response(['message' => 'Perubahan tidak tersimpan. Coba lagi atau muat ulang halaman.'], 500);
            return;
        }
        $this->respond($answer);
    }

    private function respond(array $answer): void
    {
        json_response($answer + ['state' => DashboardData::clientState(DashboardData::load())]);
    }

    private function invalid(array $errors, ?string $message = null): void
    {
        json_response(['message' => $message ?? 'Periksa kembali isian yang ditandai.', 'errors' => (object) $errors], 422);
    }

    private function missing(string $message = 'Kartu tidak ditemukan. Mungkin sudah dihapus; muat ulang halaman.'): void
    {
        json_response(['message' => $message], 404);
    }

    /** The JSON body as an array ([] when it is not a JSON object). */
    private function input(): array
    {
        $data = json_decode((string) file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function linkSection(mixed $slug): ?array
    {
        $section = is_string($slug) && preg_match('/^[a-z0-9-]{1,64}$/', $slug) === 1 ? $this->model()->sectionBySlug($slug) : null;
        return $section !== null && $section['kind'] === 'links' ? $section : null;
    }

    /** @return list<string> icon names of the pickers (config/dashboard-cards.php) */
    private function icons(): array
    {
        $style = config('dashboard-cards');
        return array_values(array_unique([...array_keys($style['icons']), ...$style['category_icons']]));
    }

    private function model(): Dashboard
    {
        return $this->dashboard ??= new Dashboard(Database::connection());
    }
}
