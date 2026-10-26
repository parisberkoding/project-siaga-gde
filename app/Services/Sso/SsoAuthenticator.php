<?php

namespace App\Services\Sso;

use App\Models\EmployeeDefault;
use App\Models\Role;
use App\Models\SsoIdentity;
use App\Models\SsoRoleMapping;
use App\Models\SsoSyncLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SsoAuthenticator — alur provisioning & pemetaan hak akses SSO.
 *
 * Ditulis provider-agnostic: masukannya selalu SsoUserInfo yang sudah
 * dinormalisasi, sehingga menambah protokol baru tidak menyentuh class ini.
 *
 * Prinsip yang dipegang (semuanya hasil keputusan desain):
 *
 *   1. `subject` (ID unik IdP) adalah kunci pencocokan utama — bukan nama,
 *      bukan email, karena keduanya bisa berubah.
 *   2. JIT BOLEH membuat & mengaktifkan akun, tetapi hak akses ditentukan
 *      mapping. Mapping tidak ada -> NOL permission + antrian review.
 *   3. JIT DILARANG menghidupkan kembali akun yang dinonaktifkan lokal.
 *   4. Unit TIDAK PERNAH ditebak. Unit tidak dikenal -> nol permission.
 *   5. Perubahan role bersifat ASIMETRIS: downgrade/revoke langsung berlaku,
 *      upgrade menunggu persetujuan Pusat.
 *   6. Setiap user SSO WAJIB punya baris Master Pekerja, karena absensi Lunch
 *      me-resolve NAMA ke Master Pekerja, bukan ke akun.
 */
class SsoAuthenticator
{
    /**
     * Titik masuk: proses satu klaim SSO menjadi keputusan login.
     *
     * @throws SsoException bila autentikasi gagal secara tak terpulihkan
     */
    public function authenticate(SsoUserInfo $info): SsoResult
    {
        if (! $info->hasSubject()) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_REJECTED,
                subject: null,
                rawClaims: $info->raw,
                note: 'Klaim identitas unik (subject) tidak ada di payload SSO.',
                provider: $info->provider,
            );

            throw SsoException::missingSubject();
        }

        $identity = SsoIdentity::findBySubject($info->subject, $info->provider);

        if ($identity && $identity->user) {
            return $this->syncExistingUser($identity, $info);
        }

        return $this->provision($info);
    }

    /* =========================================================
     * Sudah punya identitas SSO
     * =======================================================*/

    private function syncExistingUser(SsoIdentity $identity, SsoUserInfo $info): SsoResult
    {
        $user = $identity->user;

        $identity->update([
            'claims' => $info->raw,
            'last_synced_at' => now(),
        ]);

        // ── Konflik: lokal nonaktif, SSO masih aktif ──────────────────────
        // Kebijakan yang dipilih: TANDAI, jangan ubah apa pun, dan minta Pusat
        // memutuskan. Login tetap ditolak karena akunnya memang nonaktif.
        if (! $user->isActiveAccount()) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_CONFLICT,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                note: 'Akun nonaktif di SIAGA tetapi SSO masih menganggapnya aktif. Menunggu keputusan Pusat.',
                provider: $info->provider,
            );

            throw SsoException::userDisabled();
        }

        return $this->applyAttributesAndRole($user, $info, $identity);
    }

    /* =========================================================
     * Belum punya identitas SSO -> tautkan atau ciptakan (JIT)
     * =======================================================*/

    private function provision(SsoUserInfo $info): SsoResult
    {
        $existing = $this->matchExistingUser($info);

        if ($existing) {
            return $this->linkExistingUser($existing, $info);
        }

        return $this->createUser($info);
    }

    /**
     * Cocokkan dengan user yang sudah ada TETAPI belum punya identitas SSO.
     *
     * Urutan prioritas (paling andal lebih dulu):
     *   1. NIK   — identifier HR, otoritatif
     *   2. email — unik di tabel users
     * Nama SENGAJA tidak dipakai: nama bisa berubah dan bisa kembar.
     */
    private function matchExistingUser(SsoUserInfo $info): ?User
    {
        if ($info->nik) {
            $byNik = User::query()->where('nik', $info->nik)->first();
            if ($byNik) {
                return $byNik;
            }
        }

        if ($info->email) {
            return User::query()->where('email', $info->email)->first();
        }

        return null;
    }

    private function linkExistingUser(User $user, SsoUserInfo $info): SsoResult
    {
        if (! $user->isActiveAccount()) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_CONFLICT,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                note: 'User ditemukan lewat NIK/email, tetapi akunnya nonaktif di SIAGA.',
                provider: $info->provider,
            );

            throw SsoException::userDisabled();
        }

        $identity = SsoIdentity::create([
            'user_id' => $user->id,
            'provider' => $info->provider,
            'subject' => $info->subject,
            'claims' => $info->raw,
            'last_synced_at' => now(),
        ]);

        // Pindahkan akun ke jalur SSO. WAJIB: kalau tetap `local`, user masih
        // bisa login lewat form lokal memakai password lamanya — pencabutan
        // akses di SSO jadi tidak ada artinya.
        $user->auth_provider = 'sso';

        // Password lama dimatikan juga (pertahanan berlapis): seandainya
        // auth_provider suatu saat disetel ulang, password lama tidak bisa
        // dipakai untuk masuk.
        $user->password = Str::random(64);
        $user->sso_synced_at = now();
        $user->save();

        SsoSyncLog::record(
            SsoSyncLog::DECISION_SYNCED,
            subject: $info->subject,
            userId: $user->id,
            rawClaims: $info->raw,
            note: 'Akun SIAGA yang sudah ada ditautkan ke identitas SSO; login lokal dimatikan.',
            provider: $info->provider,
        );

        return $this->applyAttributesAndRole($user, $info, $identity);
    }

    /**
     * Buat akun baru (JIT).
     */
    private function createUser(SsoUserInfo $info): SsoResult
    {
        $unit = $this->resolveUnit($info);

        return DB::transaction(function () use ($info, $unit) {
            $user = new User();

            // auth_provider sengaja di-set langsung: kolomnya di luar $fillable
            // supaya tidak bisa diubah lewat mass-assignment dari form.
            $user->auth_provider = 'sso';
            $user->is_active = (bool) config('sso.jit.auto_activate', true);

            $user->username = $this->generateUsername($info);
            $user->nama_lengkap = $info->name ?: $user->username;
            $user->email = $info->email;
            $user->nik = $info->nik;
            $user->jabatan = $info->jabatan;

            // Unit HANYA dari pencocokan kode yang dikenal. Tidak pernah ditebak.
            $user->unit_id = $unit?->id;

            // Password acak tak tertebak: user SSO tidak boleh punya jalur
            // login lokal, karena itu akan melewati deprovisioning SSO.
            $user->password = Str::random(64);
            $user->role = '';
            $user->sso_synced_at = now();
            $user->save();

            $user->user_code = 'SSO.'.str_pad($user->id, 4, '0', STR_PAD_LEFT);
            $user->save();

            SsoIdentity::create([
                'user_id' => $user->id,
                'provider' => $info->provider,
                'subject' => $info->subject,
                'claims' => $info->raw,
                'last_synced_at' => now(),
            ]);

            if (config('sso.jit.link_master_pekerja', true)) {
                $this->ensureMasterPekerja($user, $unit);
            }

            return $this->applyAttributesAndRole($user->fresh(), $info, null);
        });
    }

    /* =========================================================
     * Atribut & role
     * =======================================================*/

    private function applyAttributesAndRole(User $user, SsoUserInfo $info, ?SsoIdentity $identity): SsoResult
    {
        $unit = $this->resolveUnit($info);
        $dirty = false;

        // ── Sinkron atribut identitas dari SSO ────────────────────────────
        if ($info->name && $user->nama_lengkap !== $info->name) {
            $user->nama_lengkap = $info->name;
            $dirty = true;
        }

        if ($info->email && $user->email !== $info->email) {
            $user->email = $info->email;
            $dirty = true;
        }

        // NIK hanya diisi kalau belum ada — NIK yang sudah tersimpan dianggap
        // lebih otoritatif daripada yang baru datang (bisa jadi dari HR).
        if ($info->nik && ! $user->nik) {
            $user->nik = $info->nik;
            $dirty = true;
        }

        if ($info->jabatan && $user->jabatan !== $info->jabatan) {
            $user->jabatan = $info->jabatan;
            $dirty = true;
        }

        // ── Unit berpindah: berpengaruh besar, catat di audit ─────────────
        if ($unit && $user->unit_id !== $unit->id) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_SYNCED,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                note: 'Unit berpindah: '.($user->unit?->code ?? 'tanpa unit').' -> '.$unit->code,
                provider: $info->provider,
            );

            $user->unit_id = $unit->id;
            $dirty = true;
        }

        $user->sso_synced_at = now();
        $user->save();

        // Master Pekerja wajib ada agar absensi Lunch bisa me-resolve user ini.
        if (config('sso.jit.link_master_pekerja', true)) {
            $this->ensureMasterPekerja($user, $unit);
        }

        return $this->resolveAndApplyRole($user, $info);
    }

    /**
     * Tentukan role dari mapping (jabatan + unit), lalu terapkan kebijakan
     * asimetris.
     */
    private function resolveAndApplyRole(User $user, SsoUserInfo $info): SsoResult
    {
        // Tanpa unit, mapping tidak bisa dicari — unit_id adalah bagian dari
        // kunci mapping. Fail-closed untuk HAK AKSES.
        if (! $user->unit_id) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_UNMAPPED,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                note: 'Unit tidak dapat ditentukan (kode unit tidak dikirim atau tidak dikenal). '
                    .'User diberi NOL permission sampai Pusat menetapkan unitnya.',
                provider: $info->provider,
            );

            return SsoResult::needsReview(
                $user,
                'Unit Anda belum dapat ditentukan dari data SSO. Akun aktif, tetapi belum memiliki hak akses. '
                .'Hubungi Superadmin Pusat.'
            );
        }

        $mapping = SsoRoleMapping::resolve($info->jabatan ?? $user->jabatan, $user->unit_id);

        if (! $mapping) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_UNMAPPED,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                note: 'Belum ada mapping untuk jabatan "'.($info->jabatan ?? '-').'" di unit '.($user->unit?->code ?? '-').'.',
                provider: $info->provider,
            );

            return SsoResult::needsReview(
                $user,
                'Kombinasi jabatan & unit Anda belum dipetakan ke hak akses. '
                .'Akun aktif, tetapi belum memiliki hak akses. Hubungi Superadmin Pusat.'
            );
        }

        $currentRoleId = $user->roles()->value('roles.id');

        // Sudah sesuai — tidak ada yang perlu diubah.
        if ($currentRoleId === $mapping->role_id) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_MAPPED,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                mappedRoleId: $mapping->role_id,
                note: 'Role sudah sesuai mapping.',
                provider: $info->provider,
            );

            return SsoResult::authenticated($user);
        }

        // ── Kebijakan asimetris ───────────────────────────────────────────
        // Upgrade = role hasil mapping punya permission LEBIH BANYAK daripada
        // role sekarang. Wajib lewat review Pusat.
        //
        // PENTING: user yang BELUM punya role sama sekali (provisioning awal
        // lewat JIT) BUKAN upgrade — kalau ini salah dianggap upgrade, seluruh
        // user baru akan tertahan di antrian review dan JIT tidak ada gunanya.
        $isUpgrade = $currentRoleId !== null
            && $this->isUpgrade($currentRoleId, $mapping->role_id);

        if ($isUpgrade && config('sso.role_change.require_review_on_upgrade', true)) {
            SsoSyncLog::record(
                SsoSyncLog::DECISION_PENDING_REVIEW,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                mappedRoleId: $mapping->role_id,
                note: 'Perubahan role TERLALU MENAIKKAN hak akses (upgrade) — menunggu persetujuan Pusat. '
                    .'Role lama dipertahankan.',
                provider: $info->provider,
            );

            return SsoResult::needsReview(
                $user,
                'Jabatan Anda di SSO berubah dan memerlukan persetujuan Superadmin Pusat '
                .'sebelum hak akses baru berlaku. Hak akses lama Anda masih berlaku.',
                ['pending_role_id' => $mapping->role_id]
            );
        }

        // Downgrade / perubahan setara -> berlaku langsung (keamanan lebih dulu).
        //
        // Lewat User::assignRole() supaya aturan segmentasi unit (item 4)
        // ditegakkan di jalur SSO juga, bukan hanya di Manajemen User.
        try {
            $user->assignRole($mapping->role_id);
        } catch (\InvalidArgumentException $e) {
            // Mapping diarahkan ke role milik unit LAIN -> konfigurasi salah.
            // Fail-closed: jangan beri akses apa pun, dan minta Pusat
            // memperbaiki pemetaannya.
            SsoSyncLog::record(
                SsoSyncLog::DECISION_UNMAPPED,
                subject: $info->subject,
                userId: $user->id,
                rawClaims: $info->raw,
                mappedRoleId: $mapping->role_id,
                note: 'Pemetaan mengarah ke role yang tidak berlaku untuk unit user ini: '.$e->getMessage(),
                provider: $info->provider,
            );

            return SsoResult::needsReview(
                $user,
                'Pemetaan jabatan & unit Anda belum tepat sehingga hak akses tidak dapat diberikan. '
                .'Hubungi Superadmin Pusat.'
            );
        }

        SsoSyncLog::record(
            SsoSyncLog::DECISION_MAPPED,
            subject: $info->subject,
            userId: $user->id,
            rawClaims: $info->raw,
            mappedRoleId: $mapping->role_id,
            note: 'Role diterapkan dari mapping.',
            provider: $info->provider,
        );

        return SsoResult::authenticated($user->fresh());
    }

    /**
     * Apakah perpindahan role ini sebuah "upgrade" (menambah hak akses)?
     *
     * Dipakai pendekatan jumlah permission: lebih banyak = upgrade. Cukup untuk
     * menentukan perlu-tidaknya review; penentuan hak akses sebenarnya tetap
     * dari permission role itu sendiri, bukan dari heuristik ini.
     */
    private function isUpgrade(?int $currentRoleId, int $targetRoleId): bool
    {
        // Belum punya role = setiap pemberian role adalah upgrade.
        if (! $currentRoleId) {
            return true;
        }

        $current = Role::find($currentRoleId);
        $target = Role::find($targetRoleId);

        if (! $target) {
            return false;
        }

        return $target->permissions()->count() > ($current?->permissions()->count() ?? 0);
    }

    /* =========================================================
     * Unit & Master Pekerja
     * =======================================================*/

    /**
     * Cari unit dari kode yang dikirim SSO.
     *
     * TIDAK ada fallback ke unit mana pun. Kode yang tidak dikenal
     * mengembalikan null, dan null berarti "belum bisa ditentukan" — bukan
     * "anggap Pusat". Menebak ke Pusat akan memberi bypass gate modul.
     */
    private function resolveUnit(SsoUserInfo $info): ?Unit
    {
        if (! $info->unitCode) {
            return null;
        }

        $code = strtoupper(trim($info->unitCode));

        // Cocokkan case-insensitive: SSO bisa mengirim "patuha" atau "Patuha"
        // sementara units.code disimpan uppercase.
        return Unit::query()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();
    }

    /**
     * Pastikan user punya baris Master Pekerja yang tertaut.
     *
     * WAJIB: absensi Lunch (QR) me-resolve NAMA ke Master Pekerja, bukan ke
     * akun. Tanpa baris ini, user SSO tidak akan pernah dikenali di absensi
     * makan. `user_id` di Master Pekerja bersifat unik, sehingga pencarian
     * dulu (bukan langsung buat) mencegah baris ganda.
     */
    private function ensureMasterPekerja(User $user, ?Unit $unit): void
    {
        $existing = EmployeeDefault::query()->where('user_id', $user->id)->first();

        $siteId = $user->default_site_id ?? $this->guessSiteForUnit($unit, $user);

        if ($existing) {
            // Sinkronkan nama agar identity resolution tetap menemukan orangnya
            // setelah nama berubah di SSO.
            if ($existing->employee_name !== $user->nama_lengkap) {
                $existing->employee_name = $user->nama_lengkap;
                $existing->save();
            }

            if (! $existing->default_site_id && $siteId) {
                $existing->default_site_id = $siteId;
                $existing->save();
            }

            if (! $existing->nik && $user->nik) {
                $existing->nik = $user->nik;
                $existing->save();
            }

            if (! $existing->unit_id && $unit) {
                $existing->unit_id = $unit->id;
                $existing->save();
            }

            return;
        }

        // Cari baris Master Pekerja yang belum tertaut, cocokkan lewat NIK
        // lebih dulu — supaya tidak membuat baris ganda untuk orang yang sama
        // (mis. sebelumnya dibuat manual sebagai karyawan outsourcing).
        $unlinked = null;

        if ($user->nik) {
            $unlinked = EmployeeDefault::query()
                ->whereNull('user_id')
                ->where('nik', $user->nik)
                ->first();
        }

        if ($unlinked) {
            $unlinked->user_id = $user->id;
            $unlinked->save();

            return;
        }

        EmployeeDefault::create([
            'user_id' => $user->id,
            'nik' => $user->nik,
            'employee_name' => $user->nama_lengkap,
            'position' => $user->jabatan,
            'default_site_id' => $siteId,
            'unit_id' => $unit?->id,
            'verification_status' => 'verified',
            'source' => 'sso',
            'is_active' => true,
        ]);
    }

    /**
     * Tebak site default HANYA bila unit sudah jelas (site pertama unit itu).
     *
     * Ini bukan menebak unit — unit sudah ditentukan dari kode SSO. Site hanya
     * penyempurnaan, dan boleh kosong.
     */
    private function guessSiteForUnit(?Unit $unit, User $user): ?int
    {
        if (! $unit) {
            return null;
        }

        return $unit->sites()->orderBy('id')->value('sites.id');
    }

    /* =========================================================
     * Utilitas
     * =======================================================*/

    /**
     * Username login lokal. Hanya sebagai identitas internal — user SSO tidak
     * memakai form login lokal.
     */
    private function generateUsername(SsoUserInfo $info): string
    {
        $base = $info->email
            ? Str::before($info->email, '@')
            : 'sso.'.Str::slug((string) $info->subject);

        $base = Str::limit(preg_replace('/[^a-zA-Z0-9._-]/', '', $base) ?: 'sso.user', 24, '');

        $candidate = $base;
        $i = 1;

        while (User::withTrashed()->where('username', $candidate)->exists()) {
            $candidate = $base.'.'.$i++;
        }

        return $candidate;
    }
}
