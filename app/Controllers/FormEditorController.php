<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Helpers\DashboardData;
use App\Helpers\Schedule;
use App\Models\Dashboard;
use App\Services\FormDefinition;
use Throwable;

/**
 * "Edit Isi Form": GET /form-editor?id=<slug> (tabs Pertanyaan and Jadwal, drawn by
 * public/assets/js/form-editor.js) and POST /form-editor (JSON {id, form, schedule}, X-CSRF-Token).
 * Saving a dynamic form's questions creates a new form version when they changed (reports keep the version
 * they were filled in); the schedule is saved too. POST /form-editor/pratinjau shows the unsaved questions as
 * the fill-in page in a new tab (nothing is saved).
 */
final class FormEditorController extends Controller
{
    private const ICONS = [
        'align-left', 'text', 'circle-dot', 'square-check-big', 'chevron-down', 'file-up', 'sliders-horizontal', 'hash', 'star',
        'calendar', 'clock', 'copy', 'trash-2', 'image', 'image-plus', 'circle-plus', 'type', 'rows-3', 'arrow-up', 'arrow-down',
        'plus', 'x', 'save', 'list-checks', 'calendar-days', 'info', 'circle-alert', 'lock',
        // The preview draws the fill-in page's icons too (question-ui.js); "Ubah" opens an item.
        'check', 'paperclip', 'triangle-alert', 'pencil', 'eye', 'split',
<<<<<<< HEAD
        'calendar-range', 'calendar-plus', 'calendar-check', 'calendar-off', 'chevron-left', 'chevron-right', 'thermometer', 'refresh-ccw',
=======
        'calendar-range', 'calendar-plus', 'calendar-check', 'chevron-left', 'chevron-right', 'thermometer', 'refresh-ccw',
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
    ];

    public function edit(): void
    {
        $id = (string) ($_GET['id'] ?? '');
        if (preg_match('/^[a-z0-9-]{1,64}$/', $id) !== 1) {
            redirect('/');
            return;
        }
        $data = $this->dashboardData(withQuestions: true);
        if ($data === null) {
            return;
        }
        $form = $this->model()->formBySlug($id);
        $this->render('form-editor/index', [
            'pageTitle' => 'Edit Isi Form',
            'portalPage' => true,
            'backToDashboard' => true,
            'breadcrumbWidth' => 'breadcrumb-editor',
            'formId' => $id,
            'editor' => [
                'saveUrl' => url('/form-editor'),
                'previewUrl' => url('/form-editor/pratinjau'),
                'version' => $form !== null ? (int) $form['version_no'] : null,
            ],
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, self::ICONS),
            'pageScripts' => ['js/dashboard-store.js', 'js/question-ui.js', 'js/form-editor.js'],
        ], 'main');
    }

    /** POST /form-editor/pratinjau {id, form (JSON)}: the fill-in page of the unsaved questions, read only. */
    public function preview(): void
    {
        $id = is_string($_POST['id'] ?? null) ? $_POST['id'] : '';
        $data = preg_match('/^[a-z0-9-]{1,64}$/', $id) === 1 ? $this->dashboardData(withQuestions: true) : null;
        if ($data === null || !isset($data['forms'][$id])) {
            http_response_code(404);
            \App\Core\View::render('errors/design-404', ['pageTitle' => 'Form tidak ditemukan'], 'design');
            return;
        }
        $form = json_decode(is_string($_POST['form'] ?? null) ? $_POST['form'] : '', true);
        $definition = FormDefinition::check(is_array($form) ? $form : null)['definition'];
        $data['forms'][$id] = $definition;
        $this->render('form-dinamis/fill', [
            'pageTitle' => 'Pratinjau ' . ($definition['title'] !== '' ? $definition['title'] : $data['forms'][$id]['title'] ?? ''),
            'portalPage' => true,
            'formId' => $id,
            'preview' => true,
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, ['send', 'star', 'file-up', 'plus', 'check', 'paperclip', 'circle-alert', 'list-checks', 'table-2']),
            'pageScripts' => ['js/dashboard-store.js', 'js/question-ui.js', 'js/form-dinamis.js'],
        ], 'main');
    }

    /** POST /form-editor {id, form?: {title, description, questions, …}, schedule} → {message, state, version}. */
    public function save(): void
    {
        $input = json_decode((string) file_get_contents('php://input'), true);
        $input = is_array($input) ? $input : [];
        $slug = is_string($input['id'] ?? null) ? $input['id'] : '';
        $form = preg_match('/^[a-z0-9-]{1,64}$/', $slug) === 1 ? $this->model()->formBySlug($slug) : null;
        if ($form === null || $form['card_id'] === null) {
            json_response(['message' => 'Form tidak ditemukan. Mungkin sudah dihapus; muat ulang halaman.'], 404);
            return;
        }
        $schedule = is_array($input['schedule'] ?? null) ? $input['schedule'] : [];
        $errors = [];
        foreach (Schedule::problems($schedule) as $message) {
            $errors['schedule'] ??= $message;
        }
        $current = json_decode((string) $form['definition_json'], true) ?: [];
        $before = FormDefinition::check(['title' => $form['title'], 'description' => $form['description']] + $current)['definition'];
        $check = FormDefinition::check($input['form'] ?? null);
        $errors += $check['errors'];
        $after = $check['definition'];
        if ($errors !== []) {
            json_response(['message' => 'Periksa kembali isian yang ditandai.', 'errors' => (object) $errors], 422);
            return;
        }
        $model = $this->model();
        $oldSchedule = $model->schedule((int) $form['id']);
        $newVersion = json_encode($after) !== json_encode($before);
        $scheduleChanged = Schedule::toRow($schedule) !== Schedule::toRow($oldSchedule);
        if (!$newVersion && !$scheduleChanged) {
            json_response(['message' => 'Tidak ada perubahan', 'version' => (int) $form['version_no'], 'state' => DashboardData::clientState(DashboardData::load())]);
            return;
        }
        $pdo = Database::connection();
        $versionNo = (int) $form['version_no'];
        try {
            $pdo->beginTransaction();
            $changes = [];
            if ($newVersion) {
                $versionNo++;
                $definition = array_diff_key($after, ['title' => 1, 'description' => 1]);
                $model->addVersion((int) $form['id'], $versionNo, $after['title'], $after['description'], $definition);
                $changes = Audit::changes(
                    ['title' => $before['title'], 'description' => $before['description'], 'questions' => count(FormDefinition::answerItems($before)) . ' pertanyaan'],
                    ['title' => $after['title'], 'description' => $after['description'], 'questions' => count(FormDefinition::answerItems($after)) . ' pertanyaan'],
                    ['title', 'description', 'questions']
                );
                // A dashboard form's card follows the form title unless it was renamed on its own (Edit Kartu).
                if ($form['card_title'] === $form['title'] && $after['title'] !== $before['title']) {
                    $model->updateCard((int) $form['card_id'], ['title' => mb_substr($after['title'], 0, 60)]);
                }
            }
            if ($scheduleChanged) {
                $model->saveSchedule((int) $form['id'], $schedule);
                $changes[] = ['field' => 'schedule', 'old' => Schedule::label($oldSchedule)['text'] ?? null, 'new' => Schedule::label($schedule)['text'] ?? null];
            }
            Audit::log('update', 'form', (int) $form['id'], ['version' => $versionNo, 'changes' => $changes], pdo: $pdo);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Editor form: ' . $exception->getMessage());
            json_response(['message' => 'Form tidak tersimpan. Coba lagi atau muat ulang halaman.'], 500);
            return;
        }
        json_response([
            'message' => $newVersion ? 'Form tersimpan sebagai versi ' . $versionNo : 'Jadwal tersimpan',
            'version' => $versionNo,
            'state' => DashboardData::clientState(DashboardData::load(true)),
        ]);
    }

    private function model(): Dashboard
    {
        return new Dashboard(Database::connection());
    }
}
