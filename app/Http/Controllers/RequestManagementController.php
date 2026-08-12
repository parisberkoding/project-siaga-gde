<?php

namespace App\Http\Controllers;

use App\Models\Approved_Request;
use App\Models\GasolineType;
use App\Models\Permohonan;
use App\Models\Transaction_Proofs;
use App\Models\UserSlotAdjustment;
use App\Models\VehicleAndToolsConsumers;
use App\Services\FuelBenchmarkService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestManagementController extends Controller
{
    /**
     * =========================================
     * Controller bagian Requester
     * =========================================
     */
    public function requester_index()
    {
        $title = 'Dashboard Requester';
        $user = Auth::user();

        // ✅ Get active requests (pending + pending_vehicle_approval + approved)
        $activeRequests = Permohonan::where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'pending_vehicle_approval', 'approved'])
            ->orderBy('request_date', 'desc')
            ->get();

        $activeCount = $activeRequests->count();

        // Get stats
        $pendingCount = Permohonan::where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'pending_vehicle_approval'])
            ->count();

        $approvedCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $completedCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $rejectedCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        $cancelledCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $totalRequests = Permohonan::where('requester_id', $user->id)->count();

        // ✅ Get active slots (max 5) - load with vehicle relationship
        $activeSlots = [];
        foreach ($activeRequests->take(5) as $request) {
            $activeSlots[] = $request;
        }

        // Get all vehicles (only approved ones) — + detail untuk label "Plat - Merk" & budget
        $vehicles = VehicleAndToolsConsumers::with('detail.site')
            ->where('is_approved', true)
            ->where('is_active', true)
            ->orderBy('consumerial_type')
            ->orderBy('consumerial_name')
            ->get();

        // Get gasoline types
        $gasolineTypes = GasolineType::all();

        // Opsi nominal fallback (tanpa benchmark) untuk kendaraan yang datanya belum lengkap
        $fallbackOptions = FuelBenchmarkService::optionsForBenchmark(null);

        // ✅ Calculate max slots & on-time rate
        $maxSlots = $this->calculateUserMaxSlots($user->id);

        // Calculate on-time rate untuk dashboard
        $completedRequests = Permohonan::where('requester_id', $user->id)
            ->where('status', 'completed')
            ->with(['approvedRequest', 'transactionProof'])
            ->get();

        $onTimeCount = 0;
        $totalWithProof = 0;

        foreach ($completedRequests as $req) {
            if ($req->transactionProof && $req->approvedRequest) {
                $totalWithProof++;
                if ($req->transactionProof->created_at->lessThan($req->approvedRequest->valid_until)) {
                    $onTimeCount++;
                }
            }
        }

        $onTimeRate = $totalWithProof > 0 ? round(($onTimeCount / $totalWithProof) * 100) : 0;

        return view('requester.view_request_bbm', compact(
            'title',
            'activeRequests',
            'activeCount',
            'pendingCount',
            'approvedCount',
            'completedCount',
            'rejectedCount',
            'cancelledCount',
            'totalRequests',
            'activeSlots',
            'vehicles',
            'gasolineTypes',
            'maxSlots',
            'onTimeRate',
            'onTimeCount',
            'totalWithProof',
            'fallbackOptions'
        ));
    }

    public function requester_create(Request $request)
    {
        $user = Auth::user();

        $this->checkAndExpireVouchers();

        // ✅ Validasi: Max slots
        $maxSlots = $this->calculateUserMaxSlots($user->id);
        $activeCount = Permohonan::where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'pending_vehicle_approval', 'approved'])
            ->count();

        if ($activeCount >= $maxSlots) {
            return back()->with('error', "Anda sudah memiliki {$maxSlots} permohonan aktif (maksimal untuk saat ini). Selesaikan permohonan terlebih dahulu.");
        }

        // ✅ Validasi nominal berbasis benchmark kendaraan:
        // rentang = benchmark +/- 500.000 (kelipatan 50.000, batas 50.000 - 3.000.000)
        $benchmark = null;
        if ($request->vehicle_id) {
            $v = VehicleAndToolsConsumers::with('detail.site')->find($request->vehicle_id);
            $benchmark = $v?->fuel_benchmark;
        }

        $range = FuelBenchmarkService::rangeForBenchmark($benchmark);

        $billRules = ['required', 'numeric', 'min:'.$range['min'], 'max:'.$range['max'], 'multiple_of:50000'];
        $messages = [
            'gasoline_type.required' => 'Jenis BBM harus dipilih',
            'bill_payment.required' => 'Nominal harus diisi',
            'bill_payment.min' => 'Nominal di bawah rentang benchmark BBM (min Rp '.
                number_format($range['min'], 0, ',', '.').').',
            'bill_payment.max' => 'Nominal melebihi rentang benchmark BBM (maks Rp '.
                number_format($range['max'], 0, ',', '.').').',
            'bill_payment.multiple_of' => 'Nominal harus kelipatan Rp 50.000.',
        ];

        // Validasi input
        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicle_and_tools_consumers,id',
            'new_vehicle_type' => 'nullable|in:KENDARAAN,ALAT',
            'new_vehicle_code' => 'nullable|string|max:255',
            'new_vehicle_name' => 'nullable|string|max:255',
            'new_vehicle_notes' => 'nullable|string|max:500',
            'gasoline_type' => 'required|string',
            'bill_payment' => $billRules,
            'requester_notes' => 'nullable|string|max:500',
        ], $messages);

        try {
            DB::beginTransaction();

            $vehicleId = null;
            $needsSuperadminApproval = false;
            $vehicle = null; // ✅ Initialize variable

            // CASE 1: Pilih kendaraan existing
            if (! empty($validated['vehicle_id'])) {
                $vehicle = VehicleAndToolsConsumers::find($validated['vehicle_id']);

                if (! $vehicle) {
                    return back()
                        ->withInput()
                        ->with('error', 'Kendaraan tidak ditemukan.');
                }

                // Cek apakah kendaraan sudah diapprove
                if (! $vehicle->is_approved) {
                    return back()
                        ->withInput()
                        ->with('error', 'Kendaraan/Alat yang dipilih belum disetujui oleh Superadmin. Silakan tunggu persetujuan atau pilih kendaraan lain.');
                }

                // ✅ Cek apakah kendaraan masih aktif
                if (! $vehicle->is_active) {
                    return back()
                        ->withInput()
                        ->with('error', 'Kendaraan/Alat yang dipilih sudah tidak aktif. Hubungi tim IT untuk aktivasi kembali.');
                }

                $vehicleId = $validated['vehicle_id'];
            }
            // CASE 2: Tambah kendaraan baru (rental)
            elseif (! empty($validated['new_vehicle_code']) && ! empty($validated['new_vehicle_name'])) {
                // Cek duplikasi kode
                $existingVehicle = VehicleAndToolsConsumers::where('consumerial_code', $validated['new_vehicle_code'])->first();

                if ($existingVehicle) {
                    // ✅ Cek status active
                    if (! $existingVehicle->is_active) {
                        return back()
                            ->withInput()
                            ->with('error', 'Kendaraan sudah tercatat dengan kode: '.$existingVehicle->consumerial_code.'. Hubungi tim IT untuk melakukan aktivasi kendaraan.');
                    } else {
                        return back()
                            ->withInput()
                            ->with('error', 'Kendaraan dengan kode '.$existingVehicle->consumerial_code.' sudah terdaftar dan aktif. Silakan pilih dari daftar.');
                    }
                }

                // Buat kendaraan baru dengan status pending approval
                $vehicle = VehicleAndToolsConsumers::create([
                    'consumerial_type' => $validated['new_vehicle_type'],
                    'consumerial_code' => strtoupper($validated['new_vehicle_code']),
                    'consumerial_name' => $validated['new_vehicle_name'],
                    'consumerial_notes' => $validated['new_vehicle_notes'] ?? null,
                    'is_approved' => false,
                    'is_active' => true,
                    'created_by' => $user->id,
                ]);

                $vehicleId = $vehicle->id;
                $needsSuperadminApproval = true;
            } else {
                return back()
                    ->withInput()
                    ->with('error', 'Pilih kendaraan existing atau tambahkan kendaraan baru dengan lengkap.');
            }

            // Generate request number
            $requestNumber = $this->generateRequestNumber();

            // ✅ Tentukan status permohonan
            $status = $needsSuperadminApproval ? 'pending_vehicle_approval' : 'pending';

            // Create permohonan
            $permohonan = Permohonan::create([
                'request_number' => $requestNumber,
                'requester_id' => $user->id,
                'request_date' => now(),
                'gasoline_type' => $validated['gasoline_type'],
                'bill_amounts' => $validated['bill_payment'],
                'status' => $status,
                'requester_notes' => $validated['requester_notes'] ? $validated['requester_notes'] : null,
                'consumerial_tools_id' => $vehicleId,
            ]);

            DB::commit();

            if ($needsSuperadminApproval) {
                return redirect()->route('requester-index')
                    ->with('success', 'Permohonan BBM berhasil diajukan dengan nomor: '.$requestNumber.'. Menunggu persetujuan kendaraan rental oleh Superadmin.');
            } else {
                return redirect()->route('requester-index')
                    ->with('success', 'Permohonan BBM berhasil diajukan dengan nomor: '.$requestNumber);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Request creation error: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Gagal mengajukan permohonan: '.$e->getMessage());
        }
    }

    /*
     * ✅ UPDATED: Cancel request by requester
     * Jika permohonan menggunakan kendaraan rental baru yang belum diapprove,
     * kendaraan tersebut akan dihapus otomatis
     */
    public function requester_cancel_request(Request $request, $id)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:500',
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan harus diisi',
            'cancellation_reason.min' => 'Alasan pembatalan minimal 10 karakter',
        ]);

        $permohonan = Permohonan::with('vehicle')
            ->where('requester_id', Auth::id())
            ->whereIn('status', ['pending', 'pending_vehicle_approval', 'approved'])
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            // ✅ LOGIC: Cek apakah ini permohonan dengan kendaraan rental baru yang belum diapprove
            $shouldDeleteVehicle = false;
            $vehicleToDelete = null;

            if ($permohonan->vehicle) {
                $vehicle = $permohonan->vehicle;

                // Kondisi untuk hapus kendaraan:
                // 1. Kendaraan belum diapprove (is_approved = false)
                // 2. Kendaraan dibuat oleh user yang sama (created_by = requester)
                // 3. Status permohonan adalah 'pending', 'pending_vehicle_approval', atau 'approved'
                if (! $vehicle->is_approved && $vehicle->created_by === Auth::id()) {

                    // ✅ Cek apakah kendaraan ini digunakan di permohonan lain yang masih aktif
                    $otherActiveRequests = Permohonan::where('consumerial_tools_id', $vehicle->id)
                        ->where('id', '!=', $permohonan->id)
                        ->whereIn('status', ['pending', 'pending_vehicle_approval', 'approved'])
                        ->count();

                    // Hanya hapus jika tidak ada permohonan aktif lain yang menggunakan kendaraan ini
                    if ($otherActiveRequests === 0) {
                        $shouldDeleteVehicle = true;
                        $vehicleToDelete = $vehicle;
                    }
                }
            }

            // Update status permohonan menjadi cancelled
            $taggedReason = '[USER CANCELLATION] '.$validated['cancellation_reason'];
            $newNotes = $permohonan->requester_notes
                ? $permohonan->requester_notes."\n\n".$taggedReason
                : $taggedReason;

            $permohonan->update([
                'status' => 'cancelled',
                'requester_notes' => $newNotes,
            ]);

            // Update approved_request jika ada (untuk status 'approved')
            $approvedRequest = Approved_Request::where('permohonan_id', $permohonan->id)->first();
            if ($approvedRequest) {
                $approvedRequest->update(['status' => 'cancelled']);
                \Log::info("✅ Cancelled approved_request for permohonan #{$permohonan->id}");
            }

            // ✅ HAPUS KENDARAAN jika memenuhi kondisi
            if ($shouldDeleteVehicle && $vehicleToDelete) {
                $vehicleCode = $vehicleToDelete->consumerial_code;
                $vehicleName = $vehicleToDelete->consumerial_name;
                $vehicleType = $vehicleToDelete->consumerial_type;

                // Hapus kendaraan dari database
                $vehicleToDelete->delete();

                \Log::info("🗑️ AUTO-DELETED unapproved {$vehicleType}: {$vehicleCode} - {$vehicleName} (created by User #{$permohonan->requester_id}, permohonan #{$permohonan->id} cancelled)");

                DB::commit();

                return redirect()->route('requester-index')
                    ->with('success', "Permohonan berhasil dibatalkan. {$vehicleType} rental '{$vehicleCode} - {$vehicleName}' yang belum disetujui juga telah dihapus dari sistem.");
            }

            DB::commit();

            return redirect()->route('requester-index')
                ->with('success', '✅ Permohonan berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cancel request error: '.$e->getMessage());
            \Log::error($e->getTraceAsString());

            return back()->with('error', '❌ Gagal membatalkan permohonan: '.$e->getMessage());
        }
    }

    /**
     * LAZY CHECK: Check dan expire vouchers yang sudah melewati valid_until
     * Dipanggil setiap user action untuk memastikan data selalu up-to-date
     * PENGGANTI SCHEDULER - WORK DI FTP HOSTING
     */
    private function checkAndExpireVouchers()
    {
        $expiredVouchers = Approved_Request::where('status', 'approved')
            ->where('valid_until', '<', now())
            ->get();

        foreach ($expiredVouchers as $voucher) {
            try {
                DB::beginTransaction();

                $voucher->update(['status' => 'cancelled']);

                $permohonan = Permohonan::find($voucher->permohonan_id);
                if ($permohonan && $permohonan->status === 'approved') {
                    $permohonan->update([
                        'status' => 'cancelled',
                        'requester_notes' => ($permohonan->requester_notes ?? '')
                            ."\n\n[SYSTEM AUTO-CANCEL] Voucher expired, tidak digunakan dalam 50 jam.",
                    ]);

                    // ✅ PENALTY: Kurangi 1 slot untuk 3 bulan
                    UserSlotAdjustment::create([
                        'user_id' => $permohonan->requester_id,
                        'type' => 'penalty',
                        'reason' => 'voucher_expired',
                        'related_permohonan_id' => $permohonan->id,
                        'effective_from' => now(),
                        'effective_until' => now()->addMonths(3),
                        'is_active' => true,
                    ]);

                    \Log::info("❌ Auto-cancelled voucher #{$voucher->id} for request #{$permohonan->id}");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Failed to expire voucher #{$voucher->id}: ".$e->getMessage());
            }
        }
    }

    /**
     * ✅ Calculate max slots for user based on penalties & bonuses
     */
    private function calculateUserMaxSlots($userId)
    {
        $baseSlots = 5; // Default 5 slots

        $adjustments = UserSlotAdjustment::where('user_id', $userId)
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where('effective_until', '>=', now())
            ->get();

        $totalAdjustment = 0;

        foreach ($adjustments as $adjustment) {
            if ($adjustment->type === 'penalty') {
                $totalAdjustment -= 1;
            } elseif ($adjustment->type === 'bonus') {
                $totalAdjustment += 1;
            }
        }

        $maxSlots = $baseSlots + $totalAdjustment;

        // Min 1 slot, max 6 slots
        return max(1, min(6, $maxSlots));
    }

    /**
     * ✅ Check if user eligible for reward based on submission history
     * Kriteria: minimal 80% on-time dari 10 permohonan terakhir
     */
    private function checkRewardEligibility($userId)
    {
        $recentCompletedRequests = Permohonan::where('requester_id', $userId)
            ->where('status', 'completed')
            ->with(['approvedRequest', 'transactionProof'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Kalau belum ada 10 permohonan, gak eligible (user baru)
        if ($recentCompletedRequests->count() < 10) {
            \Log::info("User {$userId} has less than 10 completed requests: ".$recentCompletedRequests->count());

            return false;
        }

        $onTimeCount = 0;
        $totalCount = 0;

        foreach ($recentCompletedRequests as $request) {
            if (! $request->approvedRequest || ! $request->transactionProof) {
                continue;
            }

            $submitTime = $request->transactionProof->created_at;
            $validUntil = $request->approvedRequest->valid_until;

            if ($submitTime->lessThan($validUntil)) {
                $onTimeCount++;
            }

            $totalCount++;
        }

        if ($totalCount === 0) {
            return false;
        }

        $onTimePercentage = ($onTimeCount / $totalCount) * 100;

        \Log::info("User {$userId} on-time rate: {$onTimePercentage}% ({$onTimeCount}/{$totalCount})");

        // Eligible kalau minimal 80% on-time
        return $onTimePercentage >= 80;
    }

    /**
     * Generate unique request number
     * Format: REQ-DDMMYYYY-RANDOM6
     */
    private function generateRequestNumber()
    {
        $date = now()->format('dmY');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        return "REQ-{$date}-{$random}";
    }

    /**
     * Show history of all requests
     */
    public function requester_history()
    {
        $title = 'Riwayat Permohonan BBM';
        $user = Auth::user();

        // Get all requests with relationships.
        // Urutkan berdasar aktivitas laporan terbaru (submission report / tanggal request)
        // supaya permohonan yang baru saja dilaporkan langsung muncul di paling atas.
        $history = Permohonan::where('requester_id', $user->id)
            ->with(['vehicle', 'authorizer', 'approvedRequest', 'transactionProof'])
            ->orderByDesc(DB::raw('COALESCE((SELECT created_at FROM transaction_proofs tp WHERE tp.req_id = permohonans.id), request_date)'))
            ->paginate(20);

        return view('requester.history_request', compact('title', 'history'));
    }

    /**
     * Submit transaction report (laporan pembelian BBM)
     */
    /**
     * Submit transaction report (laporan pembelian BBM)
     */
    public function requester_submit_report(Request $request, $id)
    {
        $permohonan = Permohonan::with('vehicle')
            ->where('requester_id', Auth::id())
            ->where('status', 'approved')
            ->findOrFail($id);

        // Cek apakah sudah pernah submit report
        if ($permohonan->transactionProof) {
            return back()->with('error', 'Laporan untuk permohonan ini sudah pernah dikirim.');
        }

        // Tentukan apakah ini kendaraan atau alat
        $isVehicle = $permohonan->vehicle && $permohonan->vehicle->consumerial_type === 'Kendaraan';

        // Validasi dinamis berdasarkan tipe
        $rules = [
            'purchase_date' => 'required|date|before_or_equal:today',
            'volume_bbm' => 'required|numeric|min:0.01',
            'foto_struk' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ];

        $messages = [
            'purchase_date.required' => 'Tanggal pengisian harus diisi',
            'purchase_date.date' => 'Format tanggal tidak valid',
            'purchase_date.before_or_equal' => 'Tanggal pengisian tidak boleh melebihi hari ini',
            'volume_bbm.required' => 'Volume pengisian harus diisi',
            'volume_bbm.numeric' => 'Volume harus berupa angka',
            'volume_bbm.min' => 'Volume minimal 0.01 liter',
            'foto_struk.required' => 'Foto struk BBM harus diupload',
            'foto_struk.image' => 'File harus berupa gambar',
            'foto_struk.mimes' => 'Format gambar harus jpeg, png, atau jpg',
            'foto_struk.max' => 'Ukuran file maksimal 5MB',
        ];

        // Jika kendaraan, wajib isi KM dan foto odometer
        if ($isVehicle) {
            $rules['km_akhir'] = 'required|numeric|min:0';
            $rules['foto_km'] = 'required|image|mimes:jpeg,png,jpg|max:5120';

            $messages['km_akhir.required'] = 'KM akhir harus diisi untuk kendaraan';
            $messages['km_akhir.numeric'] = 'KM akhir harus berupa angka';
            $messages['km_akhir.min'] = 'KM akhir tidak boleh negatif';
            $messages['foto_km.required'] = 'Foto odometer harus diupload untuk kendaraan';
            $messages['foto_km.image'] = 'File harus berupa gambar';
            $messages['foto_km.mimes'] = 'Format gambar harus jpeg, png, atau jpg';
            $messages['foto_km.max'] = 'Ukuran file maksimal 5MB';
        } else {
            // Untuk alat, KM dan foto odometer opsional (akan di-set 0 dan null)
            $rules['km_akhir'] = 'nullable|numeric|min:0';
            $rules['foto_km'] = 'nullable|image|mimes:jpeg,png,jpg|max:5120';
        }

        // Validasi request
        $validated = $request->validate($rules, $messages);

        try {
            DB::beginTransaction();

            // Generate nama file dengan format: S.OPRS-[req_number]-...
            $dateFormat = now()->format('dmY');
            $requestNumber = $permohonan->request_number;

            // Upload foto struk BBM (wajib untuk semua)
            $strukFile = $request->file('foto_struk');
            $strukExtension = $strukFile->getClientOriginalExtension();
            $strukFileName = "S.OPRS-{$requestNumber}-{$dateFormat}.{$strukExtension}";
            $strukPath = $strukFile->storeAs('transaction_proofs/struk', $strukFileName, 'public');

            // Upload foto odometer (hanya jika ada)
            $odoPath = null;
            if ($request->hasFile('foto_km')) {
                $odoFile = $request->file('foto_km');
                $odoExtension = $odoFile->getClientOriginalExtension();
                $odoFileName = "S.OPRS-{$requestNumber}-ODO-{$dateFormat}.{$odoExtension}";
                $odoPath = $odoFile->storeAs('transaction_proofs/odometer', $odoFileName, 'public');
            }

            // Get voucher code from approved_requests
            $approvedRequest = Approved_Request::where('permohonan_id', $permohonan->id)->first();

            // Generate transaction ID
            $transactionId = 'TRX-'.now()->format('YmdHis').'-'.strtoupper(substr(uniqid(), -6));

            // Gabungkan tanggal dengan waktu sekarang untuk purchase_datetime
            $purchaseDatetime = $validated['purchase_date'].' '.now()->format('H:i:s');

            // Set KM akhir: jika alat dan tidak diisi, set 0
            $kmAkhir = 0;
            if ($isVehicle) {
                $kmAkhir = $validated['km_akhir'];
            } elseif (isset($validated['km_akhir'])) {
                $kmAkhir = $validated['km_akhir'];
            }

            // Create transaction proof
            Transaction_Proofs::create([
                'transaction_id' => $transactionId,
                'req_id' => $permohonan->id,
                'requester_id' => Auth::id(),
                'voucher_number' => $approvedRequest ? $approvedRequest->voucher_code : null,
                'purchase_datetime' => $purchaseDatetime,
                'fuel_volume' => $validated['volume_bbm'],
                'km_terakhir' => $kmAkhir,
                'struk_bbm_path' => $strukPath,
                'odometer_photo_path' => $odoPath,
            ]);

            // Update status permohonan menjadi completed
            $permohonan->update(['status' => 'completed']);

            // Update status approved_request menjadi completed
            if ($approvedRequest) {
                $approvedRequest->update(['status' => 'completed']);
            }

            // ✅ CEK REWARD: Submit sebelum expired DAN konsisten on-time
            if ($approvedRequest && now()->lessThan($approvedRequest->valid_until)) {
                $isEligibleForReward = $this->checkRewardEligibility(Auth::id());

                if ($isEligibleForReward) {
                    UserSlotAdjustment::create([
                        'user_id' => Auth::id(),
                        'type' => 'bonus',
                        'reason' => 'early_submission',
                        'related_permohonan_id' => $permohonan->id,
                        'effective_from' => now(),
                        'effective_until' => now()->addWeek(),
                        'is_active' => true,
                    ]);

                    \Log::info('✅ User '.Auth::id().' eligible for bonus slot');
                } else {
                    \Log::info('⚠️ User '.Auth::id().' submit on-time but not consistent enough');
                }
            }

            DB::commit();

            // ✅ Notifikasi berbeda berdasarkan reward
            if ($approvedRequest && now()->lessThan($approvedRequest->valid_until)) {
                $isEligible = $this->checkRewardEligibility(Auth::id());

                if ($isEligible) {
                    return redirect()->route('requester-index')
                        ->with('success', '🎉 Laporan berhasil dikirim! Anda mendapat bonus 1 slot tambahan selama 1 minggu karena konsisten tepat waktu.');
                } else {
                    return redirect()->route('requester-index')
                        ->with('success', '✅ Laporan berhasil dikirim tepat waktu. Tetap pertahankan untuk mendapat bonus slot!');
                }
            } else {
                return redirect()->route('requester-index')
                    ->with('warning', '⚠️ Laporan berhasil dikirim, namun sudah melewati batas waktu voucher.');
            }
        } catch (\Exception $e) {
            DB::rollBack();

            // Hapus file yang sudah terupload jika ada error
            if (isset($strukPath) && Storage::disk('public')->exists($strukPath)) {
                Storage::disk('public')->delete($strukPath);
            }
            if (isset($odoPath) && Storage::disk('public')->exists($odoPath)) {
                Storage::disk('public')->delete($odoPath);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal mengirim laporan: '.$e->getMessage());
        }
    }

    /**
     * Show detail request
     */
    public function requester_show($id)
    {
        $title = 'Detail Permohonan';
        $request = Permohonan::with(['vehicle', 'authorizer', 'approvedRequest', 'transactionProof'])
            ->where('requester_id', Auth::id())
            ->findOrFail($id);

        return view('requester.detail_request', compact('title', 'request'));
    }

    /**
     * Upload bukti transaksi (Transaction Proof)
     */
    public function requester_upload_proof(Request $request, $id)
    {
        $permohonan = Permohonan::where('requester_id', Auth::id())
            ->where('status', 'approved')
            ->findOrFail($id);

        // Validasi
        $validated = $request->validate([
            'purchase_datetime' => 'required|date',
            'fuel_volume' => 'required|numeric|min:1',
            'km_terakhir' => 'nullable|numeric',
            'struk_bbm' => 'required|image|max:2048',
            'odometer_photo' => 'nullable|image|max:2048',
        ], [
            'purchase_datetime.required' => 'Tanggal pengisian harus diisi',
            'fuel_volume.required' => 'Volume pengisian harus diisi',
            'struk_bbm.required' => 'Foto struk BBM harus diupload',
            'struk_bbm.image' => 'File harus berupa gambar',
            'struk_bbm.max' => 'Ukuran file maksimal 2MB',
        ]);

        try {
            DB::beginTransaction();

            // Upload files
            $strukPath = $request->file('struk_bbm')->store('transaction_proofs/struk', 'public');
            $odoPath = $request->hasFile('odometer_photo')
                ? $request->file('odometer_photo')->store('transaction_proofs/odometer', 'public')
                : null;

            // Get voucher code from approved_requests
            $approvedRequest = Approved_Request::where('permohonan_id', $permohonan->id)->first();

            // Generate transaction ID
            $transactionId = 'TRX-'.now()->format('YmdHis').'-'.strtoupper(substr(uniqid(), -6));

            // Create transaction proof
            Transaction_Proofs::create([
                'transaction_id' => $transactionId,
                'req_id' => $permohonan->id,
                'requester_id' => Auth::id(),
                'voucher_number' => $approvedRequest ? $approvedRequest->voucher_code : null,
                'purchase_datetime' => $validated['purchase_datetime'],
                'fuel_volume' => $validated['fuel_volume'],
                'km_terakhir' => $validated['km_terakhir'] ?? 0,
                'struk_bbm_path' => $strukPath,
                'odometer_photo_path' => $odoPath,
            ]);

            // Update status permohonan menjadi completed
            $permohonan->update(['status' => 'completed']);

            DB::commit();

            return redirect()->route('requester-index')
                ->with('success', 'Bukti transaksi berhasil diupload. Permohonan selesai.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal upload bukti: '.$e->getMessage());
        }
    }

    /**
     * =========================================
     * Controller bagian Admin
     * =========================================
     */
    public function admin_index()
    {
        $title = 'Dashboard Admin';

        // ✅ LAZY CHECK: Cek expired vouchers
        $this->checkAndExpireVouchers();

        $siteIds = Auth::user()?->unitSiteIds();

        // Get statistics
        $stats = [
            'pending' => Permohonan::inUnit($siteIds)->where('status', 'pending')->count(),
            'approved' => Permohonan::inUnit($siteIds)->where('status', 'approved')->count(),
            'completed' => Permohonan::inUnit($siteIds)->where('status', 'completed')->count(),
            'rejected' => Permohonan::inUnit($siteIds)->where('status', 'rejected')->count(),
            'cancelled' => Permohonan::inUnit($siteIds)->where('status', 'cancelled')->count(),
            'total' => Permohonan::inUnit($siteIds)->count(),
        ];

        // ✅ Load relasi requester dan consumerialTool
        $pendingRequests = Permohonan::with(['requester', 'vehicle'])
            ->inUnit($siteIds)
            ->where('status', 'pending')
            ->orderBy('request_date', 'desc')
            ->get();

        // Filter hanya approved/rejected/completed, load relasi
        $myApprovals = Permohonan::with(['requester', 'vehicle', 'authorizer', 'approvedRequest'])
            ->inUnit($siteIds)
            ->whereIn('status', ['approved', 'rejected', 'completed'])
            ->where('authorizer_id', Auth::user()->id)
            ->orderBy('authorization_date', 'desc')
            ->get();

        // ✅ Load semua relasi, sort by date
        $allRequests = Permohonan::with(['requester', 'vehicle', 'authorizer', 'approvedRequest'])
            ->inUnit($siteIds)
            ->orderBy('request_date', 'desc')
            ->get();

        // ✅ Load transaction reports
        $reports = Transaction_Proofs::with(['permohonan.requester', 'permohonan.vehicle'])
            ->inUnit($siteIds)
            ->whereHas('permohonan', function ($q) {
                $q->whereNotNull('consumerial_tools_id');
            })
            ->whereHas('permohonan.vehicle') // Pastikan vehicle ada
            ->orderBy('created_at', 'desc')
            ->get();

        // ✅ Load approved vouchers (untuk prognosa transaksi)
        // Default: voucher yang terbit KEMARIN (approval_date = yesterday).
        // Status mencakup semua (active/approved/cancelled/completed) — fix gap
        // voucher 'active' (approve tunggal) yang sebelumnya tidak muncul.
        $voucherDate = request()->get('voucher_date', now()->subDay()->toDateString());

        $approvedVouchers = Approved_Request::with([
            'permohonan.requester',
            'permohonan.vehicle',
            'authorizer',
        ])
            ->inUnit($siteIds)
            ->whereDate('approval_date', $voucherDate)
            ->whereIn('status', ['active', 'approved', 'cancelled', 'completed'])
            ->whereHas('permohonan', function ($q) {
                $q->whereNotNull('consumerial_tools_id');
            })
            ->whereHas('permohonan.vehicle') // Pastikan vehicle ada
            ->orderBy('approval_date', 'desc')
            ->get();

        return view('admin.index', compact(
            'title',
            'stats',
            'pendingRequests',
            'myApprovals',
            'allRequests',
            'reports',
            'approvedVouchers',
            'voucherDate'
        ));
    }

    public function admin_approval()
    {
        $title = 'Approval Permohonan BBM';

        $siteIds = Auth::user()?->unitSiteIds();

        // Get pending requests
        $pendingRequests = Permohonan::with(['requester', 'vehicle'])
            ->inUnit($siteIds)
            ->where('status', 'pending')
            ->orderBy('request_date', 'desc')
            ->get();

        // Get statistics
        $stats = [
            'pending' => Permohonan::inUnit($siteIds)->where('status', 'pending')->count(),
            'approved' => Permohonan::inUnit($siteIds)->where('status', 'approved')->count(),
            'completed' => Permohonan::inUnit($siteIds)->where('status', 'completed')->count(),
            'rejected' => Permohonan::inUnit($siteIds)->where('status', 'rejected')->count(),
            'cancelled' => Permohonan::inUnit($siteIds)->where('status', 'cancelled')->count(),
            'total' => Permohonan::inUnit($siteIds)->count(),
        ];

        // Get approvals processed by this admin
        $myApprovals = Permohonan::with(['requester', 'vehicle', 'authorizer'])
            ->inUnit($siteIds)
            ->whereIn('status', ['approved', 'rejected', 'completed'])
            ->where('authorizer_id', Auth::user()->id)
            ->orderBy('authorization_date', 'desc')
            ->get();

        $allRequests = Permohonan::with(['requester', 'vehicle', 'authorizer'])
            ->inUnit($siteIds)
            ->orderBy('request_date', 'desc')
            ->paginate(15);

        // Get all requests for history
        $allRequests = Permohonan::with(['requester', 'vehicle', 'authorizer'])
            ->inUnit($siteIds)
            ->orderBy('request_date', 'desc')
            ->paginate(20);

        $reports = Transaction_Proofs::with(['permohonan.requester', 'permohonan.vehicle'])
            ->inUnit($siteIds)
            ->whereHas('permohonan', function ($q) {
                $q->whereNotNull('consumerial_tools_id');
            })
            ->whereHas('permohonan.vehicle') // Pastikan vehicle ada
            ->orderBy('created_at', 'desc')
            ->get();
        $approvedVouchers = Approved_Request::with([
            'permohonan.requester',
            'permohonan.vehicle',
            'authorizer',
        ])
            ->inUnit($siteIds)
            ->whereIn('status', ['active', 'approved', 'cancelled', 'completed'])
            ->whereHas('permohonan', function ($q) {
                $q->whereNotNull('consumerial_tools_id');
            })
            ->whereHas('permohonan.vehicle') // Pastikan vehicle ada
            ->orderBy('approval_date', 'desc')
            ->get();

        return view('admin.approval', compact('title', 'pendingRequests', 'stats', 'myApprovals', 'allRequests', 'reports', 'approvedVouchers'));
    }

    /**
     * AJAX: daftar voucher per tanggal + tracking approval (realtime filter).
     * Dipakai tab "Kode Voucher Kemarin" — filter tanpa tombol.
     */
    public function voucherList(Request $request)
    {
        $date = $request->get('date', now()->subDay()->toDateString());
        $status = $request->get('status', 'all'); // all | active | expired

        $siteIds = Auth::user()?->unitSiteIds();

        $query = Approved_Request::with([
            'permohonan.requester',
            'permohonan.vehicle',
            'authorizer',
        ])
            ->inUnit($siteIds)
            ->whereDate('approval_date', $date)
            ->whereIn('status', ['active', 'approved', 'cancelled', 'completed'])
            ->whereHas('permohonan', fn ($q) => $q->whereNotNull('consumerial_tools_id'))
            ->whereHas('permohonan.vehicle')
            ->orderBy('approval_date', 'desc');

        $vouchers = $query->get();

        $rows = $vouchers->map(function ($v) {
            $permohonan = $v->permohonan;
            $vehicle = $permohonan?->vehicle;
            $isExpired = $v->valid_until && Carbon::parse($v->valid_until)->isPast();

            return [
                'id' => $v->id,
                'request_number' => $permohonan?->request_number ?? '-',
                'pemohon' => $permohonan?->requester?->nama_lengkap ?? '-',
                'vehicle_code' => $vehicle?->consumerial_code ?? '-',
                'vehicle_name' => $vehicle?->consumerial_name ?? '-',
                'vehicle_type' => $vehicle?->consumerial_type ?? '-',
                'gasoline_type' => $permohonan?->gasoline_type ?? '-',
                'approved_amount' => (float) ($v->approved_amount ?? 0),
                'voucher_code' => $v->voucher_code,
                'status' => $isExpired ? 'expired' : 'active',
                'status_label' => $isExpired ? 'EXPIRED' : strtoupper($v->status),
                'permohonan_status' => strtoupper($permohonan?->status ?? '-'),
                'approver' => $v->authorizer?->nama_lengkap ?? '-',
                'approval_date' => $v->approval_date ? Carbon::parse($v->approval_date)->format('d/m/Y H:i') : '-',
                'valid_until' => $v->valid_until ? Carbon::parse($v->valid_until)->format('d/m/Y H:i') : '-',
                'approval_notes' => $v->approval_notes ?? $permohonan?->authorizer_notes ?? '',
            ];
        })->values();

        // Filter status (client bisa juga, tapi sekalian server-side)
        if ($status !== 'all') {
            $rows = $rows->filter(fn ($r) => $r['status'] === $status)->values();
        }

        return response()->json(['data' => $rows, 'count' => $rows->count(), 'date' => $date]);
    }

    /**
     * Export voucher ke Excel (.xlsx) — bulk: terpilih (ids) atau semua per tanggal.
     */
    public function voucherExport(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:approved_requests,id',
            'date' => 'nullable|date',
            'status' => 'nullable|in:all,active,expired',
        ]);

        $query = Approved_Request::with([
            'permohonan.requester',
            'permohonan.vehicle',
            'authorizer',
        ])
            ->whereIn('status', ['active', 'approved', 'cancelled', 'completed'])
            ->whereHas('permohonan', fn ($q) => $q->whereNotNull('consumerial_tools_id'))
            ->whereHas('permohonan.vehicle');

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->ids);
            $filterDate = 'terpilih-'.count($request->ids);
        } else {
            $date = $request->get('date', now()->subDay()->toDateString());
            $query->whereDate('approval_date', $date);
            $filterDate = $date;
        }

        $vouchers = $query->orderBy('approval_date', 'desc')->get();

        if ($vouchers->isEmpty()) {
            return back()->with('error', 'Tidak ada voucher untuk diexport pada filter ini.');
        }

        $fileName = 'Voucher-'.$filterDate.'.xlsx';

        return (new \App\Exports\VoucherExport($vouchers, $filterDate))->download($fileName);
    }

    /**
     * Approve atau reject request
     */
    public function admin_process_request(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'authorizer_notes' => 'nullable|string|max:500',
        ]);

        $permohonan = Permohonan::where('status', 'pending')->findOrFail($id);

        try {
            DB::beginTransaction();

            $action = $validated['action'];
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';

            // Update permohonan
            $permohonan->update([
                'status' => $newStatus,
                'authorizer_id' => Auth::id(),
                'authorization_date' => now(),
                'authorizer_notes' => $validated['authorizer_notes'] ?? null,
            ]);

            // Jika approved, buat approved_request dan voucher
            if ($newStatus === 'approved') {
                $voucherCode = $this->generateVoucherCode();

                Approved_Request::create([
                    'permohonan_id' => $permohonan->id,
                    'approval_date' => now(),
                    'voucher_code' => $voucherCode,
                    'valid_until' => now()->addHours(50), // Voucher berlaku 50 jam
                    'approved_amount' => $permohonan->bill_amounts,
                    'authorizer_id' => Auth::id(),
                    'authorizer_notes' => $validated['authorizer_notes'] ? ($newStatus === 'rejected' ? '[ADMIN REJECTION] ' : '[APPROVED] ').$validated['authorizer_notes'] : null,
                    'status' => 'active',
                ]);
            }

            DB::commit();

            $message = $newStatus === 'approved'
                ? 'Permohonan berhasil disetujui'
                : 'Permohonan berhasil ditolak';

            return redirect()->route('admin-approval')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memproses permohonan: '.$e->getMessage());
        }
    }

    /**
     * Bulk approve/reject - FIXED VERSION
     */
    public function admin_bulk_process(Request $request)
    {
        // ✅ Step 1: Cek data mentah
        // dd($request->all());

        // Validasi: decisions harus ada dan berupa string JSON
        $validated = $request->validate([
            'decisions' => 'required|string',
        ]);

        // ✅ Step 2: Cek hasil validasi
        // dd($validated);

        // Parse JSON decisions
        $decisions = json_decode($validated['decisions'], true);

        // ✅ Step 3: Cek hasil parsing JSON
        // dd($decisions);

        // Validasi hasil parsing
        if (! is_array($decisions) || empty($decisions)) {
            return back()->with('error', 'Format data tidak valid');
        }

        try {
            DB::beginTransaction();

            $approvedCount = 0;
            $rejectedCount = 0;
            $skippedCount = 0;

            foreach ($decisions as $decision) {
                // Validasi struktur decision
                if (! isset($decision['request_id']) || ! isset($decision['status'])) {
                    $skippedCount++;
                    \Log::warning('Decision tidak valid: ', $decision);

                    continue;
                }

                $requestId = $decision['request_id'];
                $status = $decision['status']; // 'approve' or 'reject'

                // Cari permohonan yang masih pending
                $permohonan = Permohonan::where('status', 'pending')->find($requestId);

                if (! $permohonan) {
                    $skippedCount++;
                    \Log::warning("Permohonan ID {$requestId} tidak ditemukan atau bukan pending");

                    continue;
                }

                $newStatus = $status === 'approve' ? 'approved' : 'rejected';

                // Komentar admin per permohonan (opsional)
                $note = isset($decision['note']) && trim((string) $decision['note']) !== ''
                    ? trim((string) $decision['note'])
                    : null;

                // Update permohonan
                $permohonan->update([
                    'status' => $newStatus,
                    'authorizer_id' => Auth::id(),
                    'authorization_date' => now(),
                    'authorizer_notes' => $newStatus === 'rejected'
                        ? ($note ? '[ADMIN REJECTION] '.$note : '[ADMIN REJECTION] Bulk rejection')
                        : ($note ? '[ADMIN APPROVAL] '.$note : '[ADMIN APPROVAL]'),
                ]);

                // ✅ Jika APPROVED, buat voucher di approved_requests
                if ($newStatus === 'approved') {
                    $voucherCode = $this->generateVoucherCode();

                    Approved_Request::create([
                        'permohonan_id' => $permohonan->id,
                        'approval_date' => now(),
                        'voucher_code' => $voucherCode,
                        'valid_until' => now()->addHours(50), // Voucher berlaku 50 jam
                        'approved_amount' => $permohonan->bill_amounts,
                        'authorizer_id' => Auth::id(),
                        'status' => 'approved',
                        'approval_notes' => null,
                    ]);

                    $approvedCount++;
                    \Log::info("✅ Approved: Request #{$requestId} | Voucher: {$voucherCode}");
                }
                // ✅ Jika REJECTED, tidak perlu insert ke approved_requests
                else {
                    $rejectedCount++;
                    \Log::info("❌ Rejected: Request #{$requestId}");
                }
            }

            DB::commit();

            // Build success message
            $message = [];
            if ($approvedCount > 0) {
                $message[] = "✅ {$approvedCount} permohonan disetujui";
            }
            if ($rejectedCount > 0) {
                $message[] = "{$rejectedCount} permohonan ditolak";
            }
            if ($skippedCount > 0) {
                $message[] = "{$skippedCount} dilewati (tidak valid/sudah diproses)";
            }

            $finalMessage = implode(' | ', $message);
            \Log::info("Bulk Process Summary: {$finalMessage}");

            return redirect()->route('admin-dashboard')
                ->with('success', $finalMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk process error: '.$e->getMessage());
            \Log::error($e->getTraceAsString());

            return back()->with('error', 'Gagal memproses bulk action: '.$e->getMessage());
        }
    }

    /**
     * Generate voucher code
     * Format: BBMGDE-RANDOM6-DDMMYY
     */
    private function generateVoucherCode()
    {
        $date = now()->format('dmy');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        return "BBMGDE-{$random}-{$date}";
    }

    /**
     * bagian controller untuk cetak ke printer thermal
     *
     * */

    /**
     * Handle Thermer app print request
     * Endpoint untuk aplikasi Thermer (Bluetooth Print)
     */
    public function thermerPrint(Request $request)
    {
        try {
            // Get print data from query parameter
            $encodedData = $request->query('data');

            if (! $encodedData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No print data provided',
                ], 400);
            }

            // Decode the JSON data
            $printData = json_decode(urldecode($encodedData), true);

            if (! $printData || ! is_array($printData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid print data format',
                ], 400);
            }

            // Log for debugging
            Log::info('Thermer Print Request', [
                'data_count' => count($printData),
                'first_item' => $printData[0] ?? null,
            ]);

            // Return the print data in Thermer format
            return response()->json([
                'success' => true,
                'data' => $printData,
                'message' => 'Print data ready',
            ]);

        } catch (\Exception $e) {
            Log::error('Thermer Print Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error processing print data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getTransactionPhoto($reportId, $type)
    {
        // Validasi type
        if (! in_array($type, ['struk', 'odometer'])) {
            return response()->json(['error' => 'Invalid photo type'], 400);
        }

        // Cari transaction proof
        $report = Transaction_Proofs::findOrFail($reportId);

        // Tentukan path berdasarkan type
        $photoPath = null;
        if ($type === 'struk') {
            $photoPath = $report->struk_bbm_path;
        } elseif ($type === 'odometer') {
            $photoPath = $report->odometer_photo_path;
        }

        // Cek apakah foto ada
        if (! $photoPath || ! Storage::disk('public')->exists($photoPath)) {
            return response()->json(['error' => 'Photo not found'], 404);
        }

        // Get full path
        $fullPath = storage_path('app/public/'.$photoPath);

        // Read image
        $imageData = file_get_contents($fullPath);

        // Detect mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath);
        finfo_close($finfo);

        // Convert to base64
        $base64 = base64_encode($imageData);

        return response()->json([
            'success' => true,
            'base64' => "data:{$mimeType};base64,{$base64}",
            'filename' => basename($photoPath),
        ]);
    }
}
