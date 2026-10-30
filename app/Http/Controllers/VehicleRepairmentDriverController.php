<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementTransaction;
use App\Models\VprtDetail;
use App\Models\VehicleAndToolsConsumers;

/**
 * Controller untuk role DRIVER (requester):
 * 1. Acknowledge hasil pengecekan dari dispatcher
 * 2. Lihat jadwal replacement yang di-assign
 * 3. Lapor transaksi service (beli sparepart + bukti struk)
 * 4. Riwayat transaksi service
 */
class VehicleRepairmentDriverController extends Controller
{
    // ════════════════════════════════════════════════════════════════
    //  CHECKING ACKNOWLEDGEMENT - INDEX
    //  View: driver/checking-acknowledgement/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function acknowledgementIndex(Request $request)
    {
        $driverId = Auth::id();
        $tab = $request->get('tab', 'pending');

        $query = VehicleCheckingDetail::with(['vehicleChecking.vehicle', 'dispatcher', 'itemResults'])
            ->forDriver($driverId)
            ->orderByDesc('checked_at');

        if ($tab === 'pending') {
            $query->unacknowledged();
        } else {
            $query->acknowledged();
        }

        if ($request->filled('vehicle_id')) {
            $query->whereHas('vehicleChecking', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('checked_at', '>=', $request->date_from);
        }

        $checkings = $query->paginate(15)->withQueryString();

        $pendingCount = VehicleCheckingDetail::forDriver($driverId)->unacknowledged()->count();
        $acknowledgedCount = VehicleCheckingDetail::forDriver($driverId)->acknowledged()->count();

        $vehicleIds = VehicleCheckingDetail::forDriver($driverId)
            ->with('vehicleChecking')
            ->get()
            ->pluck('vehicleChecking.vehicle_id')
            ->unique();
        $vehicles = VehicleAndToolsConsumers::whereIn('id', $vehicleIds)->orderBy('consumerial_code')->get();

        return view('requester.checking-acknowledgement.index', compact(
            'checkings', 'pendingCount', 'acknowledgedCount', 'vehicles'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  CHECKING ACKNOWLEDGEMENT - SHOW
    //  View: driver/checking-acknowledgement/show.blade.php
    // ════════════════════════════════════════════════════════════════
    public function acknowledgementShow($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::with([
            'vehicleChecking.vehicle',
            'dispatcher',
            'currentDriver',
            'itemResults.vehiclePart',
            'partsReplacement.replacementDetails',
        ])
        ->where('checking_detail_code', $checkingDetailCode)
        ->where('current_driver_id', Auth::id())
        ->firstOrFail();

        if ($detail->type === 'routine') {
            $summary = [
                'ok' => $detail->itemResults->whereIn('status', ['ok', 'baik'])->count(),
                'ng' => $detail->itemResults->whereNotIn('status', ['ok', 'baik'])->count(),
                'needs_replacement' => $detail->itemResults->where('needs_replacement', true)->count(),
            ];
        } else {
            $summary = [
                'baik'  => $detail->itemResults->where('status', 'baik')->count(),
                'cukup' => $detail->itemResults->where('status', 'cukup')->count(),
                'kurang'=> $detail->itemResults->where('status', 'kurang')->count(),
                'tidak' => $detail->itemResults->where('status', 'tidak')->count(),
                'needs_replacement' => $detail->itemResults->where('needs_replacement', true)->count(),
            ];
        }

        $damagePhotos = collect();

        return view('requester.checking-acknowledgement.show', compact('detail', 'summary', 'damagePhotos'));
    }

    // ════════════════════════════════════════════════════════════════
    //  ACKNOWLEDGE - PUT
    // ════════════════════════════════════════════════════════════════
    public function acknowledge($checkingDetailCode)
    {
        $detail = VehicleCheckingDetail::where('checking_detail_code', $checkingDetailCode)
            ->where('current_driver_id', Auth::id())
            ->where('driver_acknowledged', false)
            ->firstOrFail();

        $detail->acknowledge();

        return redirect()
            ->route('requester.checking-acknowledgement.show', $checkingDetailCode)
            ->with('success', 'Pengecekan berhasil di-acknowledge. Terima kasih.');
    }

    // ════════════════════════════════════════════════════════════════
    //  REPLACEMENT SCHEDULE - INDEX
    //  View: driver/replacement-schedule/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function replacementScheduleIndex()
    {
        $driverId = Auth::id();

        $vprs = VehiclePartsReplacement::with(['replacementDetails', 'transaction', 'vehicle'])
            ->where(function ($q) use ($driverId) {
                $q->where('desired_driver_id', $driverId)
                  ->orWhere('last_driver_id', $driverId);
            })
            ->whereIn('status', ['approved', 'scheduled', 'in_service', 'completed'])
            ->orderByRaw("FIELD(status, 'in_service', 'scheduled', 'approved', 'completed')")
            ->orderByDesc('scheduled_date')
            ->get();

        $schedules = $vprs->map(function ($vpr) {
            $trx = $vpr->transaction;
            $deadlineDate = $trx?->deadline_date ?? ($vpr->scheduled_date ? $vpr->scheduled_date->copy()->addDays(3) : null);
            $daysLeft = $deadlineDate ? now()->startOfDay()->diffInDays($deadlineDate, false) : 99;

            $group = 'upcoming';
            if ($vpr->status === 'completed') $group = 'completed';
            elseif ($daysLeft < 0) $group = 'overdue';
            elseif ($vpr->status === 'in_service') $group = 'active';

            return (object) [
                'partsReplacement' => $vpr,
                'deadline_date'    => $deadlineDate,
                'service_location' => $trx?->service_location ?? $vpr->service_location,
                'service_date'     => $trx?->service_date ?? $vpr->scheduled_date,
                'transaction'      => $trx,
                'group'            => $group,
            ];
        });

        $counts = [
            'overdue'   => $schedules->where('group', 'overdue')->count(),
            'active'    => $schedules->whereIn('group', ['active', 'upcoming'])->count(),
            'completed' => $schedules->where('group', 'completed')->count(),
        ];

        return view('requester.replacement-schedule.index', compact('schedules', 'counts'));
    }

    // ════════════════════════════════════════════════════════════════
    //  SERVICE TRANSACTION - CREATE
    //  View: driver/service-transaction/create.blade.php
    // ════════════════════════════════════════════════════════════════
    public function transactionCreate($vprCode)
    {
        $replacement = VehiclePartsReplacement::with(['replacementDetails', 'vehicle', 'transaction'])
            ->where('vpr_code', $vprCode)
            ->where(function ($q) {
                $q->where('desired_driver_id', Auth::id())
                  ->orWhere('last_driver_id', Auth::id());
            })
            ->whereIn('status', ['approved', 'scheduled', 'in_service'])
            ->firstOrFail();

        if ($replacement->transaction) {
            return redirect()
                ->route('requester.service-transaction.index')
                ->with('error', 'Transaksi untuk VPR ini sudah pernah dilaporkan.');
        }

        return view('requester.service-transaction.create', compact('replacement'));
    }

    // ════════════════════════════════════════════════════════════════
    //  SERVICE TRANSACTION - STORE
    // ════════════════════════════════════════════════════════════════
    public function transactionStore(Request $request, $vprCode)
    {

    // dd($request->all());

        $request->validate([
            'service_location' => 'required|string|max:255',
            'service_date'     => 'required|date|before_or_equal:today',
            'proof_photo'      => 'required|image|max:5120',
            'costs'            => 'required|array|min:1',
            'costs.*.item_name'  => 'required|string|max:100',
            'costs.*.quantity'   => 'required|integer|min:1',
            'costs.*.unit_price' => 'required|numeric|min:0',
            'total_amount'     => 'required|numeric|min:1',
        ], [
            'service_location.required' => 'Lokasi service/bengkel wajib diisi',
            'service_date.required'     => 'Tanggal service wajib diisi',
            'proof_photo.required'      => 'Foto bukti struk/nota wajib diupload',
            'proof_photo.image'         => 'File harus berupa gambar',
            'proof_photo.max'           => 'Ukuran foto maksimal 5MB',
            'costs.required'            => 'Minimal 1 item biaya harus diisi',
            'total_amount.min'          => 'Total biaya harus lebih dari Rp 0',
        ]);

        $replacement = VehiclePartsReplacement::where('vpr_code', $vprCode)
            ->where(function ($q) {
                $q->where('desired_driver_id', Auth::id())
                  ->orWhere('last_driver_id', Auth::id());
            })
            ->whereIn('status', ['approved', 'scheduled', 'in_service'])
            ->firstOrFail();

        if ($replacement->transaction) {
            return redirect()->back()->with('error', 'Transaksi sudah pernah dilaporkan.');
        }

        try {
            DB::beginTransaction();

            $photoPath = $request->file('proof_photo')->store('vprt-proofs', 'public');

            $serviceDate = Carbon::parse($request->service_date);
            $deadlineDate = $serviceDate->copy()->addDays(3);

            $transaction = VehiclePartsReplacementTransaction::create([
                'vprt_code'                     => VehiclePartsReplacementTransaction::generateCode($replacement->vpr_code),
                'vehicle_parts_replacement_id'  => $replacement->id,
                'vpr_code'                      => $replacement->vpr_code,
                'driver_id'                     => Auth::id(),
                'service_location'              => $request->service_location,
                'service_date'                  => $serviceDate,
                'total_amount'                  => $request->total_amount,
                'proof_photo'                   => $photoPath,
                'deadline_date'                 => $deadlineDate,
                'reported_at'                   => now(),
                'is_overdue'                    => now()->gt($deadlineDate),
            ]);

            $calculatedTotal = 0;
            foreach ($request->costs as $idx => $cost) {
                $qty = (int) $cost['quantity'];
                $unitPrice = (float) $cost['unit_price'];
                $totalPrice = $qty * $unitPrice;
                $calculatedTotal += $totalPrice;

                VprtDetail::create([
                    'vprt_detail_code' => VprtDetail::generateCode($transaction->id, $idx + 1),
                    'vprt_id'          => $transaction->id,
                    'part_name'        => $cost['item_name'],
                    'quantity'         => $qty,
                    'unit_price'       => $unitPrice,
                    'total_price'      => $totalPrice,
                ]);
            }

            if (abs($calculatedTotal - $transaction->total_amount) > 0.01) {
                $transaction->update(['total_amount' => $calculatedTotal]);
            }

            if ($replacement->status !== 'in_service') {
                $replacement->markInService();
            }

            DB::commit();

            return redirect()
                ->route('requester.service-transaction.index')
                ->with('success', 'Laporan transaksi service berhasil dikirim!');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            return redirect()->back()->withInput()
                ->with('error', safe_exception_message($e, 'Gagal menyimpan transaksi. Silakan coba lagi atau hubungi admin.'));
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  SERVICE TRANSACTION - INDEX (riwayat)
    //  View: driver/service-transaction/index.blade.php
    // ════════════════════════════════════════════════════════════════
    public function transactionIndex(Request $request)
    {
        $driverId = Auth::id();

        $query = VehiclePartsReplacementTransaction::with(['replacement.vehicle', 'details'])
            ->forDriver($driverId)
            ->orderByDesc('service_date');

        if ($request->filled('vehicle_id')) {
            $query->whereHas('replacement', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('service_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('service_date', '<=', $request->date_to);
        }

        $transactions = $query->paginate(15)->withQueryString();
        $totalSpent = VehiclePartsReplacementTransaction::forDriver($driverId)->sum('total_amount');

        $vehicleIds = VehiclePartsReplacementTransaction::forDriver($driverId)
            ->join('vehicle_parts_replacements', 'vehicle_parts_replacement_transactions.vehicle_parts_replacement_id', '=', 'vehicle_parts_replacements.id')
            ->pluck('vehicle_parts_replacements.vehicle_id')
            ->unique();
        $vehicles = VehicleAndToolsConsumers::whereIn('id', $vehicleIds)->orderBy('consumerial_code')->get();

        return view('requester.service-transaction.index', compact('transactions', 'totalSpent', 'vehicles'));
    }
}
