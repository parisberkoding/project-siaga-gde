<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->dropForeign(['spd_id']);
            $table->dropIndex('struk_items_spd_id_index');
            $table->unsignedBigInteger('spd_id')->nullable()->change();
            $table->foreign('spd_id')->references('id')->on('surat_spds')->onDelete('cascade');
            $table->index('spd_id');

            $table->enum('struk_kategori', ['BBM', 'Tol', 'Parkir', 'Lainnya', 'Tambahan'])->default('Lainnya')->change();
        });
    }

    public function down(): void
    {
        Schema::table('struk_items', function (Blueprint $table) {
            $table->dropForeign(['spd_id']);
            $table->unsignedBigInteger('spd_id')->nullable(false)->change();
            $table->foreign('spd_id')->references('id')->on('surat_spds')->onDelete('cascade');
            $table->enum('struk_kategori', ['BBM', 'Tol', 'Parkir', 'Lainnya'])->default('Lainnya')->change();
        });
    }
};
