<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDefault;
use App\Models\LunchForecast;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelfBookingController extends Controller
{
    /**
     * Tampilkan kalender booking mandiri untuk user tertentu.
     * Admin/superadmin bisa filter berdasarkan nama karyawan.
     *
     * Mode akses:
     *  - Terautentikasi  : akses penuh — direktori, lookup nama, booking
     *                       untuk karyawan mana pun (kebutuhan GA/HRD).
     *  - Anonim           : HANYA lewat slug pribadi (link/QR per karyawan).
     *                       Tidak ada direktori & lookup nama (anti PII leak).
     *                       Slug disimpan ke session sebagai "kunci" agar
     *                       POST /store anonim tidak bisa mem-book untuk slug
     *                       yang belum pernah dibuka sendiri (anti abuse).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $employee = null;
        $suggestions = collect();

        if ($user) {
            // ── Terautentikasi: akses penuh ─────────────────────────
            if ($request->filled('slug')) {
                $employee = EmployeeDefault::with('defaultSite')->where('slug', $request->slug)->first();
            } elseif ($request->filled('name')) {
                $name = trim($request->input('name'));
                $nameLower = strtolower($name);

                // First try exact match in employee_name
                $employee = EmployeeDefault::with('defaultSite')
                    ->where('employee_name', 'LIKE', "%{$name}%")
                    ->first();

                // If no match, search aliases
                if (!$employee) {
                    $employee = EmployeeDefault::with(['defaultSite', 'aliases'])
                        ->whereHas('aliases', function ($q) use ($nameLower) {
                            $q->whereRaw('LOWER(alias) LIKE ?', ["%{$nameLower}%"]);
                        })
                        ->first();
                }

                if ($name) {
                    $suggestions = EmployeeDefault::with('defaultSite')
                        ->where(function ($q) use ($name, $nameLower) {
                            $q->where('employee_name', 'LIKE', "%{$name}%")
                              ->orWhereHas('aliases', function ($q2) use ($nameLower) {
                                  $q2->whereRaw('LOWER(alias) LIKE ?', ["%{$nameLower}%"]);
                              });
                        })
                        ->limit(10)
                        ->get();
                }
            } elseif ($user->employeeDefault) {
                $employee = $user->employeeDefault->load('defaultSite');
            }
        } else {
            // ── Anonim: HANYA via slug pribadi ──────────────────────
            if ($request->filled('slug')) {
                $employee = EmployeeDefault::with('defaultSite')->where('slug', $request->slug)->first();
            }

            // "Kunci" sesi: hanya pemilik slug yang membuka halamannya
            // sendiri boleh mengirim POST /store anonim untuk slug tsb.
            if ($employee) {
                session(['lunch_self_booking_slug' => $employee->slug]);
            } else {
                session()->forget('lunch_self_booking_slug');
            }
        }

        $startDate = Carbon::parse($request->get('start_date', now()->addDay()->toDateString()));
        $days = (int) $request->get('days', 14);

        $weekHeaders = collect();
        for ($i = 0; $i < 7; $i++) {
            $weekHeaders->push($startDate->copy()->addDays($i)->locale('id')->isoFormat('dddd'));
        }

        // Build dates array
        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $d = $startDate->copy()->addDays($i);
            $dates->push([
                'date'        => $d->toDateString(),
                'day_name'    => $d->locale('id')->isoFormat('dddd'),
                'day_date'    => $d->locale('id')->isoFormat('D MMMM YYYY'),
                'is_weekend'  => $d->isWeekend(),
            ]);
        }

        $start = $startDate->toDateString();
        $end   = $startDate->copy()->addDays($days - 1)->toDateString();

        $existingForecasts = $employee
            ? LunchForecast::where('employee_default_id', $employee->id)
                ->whereBetween('lunch_date', [$start, $end])
                ->get()
                ->keyBy(fn ($f) => $f->lunch_date)
            : collect();

        foreach ($dates as &$d) {
            $forecast = $existingForecasts->get($d['date']);
            $d['forecast'] = $forecast;
            $d['default_site_id'] = $employee?->default_site_id;
            $d['selected_site_id'] = $forecast?->predicted_site_id ?? $employee?->default_site_id;
            $d['is_self_booked'] = $forecast && $forecast->prediction_source === 'self_booking';
            $d['is_admin_override'] = $forecast && $forecast->prediction_source === 'manual';
            $d['has_forecast'] = $forecast ? true : false;
        }
        unset($d);

        $sites = Site::active()->get();

        // Direktori karyawan (untuk dropdown "Ganti karyawan...") hanya untuk
        // user terautentikasi. Pengunjung anonim tetap bisa booking via
        // slug yang di-resolve dari link/QR pribadi tanpa membocorkan
        // seluruh daftar karyawan ke publik.
        $employees = Auth::check()
            ? EmployeeDefault::active()->orderBy('employee_name')->get(['id', 'slug', 'employee_name', 'default_site_id'])
            : collect();

        return view('lunch.self-booking.calendar', compact(
            'employee', 'dates', 'startDate', 'days',
            'sites', 'employees', 'start', 'end', 'suggestions', 'weekHeaders'
        ));
    }

    /**
     * Simpan booking mandiri — bulk update LunchForecast dengan source self_booking.
     *
     * Otorisasi:
     *  - Terautentikasi : boleh booking untuk karyawan mana pun.
     *  - Anonim          : HANYA boleh booking untuk slug yang sudah pernah
     *                       dibuka di halaman kalender (session marker),
     *                       sehingga tidak bisa mem-book atas nama karyawan
     *                       lain secara acak (anti IDOR tanpa login).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug'                => 'required|exists:employee_defaults,slug',
            'bookings'            => 'required|array|min:1',
            'bookings.*.date'     => 'required|date',
            'bookings.*.site_id'  => 'nullable|exists:sites,id',
        ]);

        $employee = EmployeeDefault::where('slug', $validated['slug'])->firstOrFail();
        $user = Auth::user();

        if (!$user) {
            // Anonim harus sudah membuka halaman kalender slug tsb sendiri.
            if (session('lunch_self_booking_slug') !== $employee->slug) {
                abort(403, 'Silakan buka link booking Anda terlebih dahulu.');
            }
        }

        $userId = $user?->id;

        $count = 0;
        foreach ($validated['bookings'] as $booking) {
            $bookingDate = $booking['date'] ?? null;
            $siteId = !empty($booking['site_id']) ? (int) $booking['site_id'] : null;

            if (!$bookingDate) {
                continue;
            }

            $existing = LunchForecast::where('employee_default_id', $employee->id)
                ->whereDate('lunch_date', $bookingDate)
                ->first();

            if ($existing && $existing->prediction_source === 'manual') {
                continue;
            }

            $payload = [
                'user_id'           => $employee->user_id,
                'employee_default_id' => $employee->id,
                'lunch_date'        => $bookingDate,
                'predicted_site_id' => $siteId,
                'prediction_source' => 'self_booking',
                'confidence'        => 100.0,
                'created_by'        => $userId,
                'updated_by'        => $userId,
            ];

            LunchForecast::updateOrCreate(
                [
                    'employee_default_id' => $employee->id,
                    'lunch_date'          => $bookingDate,
                ],
                $payload
            );
            $count++;
        }

        return redirect()
            ->route('welcome')
            ->with('success', 'Booking untuk ' . e($employee->employee_name) . " tersimpan ({$count} hari).");
    }
}
