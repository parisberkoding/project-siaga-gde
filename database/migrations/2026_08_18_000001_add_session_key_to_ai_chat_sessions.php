<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * URL sesi AI Assistant memakai `session_key` (token acak), bukan ID
     * numerik — agar ID database tidak bocor di URL yang dibagikan/dilihat.
     * Sesi lama di-backfill dengan token acak agar tautan tetap bisa diakses.
     */
    public function up(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('session_key', 64)->nullable()->unique()->after('title');
        });

        // Backfill: beri token acak ke seluruh sesi yang sudah ada.
        foreach (DB::table('ai_chat_sessions')->pluck('id') as $id) {
            DB::table('ai_chat_sessions')
                ->where('id', $id)
                ->update(['session_key' => Str::random(32)]);
        }

        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('session_key', 64)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropColumn('session_key');
        });
    }
};
