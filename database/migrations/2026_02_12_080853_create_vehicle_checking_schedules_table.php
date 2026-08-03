<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checking_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_code', 60)->unique()->comment('Slug: SCH-D1234AB-DAILY');

            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('cascade');

            $table->enum('check_frequency', ['daily', 'weekly', 'monthly', 'body'])->comment('Jenis jadwal');

            $table->unsignedBigInteger('assigned_dispatcher_id')->nullable()->comment('Default PIC dispatcher (hybrid)');
            $table->foreign('assigned_dispatcher_id', 'fk_vcs_dispatcher')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->datetime('last_checked_at')->nullable()->comment('Terakhir dicek kapan');

            $table->unsignedBigInteger('last_checked_by')->nullable()->comment('Dispatcher terakhir yang cek');
            $table->foreign('last_checked_by', 'fk_vcs_last_checker')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->date('next_due_date')->nullable()->comment('Kapan harus dicek lagi');
            $table->enum('status', ['pending', 'due', 'overdue', 'completed'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique: 1 schedule per kendaraan per frekuensi
            $table->unique(['vehicle_id', 'check_frequency'], 'uk_vehicle_frequency');
            $table->index('status');
            $table->index('next_due_date');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checking_schedules');
    }
};
