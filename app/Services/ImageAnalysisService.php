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
Anda adalah asisten pembaca struk/bon (receipt) untuk pelaporan operasional & SPD.
Tugas terpenting Anda: membaca ANGKA dengan benar — terutama TANGGAL dan NOMINAL.
Baca gambar dengan teliti, lalu kembalikan HANYA JSON valid tanpa teks lain:

{
  "kategori": "BBM | Tol | Parkir | Makan | Hotel | Lainnya | Tambahan",
  "metadata": {
    "tanggal": "YYYY-MM-DD",
    "tanggal_mentah": "teks tanggal PERSIS seperti tercetak di struk (boleh termasuk jam), atau null",
    "nominal": <angka integer atau null>,
    "volume": <angka liter, hanya jika BBM, atau null>,
    "harga_per_liter": <angka rupiah per liter, hanya jika terbaca, atau null>,
    "plat_nomor": "nomor polisi kendaraan yang tertulis di struk, huruf kapital tanpa spasi (contoh: B1234XYZ), atau null"
  },
  "text": "hasil OCR penuh / deskripsi gambar"
}

=== ATURAN TANGGAL (PALING PENTING — tanggal salah = laporan salah) ===
1. Cari baris berlabel "Waktu", "Tanggal", "Tgl", "Tgl. Transaksi", "Date", atau cap waktu
   transaksi. Pada struk SPBU Pertamina, tanggal transaksi ada pada baris "Waktu" dengan
   bentuk "DD/MM/YYYY HH:MM:SS" — contoh: "Waktu 25/06/2026 04:44:54" berarti 25 Juni 2026.
2. Struk Indonesia memakai urutan HARI-BULAN-TAHUN (DD/MM/YYYY). Jadi "25/06/2026" adalah
   tanggal 25 bulan 6 tahun 2026. JANGAN membalik hari & bulan (bukan 26 Juni).
3. Salin angka tanggal PERSIS seperti tercetak — hari, bulan, dan TAHUN 4 digit. JANGAN
   menggeser hari (±1) dan JANGAN mengubah tahun. Kalau struk tertulis 2026, jawablah 2026;
   jangan menulis 2025.
4. Periksa digit yang mudah tertukar SATU PER SATU: 5 vs 6 vs 8, 0 vs 8 vs 9, 1 vs 7, 2 vs 3.
   Salin bacaan digitnya ke "tanggal_mentah" supaya bisa diverifikasi manusia.
5. Bila ada BEBERAPA tanggal pada struk (tanggal cetak di footer, tanggal kartu, masa promo,
   tanggal kadaluarsa), pakai tanggal TRANSAKSI — biasanya berdekatan dengan nomor transaksi,
   daftar produk, dan total pembayaran.
6. "tanggal" WAJIB format YYYY-MM-DD dengan tahun 4 digit. Bila tanggal benar-benar tidak
   terbaca, isi null. JANGAN menebak dan JANGAN memakai tanggal hari ini.
7. "tanggal_mentah" = salinan apa adanya dari struk (boleh ada jam). Isi null bila tidak ada.
=== AKHIR ATURAN TANGGAL ===

Aturan lain:
- Kategori hanya salah satu dari: BBM, Tol, Parkir, Makan, Hotel, Lainnya, Tambahan.
- BBM jika struk SPBU (premium/pertamax/solar/dex/bahan bakar).
- Tol jika struk jalan tol / transaksi gerbang tol.
- Parkir jika struk/karcis parkir.
- Makan jika bukti makanan/minuman (bon restoran, kafe, katering, konsumsi, warung, bekal).
- Hotel jika bukti penginapan (invoice hotel, bill room, penginapan, losmen, homestay, resort).
- Tambahan jika foto odometer, dashboard mobil, speedometer, KM kendaraan, atau foto non-struk yang berkaitan dengan kendaraan (bukan struk/bon pengeluaran).
- Selain itu = Lainnya (jangan buat kategori baru).
- nominal = total pembayaran yang tertulis di struk. Untuk Tambahan, nominal = null.
- volume & harga_per_liter hanya untuk BBM bila terbaca; selain itu null.
- Jika angka tidak terbaca, gunakan null. JANGAN menebak.
- plat_nomor = nomor polisi kendaraan bila tercantum di struk (umum pada struk BBM fleet/Pertamina). Tulis huruf kapital tanpa spasi/tanda baca. Jika tidak ada atau tidak terbaca, gunakan null. JANGAN menebak.
- text = seluruh teks yang terbaca dari gambar, atau deskripsi singkat untuk foto non-struk.
PROMPT;

    /**
     * Susun system prompt, ditambah blok KONTEKS bila periode SPD diketahui.
     *
     * Konteks dipakai untuk VERIFIKASI SILANG (memastikan digit tahun tidak
     * salah baca), bukan untuk menebak. Prompt-nya menegaskan agar model tetap
     * menyalin apa yang benar-benar tercetak.
     *
     * @param  array{nomor_surat?: string|null, periode_mulai?: string|null, periode_selesai?: string|null}|array{}  $context
     */
    public function buildSystemPrompt(array $context = []): string
    {
        $mulai = $context['periode_mulai'] ?? null;
        $selesai = $context['periode_selesai'] ?? null;

        if (! $mulai && ! $selesai) {
            return self::SYSTEM_PROMPT;
        }

        $periode = '-';
        if ($mulai && $selesai) {
            $periode = $this->tanggalIndonesia($mulai).' s/d '.$this->tanggalIndonesia($selesai);
        } elseif ($mulai) {
            $periode = 'sekitar '.$this->tanggalIndonesia($mulai);
        } else {
            $periode = 'sekitar '.$this->tanggalIndonesia($selesai);
        }

        $nomor = $context['nomor_surat'] ?? '-';

        return self::SYSTEM_PROMPT . <<<CONTEXT

=== KONTEKS PERJALANAN DINAS (untuk verifikasi silang, BUKAN untuk menebak) ===
- Nomor surat tugas   : {$nomor}
- Periode perjalanan  : {$periode}
- Tanggal transaksi pada struk umumnya berada di dalam atau berdekatan dengan periode ini.
- FUNGSI KONTEKS INI HANYA UNTUK MEMERIKSA ULANG: bila hasil bacaan Anda menghasilkan tahun
  yang berbeda jauh dari periode di atas, PERIKSA KEMBALI digit tahun pada gambar sebelum
  menjawab. JANGAN langsung menyalin tahun periode, dan JANGAN mengubah tanggal yang sudah
  benar hanya supaya cocok dengan periode. Yang benar adalah apa yang TERCETAK di struk.
=== AKHIR KONTEKS ===
CONTEXT;
    }

    /**
     * Format tanggal ISO menjadi teks Indonesia ("25 Juni 2026") untuk konteks prompt.
     */
    private function tanggalIndonesia(?string $iso): string
    {
        try {
            return \Carbon\Carbon::parse($iso)->locale('id')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            return (string) $iso;
        }
    }


    /**
     * Analisis gambar struk.
     *
     * @param  string  $path     Absolute path ke file gambar
     * @param  array{nomor_surat?: string|null, periode_mulai?: string|null, periode_selesai?: string|null}  $context
     *                           Konteks periode SPD — dipakai model untuk verifikasi
     *                           silang digit tanggal (bukan untuk menebak).
     * @return array{status: string, kategori: string, metadata: array, text: string}
     */
    public function analyzeImage(string $path, array $context = []): array
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
                ? $this->callNativeGemini($path, $apiKey, $context)
                : $this->callOpenAiCompat($path, $apiKey, $context);

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
            'metadata' => ['tanggal' => null, 'tanggal_mentah' => null, 'nominal' => null, 'volume' => null, 'harga_per_liter' => null, 'plat_nomor' => null],
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
    private function callNativeGemini(string $path, string $apiKey, array $context = []): \Illuminate\Http\Client\Response
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
                    'parts' => [['text' => $this->buildSystemPrompt($context)]],
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
    private function callOpenAiCompat(string $path, string $apiKey, array $context = []): \Illuminate\Http\Client\Response
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
                            'content' => $this->buildSystemPrompt($context),
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
            'metadata' => ['tanggal' => null, 'tanggal_mentah' => null, 'nominal' => null, 'volume' => null, 'harga_per_liter' => null, 'plat_nomor' => null],
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
                'tanggal_mentah'   => isset($metadata['tanggal_mentah']) && is_string($metadata['tanggal_mentah']) && trim($metadata['tanggal_mentah']) !== ''
                    ? trim($metadata['tanggal_mentah'])
                    : null,
                'nominal'          => isset($metadata['nominal']) && $metadata['nominal'] !== null && $metadata['nominal'] !== ''
                    ? (float) $metadata['nominal']
                    : null,
                'volume'           => isset($metadata['volume']) && $metadata['volume'] !== null && $metadata['volume'] !== ''
                    ? (float) $metadata['volume']
                    : null,
                'harga_per_liter'  => isset($metadata['harga_per_liter']) && $metadata['harga_per_liter'] !== null && $metadata['harga_per_liter'] !== ''
                    ? (float) $metadata['harga_per_liter']
                    : null,
                'plat_nomor'       => self::normalizePlate($metadata['plat_nomor'] ?? null),
            ],
            'text' => is_string($decoded['text'] ?? null) ? $decoded['text'] : $content,
        ];
    }

    /**
     * Normalisasi nomor polisi: huruf kapital, buang semua karakter non-alfanumerik.
     * Contoh: "B 1234 XYZ" → "B1234XYZ". Mengembalikan null bila kosong.
     */
    public static function normalizePlate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) $value);

        return $clean === '' ? null : strtoupper($clean);
    }
}
