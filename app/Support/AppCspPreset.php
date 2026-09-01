<?php

namespace App\Support;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Preset CSP yang disesuaikan dengan kebutuhan aplikasi.
 *
 * Aplikasi banyak memakai inline <script>/<style> dan asset CDN, sehingga
 * policy tidak bisa memakai default-src 'self' yang ketat tanpa memutus
 * fungsionalitas. Preset ini tetap menambahkan proteksi penting:
 *  - frame-ancestors 'none'  → anti clickjacking
 *  - object-src 'none'       → blok plugin (Flash/Java)
 *  - base-uri 'self'         → blok injection tag <base>
 *  - form-action 'self'      → batasi tujuan submit form
 * dan membatasi sumber asset ke domain yang memang dipakai aplikasi.
 */
class AppCspPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::SCRIPT, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                Keyword::UNSAFE_EVAL,
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com',
                'https://unpkg.com',
                'https://epson.github.io',
            ])
            ->add(Directive::STYLE, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                'https://fonts.googleapis.com',
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com',
                'https://unpkg.com',
            ])
            ->add(Directive::FONT, [
                Keyword::SELF,
                'https://fonts.gstatic.com',
                'https://cdn.jsdelivr.net',
            ])
            ->add(Directive::IMG, [
                Keyword::SELF,
                'data:',
                'blob:',
                'https:',
            ]);
    }
}
