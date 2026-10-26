<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_booking_requests', function (Blueprint $table) {
            $table->id();

            // User yang mengajukan perjalanan (role: manager, non_ga_staff)
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Tanggal yang diinginkan untuk perjalanan
            $table->date('requested_date');

            // Jam keberangkatan yang diinginkan (opsional)
            $table->time('desired_departure_time')->nullable();

            // Tujuan — enum sama dengan trip_passengers untuk konsistensi
            $table->enum('destination_type', ['PMU', 'Patuha', 'Basecamp', 'Direct_Use', 'Other']);
            $table->string('destination_label', 150)->nullable(); // diisi jika destination_type = Other

            // Agenda / keperluan perjalanan
            $table->string('agenda', 255)->nullable();

            // Pilihan driver yang diinginkan (opsional, tidak mengunci driver lain)
            $table->unsignedBigInteger('desired_driver_id')->nullable();
            $table->foreign('desired_driver_id')->references('id')->on('users')->onDelete('set null');

            // Nomor kursi — diisi saat driver menerima atau saat startTrip
            $table->unsignedTinyInteger('seat_number')->nullable();

            // Lifecycle status:
            //   pending          → baru diajukan, menunggu driver
            //   confirmed_by_user → user konfirmasi H-1 "masih jadi berangkat"
            //   accepted         → driver sudah menerima request ini
            //   converted        → sudah dikonversi menjadi TripPassenger saat startTrip
            //   cancelled        → dibatalkan oleh user (manual atau H-1 tidak jadi)
            //   rejected         → driver menolak / kendaraan penuh saat startTrip
            $table->enum('status', [
                'pending',
                'confirmed_by_user',
                'accepted',
                'converted',
                'cancelled',
                'rejected',
            ])->default('pending');

            // Driver schedule yang terhubung (diisi saat driver accept)
            $table->unsignedBigInteger('driver_schedule_id')->nullable();
            $table->foreign('driver_schedule_id')->references('id')->on('driver_schedules')->onDelete('set null');

            // TripPassenger yang dibuat dari request ini (diisi saat converted)
            $table->unsignedBigInteger('trip_passenger_id')->nullable();
            $table->foreign('trip_passenger_id')->references('id')->on('trip_passengers')->onDelete('set null');

            // Audit timestamps
            $table->timestamp('confirmed_at')->nullable();  // saat user konfirmasi H-1
            $table->timestamp('accepted_at')->nullable();   // saat driver accept
            $table->timestamp('converted_at')->nullable();  // saat dikonversi ke TripPassenger
            $table->timestamp('cancelled_at')->nullable();  // saat dibatalkan

            // Satu user hanya boleh punya satu request per tanggal
            $table->unique(['user_id', 'requested_date']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_booking_requests');
    }
};
