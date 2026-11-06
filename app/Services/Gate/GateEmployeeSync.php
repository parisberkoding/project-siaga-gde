<?php

namespace App\Services\Gate;

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sinkronisasi akun Pengguna dari GATE HCIS.
 *
 * PRINSIP DATA MINIMUM: dari seluruh atribut pegawai yang dikirim GATE, yang
 * disimpan HANYA:
 *     nama lengkap  ← full_name
 *     jabatan       ← pos_name_en
 *     unit bisnis   ← worklocation_code (dipetakan lewat config gate.unit_map)
 *     username      ← user_ldap (username SSO)
 *     role          ← Pegawai (hanya untuk akun baru)
 * ditambah status aktif (emp_status) untuk menonaktifkan akun pegawai yang
 * keluar. NIK, email, atasan, grade, dsb. TIDAK disimpan — termasuk di cache.
 *
 * Kunci pencocokan: username SSO (user_ldap) pada akun auth_provider=sso.
 * Sinkron bersifat idempoten — dijalankan berulang tidak membuat akun ganda.
 *
 * Aturan lain:
 *  - Akun baru login lewat SSO; password lokal acak & tidak pernah dipakai.
 *  - Akun lama: nama, jabatan, unit & status aktif diperbarui; ROLE TIDAK
 *    DISENTUH agar penyesuaian admin tidak tertimpa.
 *  - Akun LOKAL dengan username sama TIDAK diambil alih — dilaporkan.
 *  - Pegawai emp_status ≠ 1 dinonaktifkan (bukan dihapus — histori aman);
 *    bila belum punya akun, dilewati.
 *  - Lokasi kerja tak dikenal → dilewati, karena unit kosong = akses global.
 */
class GateEmployeeSync
{
    public const CACHE_KEY = 'gate_sync:last';

    public function __construct(private GateHcisClient $client)
    {
    }

    /**
     * @param  bool  $dryRun  true = hitung hasil tanpa menyimpan (transaksi di-rollback).
     * @return array{total:int, created:int, updated:int, unchanged:int, deactivated:int, skipped:array, warnings:array, dry_run:bool, finished_at:string, duration_ms:int}
     */
    public function run(bool $dryRun = false): array
    {
        $startedAt = microtime(true);
        $rows = $this->client->employees();

        $role = Role::where('code', config('gate.default_role'))->first();
        if (! $role) {
            throw new RuntimeException('Role "'.config('gate.default_role').'" tidak ditemukan. Jalankan RbacSeeder terlebih dahulu.');
        }

        $units = Unit::pluck('id', 'code');
        // Satu hash acak per sinkron: password lokal akun SSO tidak pernah
        // dipakai (login lokal ditolak untuk auth_provider=sso). Hash tunggal
        // menghindari ±100 detik bcrypt untuk ratusan akun.
        $unusablePassword = Hash::make(Str::random(64));

        $result = [
            'total' => count($rows), 'created' => 0, 'updated' => 0, 'unchanged' => 0,
            'deactivated' => 0, 'skipped' => [], 'warnings' => [], 'dry_run' => $dryRun,
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $this->syncRow($row, $role, $units, $unusablePassword, $result);
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        unset($rows); // data mentah GATE tidak dibawa keluar dari method ini

        $result['finished_at'] = now()->toDateTimeString();
        $result['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $dryRun) {
            // Cache hanya angka ringkasan — tanpa nama/username pegawai.
            Cache::forever(self::CACHE_KEY, [
                'finished_at' => $result['finished_at'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'deactivated' => $result['deactivated'],
                'skipped' => count($result['skipped']),
            ]);
        }

        return $result;
    }

    private function syncRow(array $row, Role $role, $units, string $unusablePassword, array &$result): void
    {
        $username = Str::lower(trim((string) ($row['user_ldap'] ?? '')));
        $name = trim((string) ($row['full_name'] ?? ''));
        $label = $name !== '' ? $name : $username;

        if ($username === '' || $name === '') {
            $result['skipped'][] = [$label ?: '(tanpa nama)', 'Data GATE tidak lengkap (username/nama kosong).'];

            return;
        }

        $location = strtoupper(trim((string) ($row['worklocation_code'] ?? '')));
        $unitCode = config("gate.unit_map.{$location}");
        $unitId = $unitCode ? ($units[$unitCode] ?? null) : null;

        if (! $unitId) {
            $result['skipped'][] = [$label, "Lokasi kerja \"{$location}\" belum dipetakan ke Unit Bisnis."];

            return;
        }

        $isActive = (string) ($row['emp_status'] ?? '1') === '1';
        $jabatan = trim((string) ($row['pos_name_en'] ?? '')) ?: null;

        $user = User::withTrashed()->where('username', $username)->first();

        if ($user && ! $user->usesSso()) {
            $result['skipped'][] = [$label, "Username \"{$username}\" sudah dipakai akun lokal \"{$user->nama_lengkap}\"."];

            return;
        }

        if ($user?->trashed()) {
            $result['skipped'][] = [$label, 'Akun ada di data Terhapus — pulihkan dulu bila ingin disinkronkan.'];

            return;
        }

        if (! $user) {
            if (! $isActive) {
                return; // pegawai nonaktif tanpa akun: tidak perlu dibuat
            }
            $this->createUser($username, $name, $jabatan, $unitId, $location, $role, $unusablePassword);
            $result['created']++;

            return;
        }

        $this->updateUser($user, $name, $jabatan, $unitId, $isActive, $label, $result);
    }

    private function createUser(string $username, string $name, ?string $jabatan, int $unitId, string $location, Role $role, string $password): void
    {
        $user = User::create([
            'username' => $username,
            'password' => $password,
            'nama_lengkap' => $name,
            'jabatan' => $jabatan,
            'unit_id' => $unitId,
            'role' => $role->code,
            'is_active' => true,
        ]);

        // auth_provider & sso_synced_at sengaja di luar $fillable.
        $user->auth_provider = 'sso';
        $user->sso_synced_at = now();
        $user->user_code = config('gate.user_code_prefix').'.'.str_pad($user->id, 4, '0', STR_PAD_LEFT).'.'.$location;
        $user->save();

        $user->assignRole($role);
    }

    private function updateUser(User $user, string $name, ?string $jabatan, int $unitId, bool $isActive, string $label, array &$result): void
    {
        $user->nama_lengkap = $name;
        $user->jabatan = $jabatan;

        // Pindah unit hanya bila role user boleh dipakai di unit baru
        // (role khusus unit lain akan melanggar segmentasi).
        if ((int) $user->unit_id !== $unitId) {
            $primaryRole = $user->roles()->first();
            if (! $primaryRole || $primaryRole->isAssignableTo($unitId)) {
                $user->unit_id = $unitId;
            } else {
                $result['warnings'][] = [$label, "Unit tidak diubah: role \"{$primaryRole->name}\" khusus unit lain."];
            }
        }

        $becameInactive = $user->isActiveAccount() && ! $isActive;
        if ($becameInactive) {
            $user->is_active = false;
            $user->deactivated_at = now();
            $user->deactivation_reason = 'Nonaktif di GATE HCIS';
        } elseif ($isActive && ! $user->isActiveAccount() && $user->deactivation_reason === 'Nonaktif di GATE HCIS') {
            // Hanya aktifkan kembali akun yang dinonaktifkan OLEH sinkron ini,
            // bukan yang dinonaktifkan manual oleh admin.
            $user->is_active = true;
            $user->deactivated_at = null;
            $user->deactivation_reason = null;
        }

        $changed = $user->isDirty();
        $user->sso_synced_at = now();
        $user->save();

        if ($changed) {
            $user->forgetAuthorizationCache();
        }

        match (true) {
            $becameInactive => $result['deactivated']++,
            $changed => $result['updated']++,
            default => $result['unchanged']++,
        };
    }
}
