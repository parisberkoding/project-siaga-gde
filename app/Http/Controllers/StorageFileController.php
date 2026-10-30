<?php

namespace App\Http\Controllers;

use App\Models\LpjPenggantian;
use App\Models\SpdBarcodeSignature;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\Transaction_Proofs;
use App\Models\User;
use App\Services\UnitScope;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * BUG-SEC-003 — penyaji media privat.
 *
 * File struk/odometer (dan QR surat) tidak lagi bisa diakses lewat symlink
 * `/storage`. Semua akses wajib melewati rute ini: autentikasi (`auth`) plus
 * otorisasi per record (pemilik, penumpang, atau pengelola unit).
 */
class StorageFileController extends Controller
{
    /**
     * Permission yang menandai peran pengelola — boleh melihat dokumen
     * unit-nya (bukan hanya miliknya sendiri).
     */
    private const MANAGER_PERMISSIONS = [
        'bbm.approve',
        'bbm.export',
        'spd.manage',
        'spd.approve',
        'spd.assign',
        'spd.update',
        'lpj.validate_management',
        'lpj.pencairan',
        'lpj.export',
        'lpj.delete',
    ];

    public function serve(Request $request, string $path)
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, "\0")) {
            abort(404);
        }

        // Cari file: disk privat lebih dulu (khusus folder sensitif), lalu
        // fallback ke disk publik untuk file lama yang belum dimigrasikan.
        $base = null;
        $fullPath = null;

        $candidates = MediaStorage::isPrivatePath($path)
            ? [MediaStorage::PRIVATE_DISK, MediaStorage::LEGACY_DISK]
            : [MediaStorage::LEGACY_DISK];

        foreach ($candidates as $disk) {
            $root = realpath(Storage::disk($disk)->path(''));

            if ($root === false) {
                continue;
            }

            $resolved = realpath($root.DIRECTORY_SEPARATOR.$path);

            // Path traversal: file di luar root ditolak. realpath() gagal bila
            // segmen '..' tidak valid, dan containment check memastikan file
            // selalu berada di dalam root disk.
            if ($resolved === false || ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (is_file($resolved)) {
                $base = $root;
                $fullPath = $resolved;
                break;
            }
        }

        if ($fullPath === null || $base === null) {
            abort(404);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $sensitive = MediaStorage::isPrivatePath($path);

        if ($sensitive && ! $this->bolehAkses($user, $path)) {
            abort(403, 'Anda tidak berhak membuka berkas ini.');
        }

        $mimeType = File::mimeType($fullPath);

        // Pastikan SVG (barcode QR) disajikan dengan mime yang benar.
        // File::mimeType() kadang mengembalikan text/plain untuk .svg, yang
        // menyebabkan browser menolak render di <img>.
        if (strtolower(File::extension($fullPath)) === 'svg') {
            $mimeType = 'image/svg+xml';
        }

        return response(File::get($fullPath), 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', (string) File::size($fullPath))
            ->header(
                'Cache-Control',
                $sensitive ? 'private, no-store, max-age=0' : 'private, max-age=86400'
            )
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Otorisasi media sensitif.
     *
     * - Pemilik bukti / pemilik atau penumpang SPD / pemilik LPJ selalu boleh.
     * - Pengelola (Admin GA, Manager, GA pencairan, superadmin) boleh untuk
     *   unitnya; user global boleh lintas unit.
     * - File tanpa relasi (orphan lama) fail-closed: hanya pengelola.
     */
    private function bolehAkses(User $user, string $path): bool
    {
        $scope = UnitScope::for($user);
        $manager = $user->hasRoleCode(['superadmin', 'developers'])
            || $user->hasPermission(self::MANAGER_PERMISSIONS);

        // 1. Bukti transaksi BBM (struk & odometer).
        $proof = Transaction_Proofs::withTrashed()
            ->where('struk_bbm_path', $path)
            ->orWhere('odometer_photo_path', $path)
            ->first();

        if ($proof) {
            if ((int) $proof->requester_id === (int) $user->id) {
                return true;
            }

            return $manager && ($scope->isGlobal() || $scope->allowsUnit($proof->unit_id));
        }

        // 2. Struk item SPD / LPJ.
        $item = StrukItem::where('path_image', $path)->first();

        if ($item) {
            if ($item->lpj_id) {
                $lpj = LpjPenggantian::withTrashed()->find($item->lpj_id);

                if ($lpj) {
                    if ((int) $lpj->user_id === (int) $user->id) {
                        return true;
                    }

                    if ($lpj->suratSpd && $lpj->suratSpd->isVisibleToUser($user)) {
                        return true;
                    }
                }
            }

            if ($item->spd_id) {
                $surat = SuratSpd::withTrashed()->find($item->spd_id);

                if ($surat && $surat->isVisibleToUser($user)) {
                    return true;
                }
            }

            // Struk jalur operasional tidak menyimpan user_id/unit_id di baris
            // struk_items; fail-closed ke pengelola BBM.
            return $manager;
        }

        // 3. QR surat — ikuti visibilitas suratnya.
        $signature = SpdBarcodeSignature::where('signature_path', $path)->first();

        if ($signature) {
            $surat = SuratSpd::withTrashed()->find($signature->spd_id);

            return $surat ? $surat->isVisibleToUser($user) : false;
        }

        // 4. File historis tanpa relasi sama sekali: hanya pengelola.
        return $manager;
    }
}
