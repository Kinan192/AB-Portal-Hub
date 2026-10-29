<?php
declare(strict_types=1);

use App\Core\Migrator;

// B2: the dashboard from the database. Forms with versions and schedules, dashboard sections and cards,
// entry number counters, and attachments.field (the question an uploaded file belongs to; the
// attachments table itself is created when the database does not have it from milestone M1).
// Data change: the old tables are renamed, never dropped (decision Q7):
//   dashboard_sections, dashboard_cards (milestone M1)  -> m1_dashboard_sections, m1_dashboard_cards
//   forms, form_submissions (first schema.sql)           -> legacy_forms, legacy_form_submissions
// The new tables start empty; `php bin/migrate.php seed` fills the starting dashboard.
// Undo (`php bin/migrate.php down`): the new tables are dropped (everything saved in them since is
// lost) and the old tables get their names back; attachments stays (without the field column).
// Back up first (README, "Backup sebelum migrasi").

return [
    'up' => static function (PDO $pdo): void {
        $options = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        // Old shapes: the M1 cards table has builtin_key, the first forms table has no kind column.
        if (Migrator::hasColumn($pdo, 'dashboard_cards', 'builtin_key') && !Migrator::hasTable($pdo, 'm1_dashboard_cards')) {
            $pdo->exec('RENAME TABLE dashboard_cards TO m1_dashboard_cards, dashboard_sections TO m1_dashboard_sections');
        }
        if (Migrator::hasTable($pdo, 'forms') && !Migrator::hasColumn($pdo, 'forms', 'kind') && !Migrator::hasTable($pdo, 'legacy_forms')) {
            $pdo->exec(Migrator::hasTable($pdo, 'form_submissions')
                ? 'RENAME TABLE form_submissions TO legacy_form_submissions, forms TO legacy_forms'
                : 'RENAME TABLE forms TO legacy_forms');
        }

        // A form is a module page (suhu, cctv, cleardesk: fixed questions) or a dynamic form (question list).
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS forms (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(64) NOT NULL,
                kind ENUM('module','dynamic') NOT NULL,
                module_key ENUM('suhu','cctv','cleardesk') NULL DEFAULT NULL,
                entry_prefix VARCHAR(10) NULL DEFAULT NULL,
                current_version_id BIGINT UNSIGNED NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                delete_reason VARCHAR(500) NULL DEFAULT NULL,
                UNIQUE KEY uq_forms_slug (slug),
                UNIQUE KEY uq_forms_module_key (module_key),
                UNIQUE KEY uq_forms_entry_prefix (entry_prefix),
                CONSTRAINT chk_forms_module CHECK ((kind = 'module') = (module_key IS NOT NULL))
            )" . $options
        );
        // Every change of a form's title, description or questions is a new version; entries keep theirs.
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS form_versions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                form_id BIGINT UNSIGNED NOT NULL,
                version_no INT UNSIGNED NOT NULL,
                title VARCHAR(150) NOT NULL,
                description TEXT NOT NULL,
                definition_json JSON NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_form_versions_no (form_id, version_no),
                CONSTRAINT fk_form_versions_form FOREIGN KEY (form_id) REFERENCES forms (id)
            )' . $options
        );
        if (!Migrator::hasIndex($pdo, 'forms', 'fk_forms_current_version')) {
            $pdo->exec('ALTER TABLE forms ADD CONSTRAINT fk_forms_current_version FOREIGN KEY (current_version_id) REFERENCES form_versions (id)');
        }
        // One schedule per form (app/Helpers/Schedule.php). weekday_mask: bit 0 = Minggu … bit 6 = Sabtu.
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS form_schedules (
                form_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                type ENUM('daily','weekly','monthly','yearly','dates') NOT NULL DEFAULT 'daily',
                weekday_mask TINYINT UNSIGNED NOT NULL DEFAULT 0,
                month_day TINYINT UNSIGNED NULL DEFAULT NULL,
                month_last_day TINYINT(1) NOT NULL DEFAULT 0,
                yearly_month TINYINT UNSIGNED NULL DEFAULT NULL,
                yearly_day TINYINT UNSIGNED NULL DEFAULT NULL,
                start_date DATE NULL DEFAULT NULL,
                end_date DATE NULL DEFAULT NULL,
                deadline_time TIME NULL DEFAULT NULL,
                reminder TINYINT(1) NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_form_schedules_form FOREIGN KEY (form_id) REFERENCES forms (id),
                CONSTRAINT chk_form_schedules_mask CHECK (weekday_mask <= 127),
                CONSTRAINT chk_form_schedules_month_day CHECK (month_day IS NULL OR month_day BETWEEN 1 AND 31),
                CONSTRAINT chk_form_schedules_yearly CHECK ((yearly_month IS NULL AND yearly_day IS NULL) OR (yearly_month BETWEEN 1 AND 12 AND yearly_day BETWEEN 1 AND 31)),
                CONSTRAINT chk_form_schedules_range CHECK (start_date IS NULL OR end_date IS NULL OR end_date >= start_date)
            )" . $options
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS form_schedule_dates (
                form_id BIGINT UNSIGNED NOT NULL,
                due_date DATE NOT NULL,
                PRIMARY KEY (form_id, due_date),
                CONSTRAINT fk_form_schedule_dates_schedule FOREIGN KEY (form_id) REFERENCES form_schedules (form_id) ON DELETE CASCADE
            )' . $options
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS dashboard_sections (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(64) NOT NULL,
                title VARCHAR(32) NOT NULL,
                icon VARCHAR(40) NOT NULL,
                kind ENUM('forms','links') NOT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                UNIQUE KEY uq_dashboard_sections_slug (slug),
                KEY idx_dashboard_sections_sort (sort_order)
            )" . $options
        );
        // Uploaded files. The table came with milestone M1; a database built from schema.sql alone has none yet.
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS attachments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                owner_type VARCHAR(40) NOT NULL,
                owner_id BIGINT UNSIGNED NULL DEFAULT NULL,
                field VARCHAR(64) NULL DEFAULT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size_bytes INT UNSIGNED NOT NULL,
                sha256 CHAR(64) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                UNIQUE KEY uq_attachments_stored_path (stored_path),
                KEY idx_attachments_owner (owner_type, owner_id),
                KEY idx_attachments_owner_field (owner_type, owner_id, field)
            )' . $options
        );
        // A form has at most one card that is not deleted (active_form_id is NULL once deleted).
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS dashboard_cards (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                section_id BIGINT UNSIGNED NOT NULL,
                kind ENUM('form','link') NOT NULL,
                form_id BIGINT UNSIGNED NULL DEFAULT NULL,
                title VARCHAR(60) NOT NULL,
                description VARCHAR(60) NOT NULL DEFAULT '',
                icon VARCHAR(40) NOT NULL,
                color VARCHAR(20) NOT NULL DEFAULT 'teal',
                image_url VARCHAR(500) NULL DEFAULT NULL,
                icon_attachment_id BIGINT UNSIGNED NULL DEFAULT NULL,
                url VARCHAR(500) NULL DEFAULT NULL,
                is_hidden TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                active_form_id BIGINT UNSIGNED AS (IF(deleted_at IS NULL, form_id, NULL)) STORED,
                UNIQUE KEY uq_dashboard_cards_active_form (active_form_id),
                KEY idx_dashboard_cards_section_sort (section_id, sort_order),
                CONSTRAINT fk_cards_section FOREIGN KEY (section_id) REFERENCES dashboard_sections (id),
                CONSTRAINT fk_cards_form FOREIGN KEY (form_id) REFERENCES forms (id),
                CONSTRAINT fk_cards_icon FOREIGN KEY (icon_attachment_id) REFERENCES attachments (id),
                CONSTRAINT chk_cards_kind CHECK ((kind = 'form') = (form_id IS NOT NULL))
            )" . $options
        );

        // Entry numbers per prefix and month (TMP-202610-0001 …), used from B3 on.
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS number_sequences (
                prefix VARCHAR(10) NOT NULL,
                period CHAR(6) NOT NULL,
                last_no INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (prefix, period)
            )' . $options
        );

        if (!Migrator::hasColumn($pdo, 'attachments', 'field')) {
            $pdo->exec('ALTER TABLE attachments ADD COLUMN field VARCHAR(64) NULL DEFAULT NULL AFTER owner_id, ADD KEY idx_attachments_owner_field (owner_type, owner_id, field)');
        }
    },

    'down' => static function (PDO $pdo): void {
        if (Migrator::hasColumn($pdo, 'attachments', 'field')) {
            $pdo->exec('ALTER TABLE attachments DROP INDEX idx_attachments_owner_field, DROP COLUMN field');
        }
        // Only the B2 shapes are dropped (the new forms table has a kind column).
        $pdo->exec('DROP TABLE IF EXISTS number_sequences');
        if (Migrator::hasTable($pdo, 'dashboard_cards') && !Migrator::hasColumn($pdo, 'dashboard_cards', 'builtin_key')) {
            $pdo->exec('DROP TABLE dashboard_cards');
            $pdo->exec('DROP TABLE IF EXISTS dashboard_sections');
        }
        if (Migrator::hasColumn($pdo, 'forms', 'kind')) {
            $pdo->exec('DROP TABLE IF EXISTS form_schedule_dates');
            $pdo->exec('DROP TABLE IF EXISTS form_schedules');
            $pdo->exec('ALTER TABLE forms DROP FOREIGN KEY fk_forms_current_version');
            $pdo->exec('DROP TABLE IF EXISTS form_versions');
            $pdo->exec('DROP TABLE forms');
        }

        if (Migrator::hasTable($pdo, 'm1_dashboard_cards') && !Migrator::hasTable($pdo, 'dashboard_cards')) {
            $pdo->exec('RENAME TABLE m1_dashboard_sections TO dashboard_sections, m1_dashboard_cards TO dashboard_cards');
        }
        if (Migrator::hasTable($pdo, 'legacy_forms') && !Migrator::hasTable($pdo, 'forms')) {
            $pdo->exec(Migrator::hasTable($pdo, 'legacy_form_submissions')
                ? 'RENAME TABLE legacy_forms TO forms, legacy_form_submissions TO form_submissions'
                : 'RENAME TABLE legacy_forms TO forms');
        }
    },
];
