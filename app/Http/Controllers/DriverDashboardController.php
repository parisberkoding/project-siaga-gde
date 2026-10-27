<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;
use App\Models\SuratSpd;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePartsReplacement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard khusus Driver (role RBAC `requester`).
 *
 * Kenapa terpisah dari dashboard modul:
 * Dashboard modul (BBM/SPD/Kendaraan) adalah dashboard ANALITIK tingkat unit —
 * isinya data seluruh driver di unit itu (top 10 pengeluaran, pivot, statistik
 * per driver). Halaman itu milik superadmin/GA/manager yang tugasnya memantau,
 * bukan milik driver.
 *
 * Driver butuh halaman yang berbeda bentuknya: "apa pekerjaan SAYA hari ini".
 * Karena itu dashboard ini hanya menampilkan baris yang memang milik user yang
 * login (permohonan BBM-nya, surat tugas-nya, LPJ yang belum ia laporkan,
 * pengecekan kendaraan yang menunggu ia acknowledge, dan jadwal service yang
 * di-assign kepadanya). Tidak ada agregasi lintas driver di sini.
 */
class DriverDashboardController extends Controller
{
    /**
     * Status permohonan BBM yang masih dianggap "aktif" (belum selesai).
     */
    private const STATUS_BBM_AKTIF = ['pending', 'pending_vehicle_approval', 'approved'];

    public function index(Request $request): View
    {
        $user = $request->user();

        /*
        |----------------------------------------------------------------------
        | Permohonan BBM milik driver
        |----------------------------------------------------------------------
        */
        $bbmQuery = Permohonan::query()->where('requester_id', $user->id);

        $bbmCounts = (clone $bbmQuery)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bbmAktif = (clone $bbmQuery)
            ->with('vehicle')
            ->whereIn('status', self::STATUS_BBM_AKTIF)
            ->orderByDesc('request_date')
            ->limit(5)
            ->get();

        $bbmBulanIni = (clone $bbmQuery)
            ->whereBetween('request_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $bbmMenunggu = (int) ($bbmCounts['pending'] ?? 0) + (int) ($bbmCounts['pending_vehicle_approval'] ?? 0);
        $bbmDisetujui = (int) ($bbmCounts['approved'] ?? 0);

        $bbmStats = [
            'aktif' => $bbmMenunggu + $bbmDisetujui,
            'menunggu' => $bbmMenunggu,
            'disetujui' => $bbmDisetujui,
            'selesai' => (int) ($bbmCounts['completed'] ?? 0),
            'bulan_ini' => $bbmBulanIni,
        ];

        /*
        |----------------------------------------------------------------------
        | SPD yang masih berjalan + LPJ yang belum dilaporkan
        |----------------------------------------------------------------------
        | Sumber datanya sama dengan halaman "SPD Berjalan" milik driver, agar
        | angka di dashboard tidak pernah berbeda dengan daftar detailnya.
        */
        $spdBerjalan = SuratSpd::query()
            ->forUser($user->id)
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with('vehicle')
            ->orderBy('tanggal_kembali')
            ->get();

        $spdTerlambat = $spdBerjalan
            ->filter(fn (SuratSpd $surat) => $surat->isLpjDeadlineOverdue())
            ->values();

        /*
        |----------------------------------------------------------------------
        | Kendaraan: acknowledge pengecekan & jadwal service
        |----------------------------------------------------------------------
        */
        $ackPending = VehicleCheckingDetail::query()
            ->with(['vehicleChecking.vehicle', 'dispatcher'])
            ->forDriver($user->id)
            ->unacknowledged()
            ->orderByDesc('checked_at')
            ->limit(5)
            ->get();

        $ackPendingCount = VehicleCheckingDetail::query()
            ->forDriver($user->id)
            ->unacknowledged()
            ->count();

        $vprAktif = VehiclePartsReplacement::query()
            ->with('vehicle')
            ->where(function ($query) use ($user) {
                $query->where('desired_driver_id', $user->id)
                    ->orWhere('last_driver_id', $user->id);
            })
            ->whereIn('status', ['approved', 'scheduled', 'in_service'])
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get();

        return view('driver.dashboard', [
            'title' => 'Dashboard Driver — SIAGA',
            'bbmStats' => $bbmStats,
            'bbmAktif' => $bbmAktif,
            'spdBerjalan' => $spdBerjalan->take(5),
            'spdBerjalanCount' => $spdBerjalan->count(),
            'spdTerlambatCount' => $spdTerlambat->count(),
            'ackPending' => $ackPending,
            'ackPendingCount' => $ackPendingCount,
            'vprAktif' => $vprAktif,
        ]);
    }
}
