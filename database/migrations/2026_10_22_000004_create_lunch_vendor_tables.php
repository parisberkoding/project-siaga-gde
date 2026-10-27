<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor catering + invoice + pembayaran.
 *
 * Vendor boleh terikat ke unit saja (melayani semua site di unit itu) atau
 * ke satu site tertentu. Invoice dihitung dari total porsi distribusi pada
 * periode tertentu, lalu nominalnya masih boleh dikoreksi manual sebelum
 * disetujui — sesuai kebutuhan GA yang kadang menerima tagihan dengan
 * penyesuaian (transport, retensi, dsb).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lunch_vendors')) {
            Schema::create('lunch_vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
                $table->string('name', 150);
                $table->string('contact_person', 150)->nullable();
                $table->string('phone', 50)->nullable();
                $table->decimal('price_per_portion', 14, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['unit_id', 'is_active']);
                $table->index(['site_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('lunch_vendor_invoices')) {
            Schema::create('lunch_vendor_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('lunch_vendors')->restrictOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();

                $table->date('period_start');
                $table->date('period_end');

                $table->unsignedInteger('total_portion')->default(0);
                $table->decimal('price_per_portion', 14, 2)->nullable();
                $table->decimal('calculated_amount', 16, 2)->default(0);
                $table->decimal('total_amount', 16, 2)->default(0);

                $table->string('status', 20)->default('draft');
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();

                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->unique(['vendor_id', 'site_id', 'period_start', 'period_end'], 'lunch_invoice_unique_period');
                $table->index(['status', 'period_end']);
                $table->index(['unit_id', 'period_start']);
            });
        }

        if (! Schema::hasTable('lunch_vendor_payments')) {
            Schema::create('lunch_vendor_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lunch_vendor_invoice_id')->constrained('lunch_vendor_invoices')->cascadeOnDelete();
                $table->decimal('amount', 16, 2);
                $table->date('paid_at');
                $table->string('method', 30)->default('transfer');
                $table->string('reference', 100)->nullable();
                $table->string('proof_path')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['lunch_vendor_invoice_id', 'paid_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_vendor_payments');
        Schema::dropIfExists('lunch_vendor_invoices');
        Schema::dropIfExists('lunch_vendors');
    }
};
