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
        Schema::create('permohonans', function (Blueprint $table) {
            $table->id();

            // request number unique
            $table->string('request_number')->unique();

            // CATATAN: `unit_id` SENGAJA TIDAK dibuat di sini.
            // Tabel `units` baru ada di migrasi 2026_08_12_000100, sedangkan
            // migrasi ini berjalan jauh sebelumnya. Menaruh foreign key ke
            // `units` di sini membuat `migrate:fresh` gagal di MySQL
            // ("Failed to open the referenced table 'units'"). Kolom + FK-nya
            // ditambahkan oleh 2026_10_01_000001 setelah tabel `units` ada.

            // foreign key to users table, requester data
            $table->unsignedBigInteger('requester_id');

            // request details
            $table->timestamp('request_date')->useCurrent();
            $table->string('gasoline_type');
            $table->decimal('bill_amounts', 15, 2); // Pakai decimal untuk uang
            $table->string('status')->default('pending');
            $table->text('requester_notes')->nullable();

            // authorizer details
            $table->unsignedBigInteger('authorizer_id')->nullable();
            $table->timestamp('authorization_date')->nullable();
            $table->text('authorizer_notes')->nullable();

            // foreign key to vehicle_and_tools_consumers table
            $table->unsignedBigInteger('consumerial_tools_id')->nullable(); // Tambah nullable jika perlu

            $table->softDeletes();
            $table->timestamps();

            // Foreign key constraints - taruh di akhir
            $table->foreign('requester_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->foreign('authorizer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->foreign('consumerial_tools_id')
                  ->references('id')
                  ->on('vehicle_and_tools_consumers')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permohonans');
    }
};
