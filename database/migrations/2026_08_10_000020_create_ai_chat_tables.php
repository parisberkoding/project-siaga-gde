<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Session & riwayat chat AI Assistant (Gemini function calling).
     * Masih tahap pengembangan — disimpan per user di database.
     */
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_session_id');
            $table->string('role', 20)->comment('user | assistant');
            $table->longText('content');
            $table->json('metadata')->nullable()->comment('function calls, token usage, dll');
            $table->timestamps();

            $table->foreign('chat_session_id')->references('id')->on('ai_chat_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
        Schema::dropIfExists('ai_chat_sessions');
    }
};
