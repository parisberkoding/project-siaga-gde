<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi label role ke format lowercase snake_case.
 *
 * Role sistem memakai kode-nya sendiri (superadmin, general_affairs, requester,
 * dst). Role non-sistem (buatan admin) diubah dengan aturan yang sama seperti
 * Role::normalizeLabel(): huruf kecil, spasi/simbol menjadi underscore.
 *
 * Nama role hanya dipakai untuk tampilan — tidak ada logika yang mencocokkan
 * kolom `name`, jadi perubahan ini aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        $systemNames = [
            'superadmin' => 'superadmin',
            'general_affairs' => 'general_affairs',
            'dispatcher' => 'dispatcher',
            'requester' => 'requester',
            'manager' => 'manager',
            'view_only' => 'view_only',
            'vendor' => 'vendor',
            'developers' => 'developers',
            'pengguna_spd' => 'pengguna_spd',
            'pegawai' => 'pegawai',
        ];

        foreach (DB::table('roles')->orderBy('id')->get(['id', 'code', 'name']) as $role) {
            $name = $systemNames[$role->code] ?? $this->normalizeLabel($role->name);

            if ($name !== $role->name) {
                DB::table('roles')->where('id', $role->id)->update(['name' => $name]);
            }
        }
    }

    public function down(): void
    {
        // Normalisasi data tidak dibalik — label lama tidak disimpan.
    }

    private function normalizeLabel(?string $value): string
    {
        $label = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $value))) ?? '';

        return trim($label, '_') ?: 'role';
    }
};
