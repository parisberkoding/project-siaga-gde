<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * BUG-SEC-003 — backfill media sensitif dari disk publik ke disk privat.
 *
 * Sebelum patch ini foto struk & odometer BBM (dan QR surat) disimpan di
 * `storage/app/public` sehingga bisa diakses tanpa login lewat symlink
 * `/storage/...`. Migrasi ini memindahkan file lama ke
 * `storage/app/private/...` agar rute `/files/storage/{path}` yang
 * ber-autentikasi menjadi satu-satunya pintu akses.
 *
 * Reversible: `down()` memindahkan file kembali ke disk publik. File yang
 * sudah ada di tujuan tidak ditimpa (fallback baca tetap menangani duplikat).
 */
return new class extends Migration
{
    /**
     * Folder yang dipindahkan. Tambahkan folder sensitif lain di sini bila
     * kelak ikut diprivatkan.
     */
    private const DIRECTORIES = [
        'transaction_proofs',
        'qr-surat',
    ];

    public function up(): void
    {
        $this->move('public', 'local');
    }

    public function down(): void
    {
        $this->move('local', 'public');
    }

    private function move(string $fromDisk, string $toDisk): void
    {
        $fromRoot = rtrim(Storage::disk($fromDisk)->path(''), DIRECTORY_SEPARATOR);
        $toRoot = rtrim(Storage::disk($toDisk)->path(''), DIRECTORY_SEPARATOR);

        foreach (self::DIRECTORIES as $directory) {
            $source = $fromRoot.DIRECTORY_SEPARATOR.$directory;

            if (! is_dir($source)) {
                continue;
            }

            foreach (File::allFiles($source) as $file) {
                $relative = $directory.'/'.str_replace('\\', '/', $file->getRelativePathname());
                $target = $toRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

                File::ensureDirectoryExists(dirname($target));

                // Jangan menimpa file yang sudah ada di tujuan.
                if (File::exists($target)) {
                    continue;
                }

                try {
                    File::move($file->getPathname(), $target);
                } catch (\Throwable $e) {
                    if (File::copy($file->getPathname(), $target)) {
                        File::delete($file->getPathname());
                    }
                }
            }

            $this->pruneEmptyDirectories($source);
        }
    }

    private function pruneEmptyDirectories(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (File::directories($directory) as $subDirectory) {
            $this->pruneEmptyDirectories($subDirectory);
        }

        if (count(File::files($directory)) === 0 && count(File::directories($directory)) === 0) {
            @rmdir($directory);
        }
    }
};
