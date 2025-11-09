<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates DB triggers that keep `users.storage_used`
     * synchronized whenever file sizes change via `file_versions` inserts/updates/deletes
     * or when `files` are updated (size change / soft-delete / restore).
     *
     * Triggers:
     * - file_versions_after_insert
     * - file_versions_after_delete
     * - file_versions_after_update
     * - files_after_update
     *
     * These triggers compute the byte delta and update the owning user's storage_used.
     */
    public function up(): void
    {
        // Drop if exists
        DB::unprepared('DROP TRIGGER IF EXISTS file_versions_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS file_versions_after_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS file_versions_after_update');
        DB::unprepared('DROP TRIGGER IF EXISTS files_after_update');

        // AFTER INSERT ON file_versions -> add file size to owner storage_used
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER file_versions_after_insert
            AFTER INSERT ON file_versions
            FOR EACH ROW
            BEGIN
                DECLARE owner_id BIGINT;
                SELECT user_id INTO owner_id FROM files WHERE id = NEW.file_id LIMIT 1;
                IF owner_id IS NOT NULL THEN
                    UPDATE users
                    SET storage_used = COALESCE(storage_used, 0) + COALESCE(NEW.file_size, 0)
                    WHERE id = owner_id;
                END IF;
            END;
        SQL);

        // AFTER DELETE ON file_versions -> subtract file size from owner storage_used
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER file_versions_after_delete
            AFTER DELETE ON file_versions
            FOR EACH ROW
            BEGIN
                DECLARE owner_id BIGINT;
                SELECT user_id INTO owner_id FROM files WHERE id = OLD.file_id LIMIT 1;
                IF owner_id IS NOT NULL THEN
                    UPDATE users
                    SET storage_used = GREATEST(COALESCE(storage_used, 0) - COALESCE(OLD.file_size, 0), 0)
                    WHERE id = owner_id;
                END IF;
            END;
        SQL);

        // AFTER UPDATE ON file_versions -> apply delta if file_size changed
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER file_versions_after_update
            AFTER UPDATE ON file_versions
            FOR EACH ROW
            BEGIN
                DECLARE owner_id BIGINT;
                DECLARE delta BIGINT;
                SET delta = COALESCE(NEW.file_size, 0) - COALESCE(OLD.file_size, 0);
                IF delta <> 0 THEN
                    SELECT user_id INTO owner_id FROM files WHERE id = NEW.file_id LIMIT 1;
                    IF owner_id IS NOT NULL THEN
                        UPDATE users
                        SET storage_used = GREATEST(COALESCE(storage_used, 0) + delta, 0)
                        WHERE id = owner_id;
                    END IF;
                END IF;
            END;
        SQL);

        // AFTER UPDATE ON files -> handle size changes and soft-delete/restore
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER files_after_update
            AFTER UPDATE ON files
            FOR EACH ROW
            BEGIN
                DECLARE delta BIGINT DEFAULT 0;
                -- If file was soft-deleted
                IF (OLD.is_deleted = 0 AND NEW.is_deleted = 1) THEN
                    SET delta = -COALESCE(OLD.file_size, 0);
                -- If file was restored
                ELSEIF (OLD.is_deleted = 1 AND NEW.is_deleted = 0) THEN
                    SET delta = COALESCE(NEW.file_size, 0);
                -- If file size changed while not deleted
                ELSEIF (OLD.is_deleted = 0 AND NEW.is_deleted = 0 AND COALESCE(NEW.file_size,0) <> COALESCE(OLD.file_size,0)) THEN
                    SET delta = COALESCE(NEW.file_size,0) - COALESCE(OLD.file_size,0);
                END IF;

                IF delta <> 0 THEN
                    UPDATE users
                    SET storage_used = GREATEST(COALESCE(storage_used, 0) + delta, 0)
                    WHERE id = NEW.user_id;
                END IF;
            END;
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS file_versions_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS file_versions_after_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS file_versions_after_update');
        DB::unprepared('DROP TRIGGER IF EXISTS files_after_update');
    }
};
