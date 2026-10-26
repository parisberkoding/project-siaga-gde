<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit pembayaran vendor disimpan eksplisit agar query scope tidak perlu
 * join berlapis (invoice → site → unit) pada setiap request. Nilainya
 * di-backfill dari site.
 */
return new class extends Migration
{
    public function up(): void
    {
        // unit_id sudah dibuat bersama tabel invoice. Migrasi ini hanya
        // memastikan index tambahan untuk pencarian unit + status ada.
        Schema::table('lunch_vendor_invoices', function (Blueprint $table) {
            $table->index(['unit_id', 'status'], 'lunch_invoice_unit_status');
        });
    }

    public function down(): void
    {
        Schema::table('lunch_vendor_invoices', function (Blueprint $table) {
            $table->dropIndex('lunch_invoice_unit_status');
        });
    }
};
