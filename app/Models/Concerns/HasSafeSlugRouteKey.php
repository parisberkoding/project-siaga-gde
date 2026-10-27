<?php

namespace App\Models\Concerns;

/**
 * Jaring pengaman untuk model yang memakai kolom slug sebagai route key.
 *
 * Masalah yang dicegah: bila `slug` kosong/NULL (mis. baris hasil seeder yang
 * menyimpan langsung lewat DB::table tanpa event `creating`), Laravel
 * menghasilkan URL dengan parameter kosong dan melempar:
 *
 *     UrlGenerationException: Missing required parameter for [Route: ...]
 *
 * Trait ini membuat `getRouteKey()` jatuh ke primary key bila slug kosong,
 * sehingga tautan tidak pernah gagal dibuat. Resolusi baliknya juga menerima
 * ID, jadi URL fallback tetap bisa dibuka.
 */
trait HasSafeSlugRouteKey
{
    /**
     * Nilai yang dipakai saat model dijadikan parameter route.
     */
    public function getRouteKey()
    {
        $key = parent::getRouteKey();

        if ($key === null || $key === '') {
            return $this->getKey();
        }

        return $key;
    }

    /**
     * Resolusi route binding: coba kolom route key, lalu fallback ke ID.
     *
     * Model yang sudah punya implementasi sendiri (SuratSpd, LpjPenggantian)
     * tetap memakai implementasinya karena method di class mengalahkan trait.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === null) {
            $record = $this->where($this->getRouteKeyName(), $value)->first();

            if ($record) {
                return $record;
            }

            if (ctype_digit((string) $value)) {
                return $this->find((int) $value);
            }

            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
