<?php

namespace App\Services;

use App\Models\StrukItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiStatusService;

/**
 * ImageAnalysisService
 *
 * Integrasi VLM multimodal untuk membaca struk/bon (OCR) dan mengkategorikan
 * pengeluaran secara otomatis. Hasil AI hanya berupa PREFILL — driver tetap
 * boleh mengoreksi semua field sebelum submit.
 *
 * Kontrak JSON yang dikembalikan (setiap kategori):
 *  - status   : ok | api_limit | api_error
 *               ok        = analisis berhasil
 *               api_limit = kuota/rate-limit provider habis (mis. Gemini 429)
 *               api_error = error lain (jaringan, respons tak terparse, dll.)
 *  - kategori : BBM | Tol | Parkir | Lainnya
 *  - metadata : field relevan per kategori
 *      BBM    : tanggal, nominal, volume, harga_per_liter (opsional)
 *      Tol    : tanggal, nominal
 *      Parkir : tanggal, nominal
 *      Lainnya: tanggal, nominal (opsional)
 *  - text     : hasil OCR penuh (teks mentah struk)
 */
class ImageAnalysisService
{
    public const STATUS_OK        = 'ok';
    public const STATUS_API_LIMIT = 'api_limit';
    public const STATUS_API_ERROR = 'api_error';

    private const SYSTEM_PROMPT = <<<'PROMPT'
Anda adalah asisten pengolah struk/bon (receipt) untuk pelaporan operasional & SPD.
Baca gambar dengan teliti, lalu kembalikan HANYA JSON valid tanpa teks lain:

{
  "kategori": "BBM | Tol | Parkir | Lainnya | Tambahan",
  "metadata": {
    "tanggal": "YYYY-MM-DD",
    "nominal": <angka integer atau null>,
    "volume": <angka liter, hanya jika BBM, atau null>,
    "harga_per_liter": <angka rupiah per liter, hanya jika terbaca, atau null>
  },
  "text": "hasil OCR penuh / deskripsi gambar"
}

Aturan:
- Kategori hanya salah satu dari: BBM, Tol, Parkir, Lainnya, Tambahan.
- BBM jika struk SPBU (premium/pertamax/solar/dex/bahan bakar).
- Tol jika struk jalan tol / transaksi gerbang tol.
- Parkir jika struk/karcis parkir.
- Tambahan jika foto odometer, dashboard mobil, speedometer, KM kendaraan, atau foto non-struk yang berkaitan dengan kendaraan (bukan struk/bon pengeluaran).
- Selain itu = Lainnya (jangan buat kategori baru).
- nominal = total pembayaran yang tertulis di struk. Untuk Tambahan, nominal = null.
- Jika angka tidak terbaca, gunakan null. JANGAN menebak.
- Tanggal dalam format YYYY-MM-DD. Jika tidak terbaca, gunakan null.
- text = seluruh teks yang terbaca dari gambar, atau deskripsi singkat untuk foto non-struk.
PROMPT;

    /**
     * Analisis gambar struk.
     *
     * @param string $path Absolute path ke file gambar
     * @return array{status: string, kategori: string, metadata: array, text: string}
     */
    public function analyzeImage(string $path): array
    {
        if (! file_exists($path)) {
            Log::warning('ImageAnalysisService: file tidak ditemukan.', ['path' => $path]);
            return $this->emptyResult(self::STATUS_API_ERROR);
        }

        $apiKey = config('imageanalysis.api_key');

        // Fallback: tanpa API key, biarkan driver mengisi manual.
        if (empty($apiKey)) {
            if (config('imageanalysis.fallback_enabled')) {
                Log::info('ImageAnalysisService: API key kosong, memakai fallback manual.');
                return $this->emptyResult(self::STATUS_API_ERROR);
            }

            throw new \RuntimeException('IMAGE_ANALYSIS_API_KEY belum dikonfigurasi di .env.');
        }

        $start = microtime(true);

        try {
            // Deteksi format endpoint: native Gemini (generateContent) vs OpenAI-compat.
            $baseUrl       = (string) config('imageanalysis.base_url');
            $isNativeGemini = str_contains($baseUrl, 'generateContent');

            $response = $isNativeGemini
                ? $this->callNativeGemini($path, $apiKey)
                : $this->callOpenAiCompat($path, $apiKey);

            if (! $response->successful()) {
                $limited = $this->isRateLimited($response->status(), $response->body());

                Log::error('ImageAnalysisService: HTTP error.', [
                    'status'    => $response->status(),
                    'api_limit' => $limited,
                    'body'      => substr($response->body(), 0, 500),
                ]);

                if ($limited) {
                    GeminiStatusService::markRateLimited();
                }

                return $this->emptyResult($limited ? self::STATUS_API_LIMIT : self::STATUS_API_ERROR);
            }

            // Path respons berbeda: native → candidates[].content.parts[].text
            //                    compat → choices[].message.content
            $content = $isNativeGemini
                ? (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '')
                : (string) data_get($response->json(), 'choices.0.message.content', '');

            $parsed = $this->parseContent($content);

            if (($parsed['status']) === self::STATUS_OK) {
                GeminiStatusService::markHealthy();

                Log::info('ImageAnalysisService: berhasil dalam ' . round(microtime(true) - $start, 2) . 's.', [
                    'kategori' => $parsed['kategori'],
                ]);
            } else {
                Log::warning('ImageAnalysisService: respons model tidak dapat diparse sebagai JSON struk.');
            }

            return $parsed;

        } catch (\Throwable $e) {
            Log::error('ImageAnalysisService: gagal. ' . $e->getMessage(), [
                'time_ms' => round((microtime(true) - $start) * 1000),
            ]);
            return $this->emptyResult(self::STATUS_API_ERROR);
        }
    }

    /**
     * Hasil kosong (prefill manual) dengan status kegagalan yang eksplisit.
     */
    private function emptyResult(string $status): array
    {
        return [
            'status'   => $status,
            'kategori' => 'Lainnya',
            'metadata' => ['tanggal' => null, 'nominal' => null, 'volume' => null, 'harga_per_liter' => null],
            'text'     => '',
        ];
    }

    /**
     * Deteksi rate-limit / kuota habis pada provider (mis. Gemini):
     * HTTP 429, atau body berisi RESOURCE_EXHAUSTED / quota (bisa juga 403).
     */
    private function isRateLimited(int $httpStatus, string $body): bool
    {
        if ($httpStatus === 429) {
            return true;
        }

        return (bool) preg_match('/RESOURCE_EXHAUSTED|quota/i', $body);
    }

    /**
     * Panggil endpoint native Gemini (generateContent).
     * Auth via header x-goog-api-key. Body pakai schema contents/parts/inline_data.
     */
    private function callNativeGemini(string $path, string $apiKey): \Illuminate\Http\Client\Response
    {
        $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'image/jpeg') : 'image/jpeg';
        $b64  = base64_encode(file_get_contents($path));

        // base_url sudah berisi path lengkap termasuk :generateContent (dan model).
        $url = rtrim((string) config('imageanalysis.base_url'), '/');

        return Http::withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout(config('imageanalysis.timeout', 60))
            ->connectTimeout(15)
            ->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => self::SYSTEM_PROMPT]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => 'Analisis struk berikut dan kembalikan JSON sesuai instruksi.'],
                            ['inline_data' => ['mime_type' => $mime, 'data' => $b64]],
                        ],
                    ],
                ],
                'generationConfig' => ['temperature' => 0],
            ]);
    }

    /**
     * Panggil endpoint OpenAI-compatible (chat/completions).
     * Auth via Bearer token. Body pakai schema messages + image_url data-URL.
     */
    private function callOpenAiCompat(string $path, string $apiKey): \Illuminate\Http\Client\Response
    {
        return Http::withToken($apiKey)
            ->timeout(config('imageanalysis.timeout', 60))
            ->connectTimeout(15)
            ->post(
                rtrim((string) config('imageanalysis.base_url'), '/') . config('imageanalysis.endpoint', '/chat/completions'),
                [
                    'model' => config('imageanalysis.model', 'gemini-2.0-flash'),
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => self::SYSTEM_PROMPT,
                        ],
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analisis struk berikut dan kembalikan JSON sesuai instruksi.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $this->toDataUrl($path),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0,
                ]
            );
    }

    /**
     * Ubah gambar ke data URL (base64) untuk dikirim ke VLM.
     * Bukan disimpan ke DB — hanya untuk payload request.
     */
    private function toDataUrl(string $path): string
    {
        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'image/jpeg';
        $mime = $mime ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /**
     * Parse teks JSON dari response model (robust terhadap backticks).
     * @return array{status: string, kategori: string, metadata: array, text: string}
     */
    private function parseContent(string $content): array
    {
        $fallback = [
            'status'   => self::STATUS_API_ERROR,
            'kategori' => 'Lainnya',
            'metadata' => ['tanggal' => null, 'nominal' => null, 'volume' => null, 'harga_per_liter' => null],
            'text'     => $content,
        ];

        if (empty(trim($content))) {
            return $fallback;
        }

        // Lepas wrapper ```json ... ```
        $clean = trim($content);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/```\s*$/', '', $clean);

        // Ambil substring JSON pertama yang valid
        preg_match('/\{.*\}/s', $clean, $match);
        if (empty($match)) {
            return $fallback;
        }

        $decoded = json_decode($match[0], true);
        if (! is_array($decoded)) {
            return $fallback;
        }

        $kategori = StrukItem::normalizeKategori($decoded['kategori'] ?? null);
        $metadata = is_array($decoded['metadata'] ?? null) ? $decoded['metadata'] : [];

        return [
            'status'   => self::STATUS_OK,
            'kategori' => $kategori,
            'metadata' => [
                'tanggal'          => $metadata['tanggal'] ?? null,
                'nominal'          => isset($metadata['nominal']) && $metadata['nominal'] !== null && $metadata['nominal'] !== ''
                    ? (float) $metadata['nominal']
                    : null,
                'volume'           => isset($metadata['volume']) && $metadata['volume'] !== null && $metadata['volume'] !== ''
                    ? (float) $metadata['volume']
                    : null,
                'harga_per_liter'  => isset($metadata['harga_per_liter']) && $metadata['harga_per_liter'] !== null && $metadata['harga_per_liter'] !== ''
                    ? (float) $metadata['harga_per_liter']
                    : null,
            ],
            'text' => is_string($decoded['text'] ?? null) ? $decoded['text'] : $content,
        ];
    }
}
