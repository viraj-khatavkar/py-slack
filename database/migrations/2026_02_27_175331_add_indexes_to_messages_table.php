<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('slack_timestamp');
            $table->index(['channel_id', 'parent_id', 'slack_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['slack_timestamp']);
            $table->dropIndex(['channel_id', 'parent_id', 'slack_timestamp']);
        });
    }
};
