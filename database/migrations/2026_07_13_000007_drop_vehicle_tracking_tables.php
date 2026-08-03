<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop seluruh tabel Vehicle/Passenger Tracking internal (SIAGA trip).
     *
     * Perusahaan sudah memakai GPS Tracker vendor, dan satu-satunya
     * konsumen data trip internal adalah Lunch Tracking lama. Setelah
     * Lunch V2 berdiri sendiri (QR Attendance sebagai ground truth),
     * tabel-tabel ini tidak punya pembaca lagi:
     *
     *  - trip_booking_requests : booking kursi penumpang
     *  - trip_passengers       : manifest penumpang per trip
     *  - trips                 : perjalanan kendaraan internal
     *  - driver_schedules      : jadwal driver untuk trip
     *  - vehicle_specifications: kapasitas kursi — hanya dipakai trip
     *  - pekerja_patuha        : sudah dimigrasikan ke employee_defaults
     *
     * Catatan: tabel trip_validations & vehicle_trip_analyses TIDAK
     * disentuh — meski bernama "trip", keduanya milik modul analisis BBM
     * (berbasis transaction proof), bukan vehicle tracking.
     *
     * Urutan drop mengikuti arah foreign key (child dulu).
     */
    public function up(): void
    {
        Schema::dropIfExists('trip_booking_requests');
        Schema::dropIfExists('trip_passengers');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('driver_schedules');
        Schema::dropIfExists('vehicle_specifications');
        Schema::dropIfExists('pekerja_patuha');
    }

    /**
     * Rollback membuat ulang struktur tabel (tanpa data) persis seperti
     * migration aslinya, agar migrate:rollback tidak macet.
     */
    public function down(): void
    {
        Schema::create('vehicle_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_and_tools_consumer_id')->unique();
            $table->foreign('vehicle_and_tools_consumer_id')
                ->references('id')->on('vehicle_and_tools_consumers')->onDelete('cascade');
            $table->unsignedTinyInteger('passenger_capacity');
            $table->string('fuel_type', 50);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('vehicle_consumer_id');
            $table->foreign('vehicle_consumer_id')
                ->references('id')->on('vehicle_and_tools_consumers')->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected', 'self_assigned'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('schedule_code', 30)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_schedule_id');
            $table->foreign('driver_schedule_id')->references('id')->on('driver_schedules')->onDelete('restrict');
            $table->string('origin', 100);
            $table->enum('destination_type', ['PMU', 'Patuha', 'Basecamp', 'Direct_Use', 'Other']);
            $table->string('destination_label', 150)->nullable();
            $table->timestamp('start_trip_at')->nullable();
            $table->timestamp('end_trip_at')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed'])->default('scheduled');
            $table->string('trip_code', 30)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('trip_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedTinyInteger('seat_number');
            $table->enum('destination_type', ['PMU', 'Patuha', 'Basecamp', 'Direct_Use', 'Other']);
            $table->string('destination_label', 150)->nullable();
            $table->enum('boarding_status', ['waiting', 'onboard', 'completed'])->default('waiting');
            $table->unique(['trip_id', 'user_id']);
            $table->unique(['trip_id', 'seat_number']);
            $table->timestamps();
        });

        Schema::create('trip_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('requested_date');
            $table->time('desired_departure_time')->nullable();
            $table->enum('destination_type', ['PMU', 'Patuha', 'Basecamp', 'Direct_Use', 'Other']);
            $table->string('destination_label', 150)->nullable();
            $table->string('agenda', 255)->nullable();
            $table->unsignedBigInteger('desired_driver_id')->nullable();
            $table->foreign('desired_driver_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedTinyInteger('seat_number')->nullable();
            $table->enum('status', [
                'pending', 'confirmed_by_user', 'accepted', 'converted', 'cancelled', 'rejected',
            ])->default('pending');
            $table->unsignedBigInteger('driver_schedule_id')->nullable();
            $table->foreign('driver_schedule_id')->references('id')->on('driver_schedules')->onDelete('set null');
            $table->unsignedBigInteger('trip_passenger_id')->nullable();
            $table->foreign('trip_passenger_id')->references('id')->on('trip_passengers')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unique(['user_id', 'requested_date']);
            $table->string('booking_code', 30)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('pekerja_patuha', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pekerja', 50)->unique();
            $table->string('nama_lengkap', 150);
            $table->enum('default_location', ['PMU', 'Patuha']);
            $table->timestamps();
        });
    }
};
