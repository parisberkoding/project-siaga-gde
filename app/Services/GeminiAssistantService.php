<?php

namespace App\Services;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\MasterGasoline;
use App\Models\Permohonan;
use App\Models\Transaction_Proofs;
use App\Models\VehicleAndToolsConsumers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI Assistant berbasis Gemini (function calling).
 *
 * Model default: "Gemini 3 Flash Live" (dapat diganti via env AI_ASSISTANT_MODEL).
 * AI dapat memanggil fungsi untuk query data dashboard secara real-time,
 * lalu menjawab pertanyaan dalam bahasa alami.
 *
 * ⚠️ MASIH TAHAP PENGEMBANGAN — jawaban AI belum tentu stabil / akurat.
 */
class GeminiAssistantService
{
    protected const MAX_ITERS = 5;
    protected const MAX_HISTORY = 12;

    public function isConfigured(): bool
    {
        return !empty(config('services.gemini.api_key'));
    }

    /**
     * Titik masuk utama: terima pertanyaan user, jalankan loop function calling,
     * simpan riwayat ke database, kembalikan jawaban.
     *
     * @return array{session: AiChatSession, reply: string}
     */
    public function ask(int $userId, ?int $sessionId, string $question): array
    {
        $session = $sessionId
            ? AiChatSession::where('user_id', $userId)->findOrFail($sessionId)
            : AiChatSession::create(['user_id' => $userId, 'title' => (string) str($question)->limit(60)]);

        AiChatMessage::create([
            'chat_session_id' => $session->id,
            'role'            => 'user',
            'content'         => $question,
        ]);

        if (!$this->isConfigured()) {
            $reply = '⚠️ **AI Assistant belum dikonfigurasi.** Mintalah admin mengisi `GEMINI_API_KEY` di file `.env`, lalu jalankan `php artisan config:clear`.';
            $this->storeAssistant($session, $reply, ['error' => 'missing_api_key']);
            return ['session' => $session, 'reply' => $reply];
        }

        $contents = $this->buildHistoryContents($session);
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $reply = $this->runLoop($session, $contents);

        $this->storeAssistant($session, $reply, null);

        $session->touch();

        return ['session' => $session, 'reply' => $reply];
    }

    /**
     * Loop generateContent + function calling sampai model memberi jawaban teks.
     */
    protected function runLoop(AiChatSession $session, array $contents): string
    {
        $tools = [['functionDeclarations' => $this->functions()]];

        for ($i = 0; $i < self::MAX_ITERS; $i++) {
            $response = $this->generateContent($contents, $tools);

            if ($response === null) {
                return '⚠️ Gagal terhubung ke Gemini. Periksa koneksi / API key, lalu coba lagi.';
            }

            $parts = $response['candidates'][0]['content']['parts'] ?? [];
            $text = '';
            $calls = [];

            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $text .= $part['text'];
                }
                if (isset($part['functionCall'])) {
                    $calls[] = $part['functionCall'];
                }
            }

            if (empty($calls)) {
                return trim($text) ?: '⚠️ AI tidak memberikan jawaban. Coba pertanyaan yang lebih spesifik.';
            }

            // Eksekusi setiap function call & kirim balik hasilnya.
            $modelTurn = ['role' => 'model', 'parts' => array_map(fn ($c) => [
                'functionCall' => ['name' => $c['name'], 'args' => $c['args'] ?? new \stdClass()],
            ], $calls)];

            $toolTurn = ['role' => 'user', 'parts' => []];
            foreach ($calls as $call) {
                $result = $this->executeFunction($call['name'], $call['args'] ?? []);
                $toolTurn['parts'][] = [
                    'functionResponse' => [
                        'name'     => $call['name'],
                        'response' => ['result' => $result],
                    ],
                ];
            }

            $contents[] = $modelTurn;
            $contents[] = $toolTurn;
        }

        return '⚠️ Percakapan terlalu panjang untuk diproses. Silakan mulai sesi baru.';
    }

    /**
     * Panggil API Gemini generateContent (HTTP, tanpa streaming).
     */
    protected function generateContent(array $contents, array $tools): ?array
    {
        $url = rtrim((string) config('services.gemini.url'), '/')
            . '/models/' . config('services.gemini.model', 'gemini-3-flash-live')
            . ':generateContent';

        $payload = [
            'contents'     => $contents,
            'tools'        => $tools,
            'toolConfig'   => ['functionCallingConfig' => ['mode' => 'AUTO']],
            'systemInstruction' => ['parts' => [['text' => $this->systemPrompt()]]],
        ];

        $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.api_key')])
            ->timeout(60)
            ->post($url, $payload);

        if (!$response->successful()) {
            Log::error('GeminiAssistantService: HTTP ' . $response->status() . ' ' . substr($response->body(), 0, 500));
            return null;
        }

        return $response->json();
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah asisten analisis data BBM (bahan bakar minyak) internal PT Geo Dipa Energi.
Gunakan function calling untuk mengambil data REAL-TIME dari dashboard sebelum menjawab.
Jawab dalam Bahasa Indonesia, ringkas namun informatif, dengan format angka yang rapi (contoh: Rp 1.500.000).
Jika data kosong, katakan jujur bahwa tidak ada data. Jika pertanyaan di luar cakupan data yang tersedia, sampaikan keterbatasan Anda.
JANGAN membuat-buat angka. Selalu gunakan hasil fungsi sebagai sumber.
Catatan: fitur ini masih tahap pengembangan, jika diminta tolong sebutkan itu.
PROMPT;
    }

    /**
     * Ambil riwayat pesan (teks saja) dari sesi sebagai "contents".
     */
    protected function buildHistoryContents(AiChatSession $session): array
    {
        return $session->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->latest('id')
            ->limit(self::MAX_HISTORY)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (AiChatMessage $m) => [
                'role'  => $m->role === 'user' ? 'user' : 'model',
                'parts' => [['text' => $m->content]],
            ])
            ->all();
    }

    protected function storeAssistant(AiChatSession $session, string $reply, ?array $metadata): void
    {
        AiChatMessage::create([
            'chat_session_id' => $session->id,
            'role'            => 'assistant',
            'content'         => $reply,
            'metadata'        => $metadata,
        ]);
    }

    // ================================================================
    // FUNCTION DECLARATIONS (dikirim ke Gemini)
    // ================================================================

    protected function functions(): array
    {
        return [
            [
                'name'        => 'get_dashboard_stats',
                'description' => 'Ringkasan statistik dashboard BBM (total pengeluaran, jumlah permohonan per status, rata-rata nilai, kendaraan & driver teratas) untuk periode tertentu.',
                'parameters'  => [
                    'type'        => 'object',
                    'properties'  => [
                        'period' => ['type' => 'string', 'enum' => ['day', 'month', 'year'], 'description' => 'Periode: day (hari ini), month (bulan ini), year (tahun ini). Default month.'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name'        => 'get_driver_statistics',
                'description' => 'Statistik detail per driver/pemohon (total, rejected, completed, approved, pending, persentase pelaporan, rata-rata nominal).',
                'parameters'  => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name'        => 'get_vehicle_km_performance',
                'description' => 'Performa KM per kendaraan (rata-rata jarak antar pengisian, jumlah trip, odometer terakhir).',
                'parameters'  => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name'        => 'get_vehicle_list',
                'description' => 'Daftar kendaraan/alat terdaftar beserta unit/site, jenis BBM, kapasitas tangki, dan benchmark nominal.',
                'parameters'  => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name'        => 'get_gasoline_prices',
                'description' => 'Harga BBM per liter (PERTAMAX & PERTAMINA DEX) per region/provinsi dari Master Gasoline.',
                'parameters'  => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name'        => 'get_permohonan_transactions',
                'description' => 'Daftar transaksi permohonan BBM dengan filter opsional (status, jenis BBM, rentang tanggal, kendaraan, driver).',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'status'         => ['type' => 'string', 'enum' => ['pending', 'pending_vehicle_approval', 'approved', 'rejected', 'completed', 'cancelled'], 'description' => 'Filter status.'],
                        'gasoline_type'  => ['type' => 'string', 'description' => 'Filter jenis BBM, contoh: Pertamax atau Pertamina Dex.'],
                        'start_date'     => ['type' => 'string', 'description' => 'Tanggal mulai YYYY-MM-DD.'],
                        'end_date'       => ['type' => 'string', 'description' => 'Tanggal akhir YYYY-MM-DD.'],
                        'limit'          => ['type' => 'integer', 'description' => 'Jumlah maksimum baris. Default 20.'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    // ================================================================
    // EXECUTE FUNCTIONS (query real-time ke database)
    // ================================================================

    protected function executeFunction(string $name, array $args): array
    {
        return match ($name) {
            'get_dashboard_stats'              => $this->dashboardStats($args['period'] ?? 'month'),
            'get_driver_statistics'            => $this->driverStatistics(),
            'get_vehicle_km_performance'       => $this->vehicleKmPerformance(),
            'get_vehicle_list'                 => $this->vehicleList(),
            'get_gasoline_prices'              => $this->gasolinePrices(),
            'get_permohonan_transactions'      => $this->transactions($args),
            default                            => ['error' => "Fungsi tidak dikenal: {$name}"],
        };
    }

    protected function dashboardStats(string $period = 'month'): array
    {
        $now = Carbon::now();
        $start = match ($period) {
            'day'  => $now->copy()->startOfDay(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };
        $end = $now->copy()->endOfDay();

        $rows = Permohonan::with('consumerialTool')->whereBetween('request_date', [$start, $end])->get();
        $spending = $rows->whereIn('status', ['completed', 'approved']);

        $topVehicles = $spending->groupBy('consumerial_tools_id')
            ->map(function ($g) {
                $v = $g->first()->consumerialTool;
                return [
                    'vehicle' => $v ? "{$v->consumerial_code} - {$v->consumerial_name}" : 'Unknown',
                    'count'   => $g->count(),
                    'total'   => (float) $g->sum('bill_amounts'),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $topDrivers = $spending->groupBy('requester_id')
            ->map(function ($g) {
                return [
                    'driver' => $g->first()->requester->nama_lengkap ?? 'Unknown',
                    'count'  => $g->count(),
                    'total'  => (float) $g->sum('bill_amounts'),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $byStatus = $rows->groupBy('status')->map->count();

        return [
            'period'            => $period,
            'start_date'        => $start->format('Y-m-d'),
            'end_date'          => $end->format('Y-m-d'),
            'total_expense'     => round($spending->sum('bill_amounts'), 0),
            'total_permohonan'  => $rows->count(),
            'avg_request_value' => round($spending->avg('bill_amounts') ?? 0, 0),
            'counts_by_status'  => $byStatus,
            'top_vehicles'      => $topVehicles,
            'top_drivers'       => $topDrivers,
        ];
    }

    protected function driverStatistics(): array
    {
        return Permohonan::with('requester')->get()
            ->groupBy('requester_id')
            ->map(function ($requests) {
                $nominals = $requests->pluck('bill_amounts')->filter()->toArray();
                return [
                    'driver'        => $requests->first()->requester->nama_lengkap ?? 'Unknown',
                    'total'         => $requests->count(),
                    'rejected'      => $requests->where('status', 'rejected')->count(),
                    'completed'     => $requests->where('status', 'completed')->count(),
                    'approved'      => $requests->where('status', 'approved')->count(),
                    'pending'       => $requests->where('status', 'pending')->count(),
                    'report_rate'   => $requests->count() > 0
                        ? round(($requests->where('status', 'completed')->count() / $requests->count()) * 100, 1)
                        : 0,
                    'avg_nominal'   => !empty($nominals) ? round(array_sum($nominals) / count($nominals), 0) : 0,
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values()
            ->all();
    }

    protected function vehicleKmPerformance(): array
    {
        $proofs = Transaction_Proofs::with('permohonan.consumerialTool')
            ->whereHas('permohonan.consumerialTool', fn ($q) => $q->where('consumerial_type', 'KENDARAAN'))
            ->where('km_terakhir', '>', 0)
            ->orderBy('purchase_datetime', 'asc')
            ->get();

        $grouped = $proofs->groupBy('permohonan.consumerial_tools_id');
        $result = [];

        foreach ($grouped as $vehicleId => $records) {
            $vehicle = $records->first()->permohonan->consumerialTool;
            if (!$vehicle || $records->count() < 2) {
                continue;
            }

            $kmRecords = $records->map(fn ($p) => ['km' => $p->km_terakhir, 'date' => $p->purchase_datetime])
                ->sortBy('date')
                ->values()
                ->all();

            $diffs = [];
            for ($i = 1; $i < count($kmRecords); $i++) {
                $diff = $kmRecords[$i]['km'] - $kmRecords[$i - 1]['km'];
                if ($diff > 0 && $diff < 10000) {
                    $diffs[] = $diff;
                }
            }
            if (empty($diffs)) {
                continue;
            }

            $result[] = [
                'vehicle'       => "{$vehicle->consumerial_code} - {$vehicle->consumerial_name}",
                'avg_km'        => round(array_sum($diffs) / count($diffs)),
                'trips'         => count($diffs),
                'max_km'        => max($diffs),
                'min_km'        => min($diffs),
                'latest_odo'    => $kmRecords[count($kmRecords) - 1]['km'],
            ];
        }

        usort($result, fn ($a, $b) => $b['avg_km'] <=> $a['avg_km']);

        return array_slice($result, 0, 10);
    }

    protected function vehicleList(): array
    {
        return VehicleAndToolsConsumers::with('detail.site')
            ->where('consumerial_type', 'KENDARAAN')
            ->where('is_approved', true)
            ->where('is_active', true)
            ->orderBy('consumerial_name')
            ->get()
            ->map(fn ($v) => [
                'code'           => $v->consumerial_code,
                'name'           => $v->consumerial_name,
                'site'           => $v->detail?->site?->name ?? '-',
                'fuel'           => $v->detail?->jenis_bahan_bakar ?? '-',
                'tank_liter'     => $v->detail?->volume_tangki,
                'passengers'     => $v->detail?->kapasitas_penumpang,
                'benchmark'      => $v->detail?->nominal_full_tank,
            ])
            ->values()
            ->all();
    }

    protected function gasolinePrices(): array
    {
        return MasterGasoline::orderBy('province_slug')
            ->orderBy('fuel_type')
            ->get()
            ->map(fn ($m) => [
                'province'          => $m->province,
                'province_slug'     => $m->province_slug,
                'fuel_type'         => $m->fuel_type,
                'price_per_liter'   => $m->price_per_liter,
                'price_date'        => $m->price_date?->toDateString(),
            ])
            ->values()
            ->all();
    }

    protected function transactions(array $args): array
    {
        $query = Permohonan::with(['requester', 'consumerialTool']);

        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['gasoline_type'])) {
            $query->where('gasoline_type', $args['gasoline_type']);
        }
        if (!empty($args['start_date']) && !empty($args['end_date'])) {
            $query->whereBetween('request_date', [$args['start_date'] . ' 00:00:00', $args['end_date'] . ' 23:59:59']);
        }

        $limit = isset($args['limit']) ? min((int) $args['limit'], 50) : 20;

        return $query->orderByDesc('request_date')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'request_number' => $p->request_number,
                'date'           => $p->request_date?->toDateString(),
                'driver'         => $p->requester->nama_lengkap ?? 'Unknown',
                'vehicle'        => $p->consumerialTool
                    ? "{$p->consumerialTool->consumerial_code} - {$p->consumerialTool->consumerial_name}"
                    : '-',
                'gasoline_type'  => $p->gasoline_type,
                'nominal'        => $p->bill_amounts,
                'status'         => $p->status,
            ])
            ->values()
            ->all();
    }
}
