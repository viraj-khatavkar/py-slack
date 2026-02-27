<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->text('purpose')->nullable();
            $table->text('topic')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->integer('member_count')->default(0);
            $table->timestamp('created_at_slack')->nullable();
            $table->unsignedInteger('message_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'topic', 'creator_id', 'member_count', 'created_at_slack', 'message_count']);
        });
    }
};
