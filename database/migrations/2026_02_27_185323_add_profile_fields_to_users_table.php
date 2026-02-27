<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->nullable();
            $table->string('timezone_label')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->string('title')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'timezone_label', 'is_admin', 'is_bot', 'is_deleted', 'title']);
        });
    }
};
