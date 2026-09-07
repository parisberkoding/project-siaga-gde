<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- sheet_sync_states ---
        Schema::create('sheet_sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 50)->unique();
            $table->string('cursor')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('last_row_count')->default(0);
            $table->timestamps();
        });

        // --- sheet_sync_rejects ---
        Schema::create('sheet_sync_rejects', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 50);
            $table->string('key')->nullable();
            $table->text('reason');
            $table->longText('payload');
            $table->timestamps();
        });

        // --- legacy_gasoline_types ---
        Schema::create('legacy_gasoline_types', function (Blueprint $table) {
            $table->id();
            $table->string('gasoline_type_id');
            $table->string('gasoline_name')->nullable();
            $table->integer('price_per_liter_rp')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('gasoline_type_id');
        });

        // --- legacy_users ---
        Schema::create('legacy_users', function (Blueprint $table) {
            $table->id('staging_id');
            $table->unsignedInteger('legacy_id');
            $table->string('username')->nullable();
            $table->string('fullname')->nullable();
            $table->string('role')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('legacy_id');
        });

        // --- legacy_vehicles ---
        Schema::create('legacy_vehicles', function (Blueprint $table) {
            $table->id('staging_id');
            $table->unsignedInteger('legacy_id');
            $table->string('plat_nomer')->nullable();
            $table->string('merk')->nullable();
            $table->string('jenis')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('legacy_id');
        });

        // --- legacy_permohonans ---
        Schema::create('legacy_permohonans', function (Blueprint $table) {
            $table->id();
            $table->string('request_id');
            $table->unsignedInteger('driver_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('vehicle_id')->nullable();
            $table->integer('km_terakhir')->nullable();
            $table->string('gasoline_type')->nullable();
            $table->integer('bill_payments')->nullable();
            $table->timestamp('request_at')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('approve_at')->nullable();
            $table->unsignedInteger('authorizer_id')->nullable();
            $table->text('authorizer_comment')->nullable();
            $table->string('voucher_code')->nullable();
            $table->text('requester_note')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('request_id');
        });

        // --- legacy_transaction_proofs ---
        Schema::create('legacy_transaction_proofs', function (Blueprint $table) {
            $table->id();
            $table->string('proof_id');
            $table->string('request_id')->nullable();
            $table->string('voucher_code')->nullable();
            $table->integer('km_terakhir')->nullable();
            $table->text('foto_km_terakhir')->nullable();
            $table->text('foto_struk_bbm_pertamina')->nullable();
            $table->timestamp('proof_created_at')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('proof_id');
        });

        // --- legacy_vehicle_healths ---
        Schema::create('legacy_vehicle_healths', function (Blueprint $table) {
            $table->id('staging_id');
            $table->unsignedInteger('legacy_id');
            $table->unsignedInteger('vehicle_id')->nullable();
            $table->string('last_transaction_proof_odoo')->nullable();
            $table->timestamp('ltpo_date')->nullable();
            $table->string('lates_transaction_proof_id')->nullable();
            $table->timestamp('ltp_date')->nullable();
            $table->integer('sum_of_km')->nullable();
            $table->integer('calc_odoo')->nullable();
            $table->date('service_date_schedule')->nullable();
            $table->date('determine_service_date')->nullable();
            $table->string('pic_services')->nullable();
            $table->text('service_notes')->nullable();
            $table->text('service_invoice_picture')->nullable();
            $table->string('service_location')->nullable();
            $table->string('service_location_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_vehicle_healths');
        Schema::dropIfExists('legacy_transaction_proofs');
        Schema::dropIfExists('legacy_permohonans');
        Schema::dropIfExists('legacy_vehicles');
        Schema::dropIfExists('legacy_users');
        Schema::dropIfExists('legacy_gasoline_types');
        Schema::dropIfExists('sheet_sync_rejects');
        Schema::dropIfExists('sheet_sync_states');
    }
};