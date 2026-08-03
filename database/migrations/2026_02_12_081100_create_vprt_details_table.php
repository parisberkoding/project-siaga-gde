<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vprt_details', function (Blueprint $table) {
            $table->id();
            $table->string('vprt_detail_code', 60)->unique()->comment('Slug: VPRTD-001-001');

            $table->unsignedBigInteger('vprt_id');
            $table->foreign('vprt_id', 'fk_vprtd_transaction')
                  ->references('id')
                  ->on('vehicle_parts_replacement_transactions')
                  ->onDelete('cascade');

            $table->string('part_name', 100)->comment('Nama parts aktual yang diganti');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->comment('Harga satuan');
            $table->decimal('total_price', 15, 2)->comment('quantity x unit_price');
            $table->timestamps();

            $table->index('vprt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vprt_details');
    }
};
