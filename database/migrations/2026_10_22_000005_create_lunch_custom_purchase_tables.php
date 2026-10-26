<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom Purchase = pembelian manual untuk hari/tempat tertentu yang
 * menggantikan katering reguler (perayaan, pelayanan, apresiasi pegawai).
 *
 * `replaces_catering` menentukan apakah distribusi katering pada tanggal +
 * site tersebut di-nol-kan. Item pembelian disimpan terpisah agar rincian
 * belanja tetap bisa diaudit tanpa mengubah header.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lunch_custom_purchases')) {
            Schema::create('lunch_custom_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();

                $table->date('purchase_date');
                $table->string('title', 150);
                $table->text('description')->nullable();
                $table->string('vendor_name', 150)->nullable();

                $table->decimal('total_amount', 16, 2)->default(0);
                $table->string('status', 20)->default('draft');
                $table->boolean('replaces_catering')->default(true);
                $table->string('receipt_path')->nullable();

                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->index(['purchase_date', 'site_id']);
                $table->index(['unit_id', 'status']);
            });
        }

        if (! Schema::hasTable('lunch_custom_purchase_items')) {
            Schema::create('lunch_custom_purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lunch_custom_purchase_id')->constrained('lunch_custom_purchases')->cascadeOnDelete();
                $table->string('item_name', 150);
                $table->decimal('qty', 10, 2)->default(1);
                $table->string('unit', 30)->nullable();
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('subtotal', 16, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('lunch_distributions', function (Blueprint $table) {
            if (! Schema::hasColumn('lunch_distributions', 'custom_purchase_id')) {
                $table->foreignId('custom_purchase_id')->nullable()->after('forecast_batch_id')
                    ->constrained('lunch_custom_purchases')->nullOnDelete();
            }
            if (! Schema::hasColumn('lunch_distributions', 'is_custom_purchase')) {
                $table->boolean('is_custom_purchase')->default(false)->after('custom_purchase_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lunch_distributions', function (Blueprint $table) {
            if (Schema::hasColumn('lunch_distributions', 'is_custom_purchase')) {
                $table->dropColumn('is_custom_purchase');
            }
            if (Schema::hasColumn('lunch_distributions', 'custom_purchase_id')) {
                $table->dropConstrainedForeignId('custom_purchase_id');
            }
        });

        Schema::dropIfExists('lunch_custom_purchase_items');
        Schema::dropIfExists('lunch_custom_purchases');
    }
};
