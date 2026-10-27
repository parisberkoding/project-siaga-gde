<?php

namespace App\Services\Lunch;

use App\Models\EmployeeDefault;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pendaftaran pegawai dari scan QR publik.
 *
 * Pegawai non-SSO tetap harus punya identitas di SIAGA. Alur yang disepakati:
 *   scan → nama belum dikenal → isi nama/divisi/unit
 *   → dibuatkan EmployeeDefault (pending) + akun User nonaktif
 *   → superadmin unit membuat username/password final dan memberikannya
 *     secara offline, lalu mengaktifkan akun + memberi role.
 *
 * Akun sengaja TIDAK diberi role RBAC di sini. Role adalah kewenangan
 * superadmin unit, bukan hasil scan publik.
 */
class EmployeeRegistrationService
{
    /**
     * @return array{employee: EmployeeDefault, user: ?User, created: bool}
     */
    public function registerFromScan(
        Site $site,
        string $name,
        ?string $division,
        ?int $unitId = null,
        ?string $position = null
    ): array {
        $name = trim($name);
        $unitId = $unitId ?: $site->unit_id;

        if (! $unitId || ! Unit::whereKey($unitId)->exists()) {
            $unitId = $site->unit_id;
        }

        return DB::transaction(function () use ($site, $name, $division, $unitId, $position) {
            $normalized = NameNormalizer::normalize($name);

            // 1. Sudah ada di master unit yang sama? Pakai itu.
            $employee = EmployeeDefault::query()
                ->where('unit_id', $unitId)
                ->get()
                ->first(fn (EmployeeDefault $e) => NameNormalizer::normalize($e->employee_name) === $normalized);

            // 2. Fallback exact name global (hindari duplikat nama yang sama).
            if (! $employee) {
                $employee = EmployeeDefault::query()
                    ->get()
                    ->first(fn (EmployeeDefault $e) => NameNormalizer::normalize($e->employee_name) === $normalized);
            }

            $created = false;

            if (! $employee) {
                $employee = EmployeeDefault::create([
                    'employee_name'       => $name,
                    'division'            => $division,
                    'position'            => $position,
                    'default_site_id'     => $site->id,
                    'unit_id'             => $unitId,
                    'is_active'           => true,
                    'verification_status' => 'pending',
                    'source'              => 'scan',
                ]);
                $created = true;
            } else {
                $updates = [];

                if (! $employee->unit_id && $unitId) {
                    $updates['unit_id'] = $unitId;
                }
                if (! $employee->default_site_id) {
                    $updates['default_site_id'] = $site->id;
                }
                if (! $employee->division && $division) {
                    $updates['division'] = $division;
                }
                if (! $employee->position && $position) {
                    $updates['position'] = $position;
                }
                if ($employee->verification_status === 'pending' && $employee->source === 'admin') {
                    $updates['verification_status'] = 'pending';
                }

                if ($updates) {
                    $employee->update($updates);
                }
            }

            $user = $employee->user;

            // 3. Pastikan ada akun. Akun SSO yang sudah terhubung tidak diubah.
            if (! $user) {
                $user = $this->findExistingUser($name, $unitId);

                if (! $user) {
                    $user = User::create([
                        'username'            => $this->uniqueUsername($name, $unitId),
                        'password'            => Str::random(32),
                        'nama_lengkap'        => $name,
                        'role'                => 'pegawai',
                        'jabatan'             => $position ?? $employee->position,
                        'default_site_id'     => $site->id,
                        'unit_id'             => $unitId,
                        'email'               => null,
                        'is_active'           => false,
                        'registration_source' => 'scan',
                    ]);
                }

                $employee->update(['user_id' => $user->id]);
            }

            return [
                'employee' => $employee->fresh(['user', 'defaultSite.unit', 'unit']),
                'user'     => $user,
                'created'  => $created,
            ];
        });
    }

    private function findExistingUser(string $name, ?int $unitId): ?User
    {
        $normalized = NameNormalizer::normalize($name);

        $candidates = User::query()
            ->whereNull('deleted_at')
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->get(['id', 'nama_lengkap', 'unit_id', 'auth_provider']);

        return $candidates->first(
            fn (User $u) => NameNormalizer::normalize($u->nama_lengkap) === $normalized
        );
    }

    private function uniqueUsername(string $name, ?int $unitId): string
    {
        $unitCode = $unitId ? Unit::whereKey($unitId)->value('code') : null;
        $base = 'scan.'.Str::slug($name).($unitCode ? '.'.strtolower($unitCode) : '');
        $base = $base ?: 'scan.'.Str::random(6);

        $username = $base;
        $i = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base.'.'.$i++;
        }

        return $username;
    }
}
