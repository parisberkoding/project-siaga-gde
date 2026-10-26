<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code')->unique()->nullable()->after('id');
        });

        // Backfill existing rows
        $users = DB::table('users')->orderBy('id')->get();
        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'user_code' => 'USR-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // Make non-nullable after backfill
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_code');
        });
    }
};
