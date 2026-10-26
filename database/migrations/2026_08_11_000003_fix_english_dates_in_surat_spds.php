<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaiki tanggal_surat lama yang tersimpan dengan nama bulan Bahasa
     * Inggris (mis. "Patuha, 29 July 2026") menjadi format Bahasa Indonesia
     * ("Patuha, 29 Juli 2026") dan lokasi mengikut situs user.
     */
    public function up(): void
    {
        if (! Schema::hasTable('surat_spds')) {
            return;
        }

        DB::table('surat_spds')
            ->whereNotNull('tanggal_surat')
            ->orderBy('id')
            ->each(function ($row) {
                $dateStr = trim((string) $row->tanggal_surat);

                // Pisahkan bagian lokasi dan tanggal ("Lokasi, dd Month YYYY").
                $comma = strrpos($dateStr, ',');
                if ($comma === false) {
                    return;
                }

                $datePart = trim(substr($dateStr, $comma + 1));

                try {
                    $parsed = Carbon\Carbon::parse($datePart);
                } catch (\Throwable $e) {
                    return;
                }

                $newDate = $parsed->locale('id')->translatedFormat('d F Y');
                if ($newDate === $datePart) {
                    return;
                }

                $siteName = null;
                if (! empty($row->user_id)) {
                    $siteName = DB::table('users')
                        ->join('sites', 'sites.id', '=', 'users.default_site_id')
                        ->where('users.id', $row->user_id)
                        ->value('sites.name');
                }

                $location = $siteName ?: trim(substr($dateStr, 0, $comma)) ?: 'Patuha';

                DB::table('surat_spds')
                    ->where('id', $row->id)
                    ->update(['tanggal_surat' => $location . ', ' . $newDate]);
            });
    }

    public function down(): void
    {
        // Tidak ada rollback otomatis untuk perbaikan data format tanggal.
    }
};