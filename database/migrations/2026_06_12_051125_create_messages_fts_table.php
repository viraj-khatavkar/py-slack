<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement("CREATE VIRTUAL TABLE IF NOT EXISTS messages_fts USING fts5(content, content='messages', content_rowid='id', tokenize='porter unicode61')");
        DB::statement("INSERT INTO messages_fts(messages_fts) VALUES('rebuild')");

        DB::statement('CREATE TRIGGER IF NOT EXISTS messages_fts_after_insert AFTER INSERT ON messages BEGIN
            INSERT INTO messages_fts(rowid, content) VALUES (new.id, new.content);
        END');

        DB::statement("CREATE TRIGGER IF NOT EXISTS messages_fts_after_delete AFTER DELETE ON messages BEGIN
            INSERT INTO messages_fts(messages_fts, rowid, content) VALUES ('delete', old.id, old.content);
        END");

        DB::statement("CREATE TRIGGER IF NOT EXISTS messages_fts_after_update AFTER UPDATE OF content ON messages BEGIN
            INSERT INTO messages_fts(messages_fts, rowid, content) VALUES ('delete', old.id, old.content);
            INSERT INTO messages_fts(rowid, content) VALUES (new.id, new.content);
        END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS messages_fts_after_insert');
        DB::statement('DROP TRIGGER IF EXISTS messages_fts_after_delete');
        DB::statement('DROP TRIGGER IF EXISTS messages_fts_after_update');
        DB::statement('DROP TABLE IF EXISTS messages_fts');
    }
};
