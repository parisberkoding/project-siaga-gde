<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_defaults', function (Blueprint $table) {
            $table->string('slug', 160)->nullable()->unique()->after('employee_name');
        });

        DB::table('employee_defaults')->orderBy('id')->each(function ($row) {
            $slug = Str::slug($row->employee_name);
            $original = $slug;
            $i = 1;
            while (DB::table('employee_defaults')->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $slug = $original . '-' . $i++;
            }
            DB::table('employee_defaults')->where('id', $row->id)->update(['slug' => $slug]);
        });

        Schema::table('employee_defaults', function (Blueprint $table) {
            $table->string('slug', 160)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_defaults', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
