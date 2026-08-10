<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ImageCompressor
 *
 * Kompresi gambar hanya dengan menurunkan kualitas ke 50% TANPA mengubah
 * dimensi (tidak ada resize). Mengembalikan konten JPEG siap ditulis ke
 * storage server — DB hanya menyimpan path, bukan base64.
 */
class ImageCompressor
{
    /**
     * Kompres gambar (jpeg/png/webp) menjadi JPEG dengan kualitas tertentu.
     *
     * @param string $inputPath Path absolut file sumber
     * @param int $quality Kualitas JPEG (default 50)
     * @return string|null Binary JPEG, atau null bila gagal
     */
    public static function compressJpeg(string $inputPath, int $quality = 50): ?string
    {
        if (! file_exists($inputPath)) {
            return null;
        }

        try {
            $info = getimagesize($inputPath);
            if (! $info) {
                return null;
            }

            $source = match ($info['mime']) {
                'image/jpeg' => imagecreatefromjpeg($inputPath),
                'image/png'  => imagecreatefrompng($inputPath),
                'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($inputPath) : null,
                'image/gif'  => function_exists('imagecreatefromgif') ? imagecreatefromgif($inputPath) : null,
                default      => imagecreatefromstring(file_get_contents($inputPath)),
            };

            if (! $source) {
                return null;
            }

            // Pertahankan transparansi PNG jika ada (jika tidak akan jadi hitam)
            imagealphablending($source, false);
            imagesavealpha($source, true);

            ob_start();
            imagejpeg($source, null, $quality);
            $compressed = ob_get_clean();
            imagedestroy($source);

            return $compressed !== false ? $compressed : null;
        } catch (\Throwable $e) {
            Log::warning('ImageCompressor gagal: ' . $e->getMessage(), ['path' => $inputPath]);
            return null;
        }
    }
}
