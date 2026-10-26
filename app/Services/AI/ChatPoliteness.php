<?php

namespace App\Services\AI;

/**
 * Deteksi & penanganan chat sosial/sapaan agar TIDAK memanggil LLM.
 *
 * Ucapan seperti "terimakasih", "halo", "dadah" sebelumnya diteruskan ke
 * pipeline + panggilan Groq, sehingga rawan error "Gagal terhubung ke API key".
 * Handler ini menjawab dengan hangat secara lokal, tanpa API eksternal.
 */
class ChatPoliteness
{
    /** Kata kunci data: jika ada, message dianggap pertanyaan data, bukan sapaan. */
    protected const DATA_KEYWORDS = [
        'berapa', 'total', 'jumlah', 'daftar', 'list', 'harga', 'nominal', 'rinci',
        'spd', 'bbm', 'lpj', 'lunch', 'makan siang', 'kendaraan', 'kendaran',
        'driver', 'pengemudi', 'permohonan', 'suku cadang', 'sparepart', 'perawatan',
        'check', 'nopol', 'laporan', 'porsi', 'pengeluaran', 'transaksi', 'status',
        'bulan', 'tahun', 'tanggal', 'minggu', 'hari ini', 'kemarin', 'minggu lalu',
        'paling', 'terbanyak', 'tertinggi', 'terbesar', 'berapa banyak', 'siapa',
        'site', 'unit', 'patuha', 'dieng', 'pusat', 'pmu',
    ];

    public function resolve(string $message): ?array
    {
        $text = mb_strtolower(trim($message));

        // Jangan interupsi bila mengandung indikator pertanyaan data.
        if ($this->looksLikeDataQuery($text)) {
            return null;
        }

        $candidates = [
            'thanks'   => ['reply' => 'Sama-sama! Senang bisa membantu. Ada lagi yang ingin Anda tanyakan seputar data operasional SIAGA? 😊'],
            'greeting' => ['reply' => 'Halo! Saya Gaga, asisten analisis data operasional SIAGA (BBM, SPD, LPJ, Kendaraan, Lunch, Suku Cadang). Ada yang bisa saya bantu?'],
            'farewell' => ['reply' => 'Sampai jumpa! Semoga hari Anda lancar. Kalau butuh bantuan lagi, tinggal panggil saya ya. 👋'],
            'affirm'   => ['reply' => 'Siap! Ada pertanyaan lain seputar data operasional yang bisa saya bantu?'],
        ];

        foreach ($candidates as $type => $candidate) {
            if ($this->matches($type, $text)) {
                return [
                    'type'  => $type,
                    'reply' => $candidate['reply'],
                ];
            }
        }

        return null;
    }

    protected function looksLikeDataQuery(string $text): bool
    {
        foreach (self::DATA_KEYWORDS as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function matches(string $type, string $text): bool
    {
        return match ($type) {
            // Terima kasih
            'thanks' => (bool) preg_match('/(\b(makasi[h]?|terima\s*kasi[h]?|terimakasi[h]?|thanks|thank\s*you|tq|thx|matur\s*nuwun)\b)|(\bmakasi[h]?\b)/i', $text),
            // Sapaan
            'greeting' => (bool) preg_match('/\b(halo|hallo|hai|hei|hi|selamat\s+(pagi|siang|sore|malam)|assalamualaikum|selamat\s+datang|good\s+(morning|afternoon|evening))\b/i', $text),
            // Perpisahan
            'farewell' => (bool) preg_match('/\b(dadah|dah|bye|sampai\s+jumpa|selamat\s+tinggal|goodbye|sampai\s+nanti|udahan|sekian|terimakasi[h]?\s+ya|oke\s+makasi[h]?)\b/i', $text),
            // Afirmasi singkat
            'affirm' => (bool) preg_match('/^(\s*(oke|ok|okay|sip|setuju|mantap|bagus|nice|good|hebat|siap)\s*[.!]*\s*)+$/i', $text),
            default => false,
        };
    }
}
