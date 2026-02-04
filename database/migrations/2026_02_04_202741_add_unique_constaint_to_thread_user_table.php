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
        Schema::table('thread_user', function (Blueprint $table) {
            $table->unique(['thread_id', 'user_id'], "unique_thread_user");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thread_user', function (Blueprint $table) {
            $table->dropUnique("unique_thread_user");
        });
    }
};
