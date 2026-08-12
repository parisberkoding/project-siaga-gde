<?php

namespace App\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Render markdown respons AI menjadi HTML yang aman untuk ditampilkan.
 *
 * Gunakan GithubFlavoredMarkdownConverter (tabel, list, heading, bold/italic)
 * dengan html_input strip + allow_unsafe_links=false sehingga tag HTML mentah
 * dan URL javascript: tidak lolos ke halaman (anti-XSS).
 */
class AiMarkdown
{
    protected static ?GithubFlavoredMarkdownConverter $converter = null;

    public static function render(?string $text): string
    {
        if (is_null($text) || trim($text) === '') {
            return '';
        }

        self::$converter ??= new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = self::$converter->convert($text)->getContent();

        return trim($html);
    }
}