<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * BUG-SEC-003 — satu pintu untuk media sensitif (struk & foto odometer BBM,
 * serta QR surat).
 *
 * Sebelum patch ini file ditulis ke disk `public` sehingga bisa diunduh
 * langsung lewat symlink `/storage/...` tanpa login. Sekarang file baru
 * ditulis ke disk privat (`storage/app/private/...`) dan hanya disajikan
 * lewat rute ber-autentikasi `/files/storage/{path}`.
 *
 * File lama yang belum sempat dipindahkan oleh migrasi tetap dibaca sebagai
 * fallback dari disk `public`, sehingga data historis tidak hilang.
 */
final class MediaStorage
{
    /** Disk privat (root: storage/app/private). */
    public const PRIVATE_DISK = 'local';

    /** Disk lama yang masih dipakai untuk fallback file historis. */
    public const LEGACY_DISK = 'public';

    /**
     * Folder yang wajib privat. Path di luar daftar ini tetap memakai disk
     * publik (mis. foto kendaraan / bukti service) sampai dimigrasikan.
     */
    public const PRIVATE_PREFIXES = [
        'transaction_proofs/',
        'qr-surat/',
    ];

    /**
     * Apakah path termasuk media yang wajib privat.
     */
    public static function isPrivatePath(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        foreach (self::PRIVATE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Disk tempat file benar-benar ada: privat lebih dulu, lalu legacy publik.
     * Null bila file tidak ditemukan di keduanya.
     */
    public static function diskFor(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (self::isPrivatePath($path)) {
            if (Storage::disk(self::PRIVATE_DISK)->exists($path)) {
                return self::PRIVATE_DISK;
            }

            if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
                return self::LEGACY_DISK;
            }

            return null;
        }

        // Path non-sensitif belum dimigrasikan: tetap di disk publik.
        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            return self::LEGACY_DISK;
        }

        return null;
    }

    public static function exists(string $path): bool
    {
        return self::diskFor($path) !== null;
    }

    public static function get(string $path): ?string
    {
        $disk = self::diskFor($path);

        if ($disk === null) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        return $contents === false ? null : $contents;
    }

    public static function mimeType(string $path): ?string
    {
        $disk = self::diskFor($path);

        if ($disk === null) {
            return null;
        }

        try {
            return Storage::disk($disk)->mimeType($path) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Path absolut (khusus driver lokal) untuk kompresi gambar/PDF.
     */
    public static function absolutePath(string $path): ?string
    {
        $disk = self::diskFor($path);

        if ($disk === null) {
            return null;
        }

        try {
            return Storage::disk($disk)->path($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Simpan media baru ke disk privat. Return path relatif.
     */
    public static function put(string $path, string $contents): string
    {
        $path = ltrim($path, '/');
        Storage::disk(self::PRIVATE_DISK)->put($path, $contents);

        return $path;
    }

    /**
     * Simpan file upload ke disk privat. Return path relatif.
     */
    public static function storeUploaded($file, string $directory, string $filename): string
    {
        $directory = trim($directory, '/');

        $file->storeAs($directory, $filename, self::PRIVATE_DISK);

        return $directory === '' ? $filename : $directory.'/'.$filename;
    }

    /**
     * Hapus media dari disk privat maupun legacy publik (jika ada).
     */
    public static function delete(string $path): void
    {
        foreach ([self::PRIVATE_DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * URL internal yang selalu melewati rute ber-autentikasi.
     */
    public static function url(string $path): string
    {
        return url('files/storage/'.ltrim($path, '/'));
    }

    /**
     * Data URI untuk disematkan ke dokumen cetak/PDF (tanpa request HTTP).
     */
    public static function dataUri(string $path): ?string
    {
        $contents = self::get($path);

        if ($contents === null) {
            return null;
        }

        $mime = self::mimeType($path) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
