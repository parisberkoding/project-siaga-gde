<?php

namespace App\Services\AI\DataMart;

use App\Models\LpjPenggantian;
use App\Models\LunchDistribution;
use App\Models\Permohonan;
use App\Models\Site;
use App\Models\SuratSpd;
use App\Models\Unit;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehiclePartsReplacement;
use Illuminate\Support\Facades\Cache;

/**
 * Membangun konteks JSON per-Unit untuk AI Context Knowledge.
 *
 * Struktur: DM_<UnitCode> -> {SPD, BBM, LPJ, LUNCH, VEHICLE, PARTS}
 * Setiap modul: { overview, sub_overview, detailing }
 *
 * overview     : agregat (COUNT, SUM, AVG) — ringan.
 * sub_overview : breakdown per dimensi (driver, site, status).
 * detailing    : maksimal 10 record terbaru (anti LLM token bloat).
 */
class DataMartContextBuilder
{
    protected const DETAILING_LIMIT = 30;

    /**
     * Bangun JSON konteks lengkap untuk satu Unit.
     *
     * @return array<string, array<string, mixed>>
     */
    public function buildForUnit(int $unitId): array
    {
        $unit = Unit::with('sites')->findOrFail($unitId);
        $unitCode = $unit->code;
        $siteIds = $unit->sites->pluck('id')->all();

        return [
            "DM_{$unitCode}" => [
                'SPD'     => $this->buildSpd($siteIds),
                'BBM'     => $this->buildBbm($siteIds),
                'LPJ'     => $this->buildLpj($siteIds),
                'LUNCH'   => $this->buildLunch($siteIds),
                'VEHICLE' => $this->buildVehicle($siteIds),
                'PARTS'   => $this->buildParts($siteIds),
            ],
        ];
    }

    /**
     * Bangun konteks untuk semua Unit aktif sekaligus.
     *
     * @return array<string, array<string, mixed>>
     */
    public function buildAll(): array
    {
        $result = [];

        foreach (Unit::where('is_active', true)->get() as $unit) {
            $result = array_merge($result, $this->buildForUnit($unit->id));
        }

        return $result;
    }

    /**
     * Bangun dari cache harian, atau bangun ulang jika expired.
     *
     * @return array<string, mixed>
     */
    public function buildCached(int $unitId): array
    {
        $unit = Unit::findOrFail($unitId);
        $cacheKey = "data_mart_context_{$unitId}_".now()->toDateString();
        $ttl = (int) config('ai.datamart.context_cache_ttl', 86400);

        return Cache::remember($cacheKey, $ttl, function () use ($unitId) {
            return $this->buildForUnit($unitId);
        });
    }

    // ────────────────────────────────────────────────────────────────────────
    // SPD
    // ────────────────────────────────────────────────────────────────────────

    protected function buildSpd(array $siteIds): array
    {
        $base = SuratSpd::query()
            ->whereHas('vehicle.detail', fn ($q) => $q->whereIn('site_id', $siteIds));

        $overview = (clone $base)->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(total_budget), 0) as total_budget,
            COALESCE(AVG(total_budget), 0) as avg_budget,
            COALESCE(AVG(lama_perjalanan_hari), 0) as avg_days
        ")->first();

        $subOverView = (clone $base)
            ->join('users', 'surat_spds.user_id', '=', 'users.id')
            ->selectRaw('users.nama_lengkap as driver, COUNT(*) as total')
            ->groupBy('users.nama_lengkap')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->pluck('total', 'driver')
            ->all();

        $detailing = (clone $base)
            ->with(['user:id,nama_lengkap', 'vehicle:id,consumerial_code,consumerial_name'])
            ->select('surat_spds.id', 'surat_spds.nomor_surat', 'surat_spds.user_id',
                'surat_spds.vehicle_id', 'surat_spds.tanggal_berangkat', 'surat_spds.tempat_tujuan',
                'surat_spds.total_budget', 'surat_spds.status')
            ->latest('tanggal_berangkat')
            ->limit(self::DETAILING_LIMIT)
            ->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'nomor_surat'    => $s->nomor_surat,
                'driver'         => $s->user?->nama_lengkap,
                'vehicle'        => $s->vehicle?->consumerial_code.' - '.$s->vehicle?->consumerial_name,
                'tanggal'        => $s->tanggal_berangkat?->toDateString(),
                'tempat_tujuan'  => $s->tempat_tujuan,
                'total_budget'   => (float) $s->total_budget,
                'status'         => $s->status,
            ])->all();

        return [
            'overview'     => [
                'total'      => (int) $overview->total,
                'total_budget' => (float) $overview->total_budget,
                'avg_budget'   => (float) round($overview->avg_budget, 2),
                'avg_days'    => (float) round($overview->avg_days, 2),
            ],
            'sub_overview' => $subOverView,
            'detailing'    => $detailing,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // BBM (Permohonan)
    // ────────────────────────────────────────────────────────────────────────

    protected function buildBbm(array $siteIds): array
    {
        $base = Permohonan::query()
            ->whereHas('consumerialTool.detail', fn ($q) => $q->whereIn('site_id', $siteIds));

        $overview = (clone $base)->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(bill_amounts), 0) as total_nominal,
            COALESCE(AVG(bill_amounts), 0) as avg_nominal
        ")->first();

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->all();

        $byFuel = (clone $base)
            ->selectRaw('gasoline_type, COUNT(*) as total')
            ->groupBy('gasoline_type')
            ->get()
            ->pluck('total', 'gasoline_type')
            ->all();

        $detailing = (clone $base)
            ->with(['requester:id,nama_lengkap',
                    'consumerialTool:id,consumerial_code,consumerial_name',
                    'consumerialTool.detail.site:id,name'])
            ->select('permohonans.id', 'permohonans.request_number', 'permohonans.requester_id',
                'permohonans.consumerial_tools_id', 'permohonans.request_date',
                'permohonans.gasoline_type', 'permohonans.bill_amounts', 'permohonans.status')
            ->latest('request_date')
            ->limit(self::DETAILING_LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'request_number' => $p->request_number,
                'driver'         => $p->requester?->nama_lengkap,
                'vehicle'        => $p->consumerialTool?->consumerial_code.' - '.$p->consumerialTool?->consumerial_name,
                'site'           => $p->consumerialTool?->detail?->site?->name,
                'tanggal'        => $p->request_date?->toDateString(),
                'gasoline_type'  => $p->gasoline_type,
                'bill_amounts'   => (float) $p->bill_amounts,
                'status'         => $p->status,
            ])->all();

        return [
            'overview'     => [
                'total'        => (int) $overview->total,
                'total_nominal' => (float) $overview->total_nominal,
                'avg_nominal'   => (float) round($overview->avg_nominal, 2),
            ],
            'sub_overview' => [
                'by_status' => $byStatus,
                'by_fuel'   => $byFuel,
            ],
            'detailing'    => $detailing,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // LPJ
    // ────────────────────────────────────────────────────────────────────────

    protected function buildLpj(array $siteIds): array
    {
        $base = LpjPenggantian::query()
            ->whereHas('vehicle.detail', fn ($q) => $q->whereIn('site_id', $siteIds));

        $overview = (clone $base)->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(total_pengeluaran), 0) as total_pengeluaran,
            COALESCE(AVG(total_pengeluaran), 0) as avg_pengeluaran,
            COALESCE(SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END), 0) as late_count,
            COALESCE(SUM(CASE WHEN is_over_budget = 1 THEN 1 ELSE 0 END), 0) as over_budget_count
        ")->first();

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->all();

        $detailing = (clone $base)
            ->with(['user:id,nama_lengkap',
                    'vehicle:id,consumerial_code,consumerial_name',
                    'suratSpd:id,melayani_bidang'])
            ->select('lpj_penggantians.id', 'lpj_penggantians.lpj_number',
                'lpj_penggantians.user_id', 'lpj_penggantians.vehicle_id',
                'lpj_penggantians.surat_spd_id', 'lpj_penggantians.tanggal_lpj',
                'lpj_penggantians.total_pengeluaran', 'lpj_penggantians.is_late',
                'lpj_penggantians.is_over_budget', 'lpj_penggantians.status')
            ->latest('tanggal_lpj')
            ->limit(self::DETAILING_LIMIT)
            ->get()
            ->map(fn ($l) => [
                'id'               => $l->id,
                'lpj_number'       => $l->lpj_number,
                'driver'           => $l->user?->nama_lengkap,
                'vehicle'          => $l->vehicle?->consumerial_code.' - '.$l->vehicle?->consumerial_name,
                'bidang'           => $l->suratSpd?->melayani_bidang,
                'tanggal'          => $l->tanggal_lpj?->toDateString(),
                'total_pengeluaran' => (float) $l->total_pengeluaran,
                'is_late'          => (bool) $l->is_late,
                'is_over_budget'   => (bool) $l->is_over_budget,
                'status'           => $l->status,
            ])->all();

        return [
            'overview'     => [
                'total'            => (int) $overview->total,
                'total_pengeluaran' => (float) $overview->total_pengeluaran,
                'avg_pengeluaran'   => (float) round($overview->avg_pengeluaran, 2),
                'late'              => (int) $overview->late_count,
                'over_budget'       => (int) $overview->over_budget_count,
            ],
            'sub_overview' => ['by_status' => $byStatus],
            'detailing'    => $detailing,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // LUNCH
    // ────────────────────────────────────────────────────────────────────────

    protected function buildLunch(array $siteIds): array
    {
        $base = LunchDistribution::query()
            ->whereIn('site_id', $siteIds);

        $overview = (clone $base)->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(employee_count), 0) as total_employee,
            COALESCE(SUM(guest_count), 0) as total_guest,
            COALESCE(SUM(total_count), 0) as total_porsi,
            COALESCE(AVG(total_count), 0) as avg_porsi
        ")->first();

        $bySite = (clone $base)
            ->join('sites', 'lunch_distributions.site_id', '=', 'sites.id')
            ->selectRaw('sites.name as site, SUM(lunch_distributions.total_count) as total')
            ->groupBy('sites.name')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'site')
            ->all();

        $detailing = (clone $base)
            ->with('site:id,name')
            ->select('lunch_distributions.id', 'lunch_distributions.site_id',
                'lunch_distributions.distribution_date', 'lunch_distributions.employee_count',
                'lunch_distributions.guest_count', 'lunch_distributions.total_count',
                'lunch_distributions.is_locked')
            ->latest('distribution_date')
            ->limit(self::DETAILING_LIMIT)
            ->get()
            ->map(fn ($d) => [
                'id'               => $d->id,
                'site'             => $d->site?->name,
                'tanggal'          => $d->distribution_date?->toDateString(),
                'employee_count'   => (int) $d->employee_count,
                'guest_count'      => (int) $d->guest_count,
                'total_count'      => (int) $d->total_count,
                'is_locked'        => (bool) $d->is_locked,
            ])->all();

        return [
            'overview'     => [
                'total'         => (int) $overview->total,
                'total_employee' => (int) $overview->total_employee,
                'total_guest'    => (int) $overview->total_guest,
                'total_porsi'    => (int) $overview->total_porsi,
                'avg_porsi'      => (float) round($overview->avg_porsi, 2),
            ],
            'sub_overview' => ['by_site' => $bySite],
            'detailing'    => $detailing,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // VEHICLE
    // ────────────────────────────────────────────────────────────────────────

    protected function buildVehicle(array $siteIds): array
    {
        $base = VehicleAndToolsConsumers::query()
            ->whereHas('detail', fn ($q) => $q->whereIn('site_id', $siteIds));

        $overview = (clone $base)->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active,
            COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) as inactive,
            COALESCE(SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END), 0) as approved
        ")->first();

        $byType = (clone $base)
            ->selectRaw('consumerial_type as type, COUNT(*) as total')
            ->groupBy('consumerial_type')
            ->get()
            ->pluck('total', 'type')
            ->all();

        $byFuel = (clone $base)
            ->join('vehicle_details', 'vehicle_and_tools_consumers.id', '=', 'vehicle_details.vehicle_and_tools_consumer_id')
            ->selectRaw('vehicle_details.jenis_bahan_bakar as fuel, COUNT(*) as total')
            ->whereNotNull('vehicle_details.jenis_bahan_bakar')
            ->groupBy('vehicle_details.jenis_bahan_bakar')
            ->get()
            ->pluck('total', 'fuel')
            ->all();

        $detailing = (clone $base)
            ->with(['detail.site:id,name'])
            ->select('vehicle_and_tools_consumers.id', 'vehicle_and_tools_consumers.consumerial_code',
                'vehicle_and_tools_consumers.consumerial_name', 'vehicle_and_tools_consumers.consumerial_type',
                'vehicle_and_tools_consumers.is_active', 'vehicle_and_tools_consumers.is_approved')
            ->latest()
            ->limit(self::DETAILING_LIMIT)
            ->get()
            ->map(fn ($v) => [
                'id'               => $v->id,
                'code'             => $v->consumerial_code,
                'name'             => $v->consumerial_name,
                'type'             => $v->consumerial_type,
                'site'             => $v->detail?->site?->name,
                'fuel_type'        => $v->detail?->jenis_bahan_bakar,
                'tank_liter'       => $v->detail?->volume_tangki ? (float) $v->detail->volume_tangki : null,
                'is_active'        => (bool) $v->is_active,
                'is_approved'      => (bool) $v->is_approved,
            ])->all();

        return [
            'overview'     => [
                'total'    => (int) $overview->total,
                'active'   => (int) $overview->active,
                'inactive' => (int) $overview->inactive,
                'approved' => (int) $overview->approved,
            ],
            'sub_overview' => [
                'by_type' => $byType,
                'by_fuel' => $byFuel,
            ],
            'detailing'    => $detailing,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // PARTS (Suku Cadang)
    // ────────────────────────────────────────────────────────────────────────

    protected function buildParts(array $siteIds): array
    {
        $base = VehiclePartsReplacement::query()
            ->whereHas('vehicle.detail', fn ($q) => $q->whereIn('site_id', $siteIds));

        $overview = (clone $base)
            ->leftJoin('vehicle_parts_replacement_transactions as t',
                'vehicle_parts_replacements.id', '=', 't.vehicle_parts_replacement_id')
            ->selectRaw("
                COUNT(DISTINCT vehicle_parts_replacements.id) as total,
                COALESCE(SUM(t.total_amount), 0) as total_amount,
                COALESCE(AVG(t.total_amount), 0) as avg_amount,
                COALESCE(SUM(CASE WHEN t.is_overdue = 1 THEN 1 ELSE 0 END), 0) as overdue_count
            ")->first();

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->all();

        $detailing = (clone $base)
            ->with(['vehicle:id,consumerial_code,consumerial_name',
                    'transaction.driver:id,nama_lengkap'])
            ->select('vehicle_parts_replacements.id', 'vehicle_parts_replacements.vpr_code',
                'vehicle_parts_replacements.vehicle_id', 'vehicle_parts_replacements.nopol',
                'vehicle_parts_replacements.status', 'vehicle_parts_replacements.scheduled_date',
                'vehicle_parts_replacements.service_location')
            ->latest('scheduled_date')
            ->limit(self::DETAILING_LIMIT)
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'vpr_code'         => $r->vpr_code,
                'vehicle'          => $r->vehicle?->consumerial_code.' - '.$r->vehicle?->consumerial_name,
                'nopol'            => $r->nopol,
                'driver'           => $r->transaction?->driver?->nama_lengkap,
                'scheduled_date'   => $r->scheduled_date?->toDateString(),
                'service_location' => $r->service_location,
                'total_amount'     => (float) ($r->transaction?->total_amount ?? 0),
                'status'           => $r->status,
            ])->all();

        return [
            'overview'     => [
                'total'        => (int) $overview->total,
                'total_amount'  => (float) $overview->total_amount,
                'avg_amount'    => (float) round($overview->avg_amount, 2),
                'overdue'       => (int) $overview->overdue_count,
            ],
            'sub_overview' => ['by_status' => $byStatus],
            'detailing'    => $detailing,
        ];
    }
}