<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDefault;
use App\Models\LunchForecast;
use App\Services\UnitScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelfBookingController extends Controller
{
    /**
     * Kalender booking mandiri.
     *
     * Aturan akses:
     *  - Anonim: hanya lewat slug pribadi (link/QR). Tidak ada direktori.
     *  - Login tanpa lunch.manage: hanya boleh membuka datanya sendiri.
     *  - Login dengan lunch.manage: boleh membuka karyawan di unitnya saja
     *    (superadmin pusat / developers: semua unit).
     *
     * Pilihan site selalu diambil dari unit karyawan yang bersangkutan, bukan
     * dari seluruh site yang ada.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $scope = UnitScope::for($user);

        $employee = null;
        $suggestions = collect();

        if ($user) {
            $canManage = $user->hasPermission('lunch.manage');

            if ($request->filled('slug')) {
                $employee = $this->employeeQuery($scope)
                    ->with('defaultSite.unit', 'unit')
                    ->where('slug', $request->slug)
                    ->first();
            } elseif ($request->filled('name')) {
                $name = trim($request->input('name'));

                $employee = $this->employeeQuery($scope)
                    ->with('defaultSite.unit', 'unit')
                    ->where('employee_name', 'LIKE', "%{$name}%")
                    ->first();

                if (! $employee) {
                    $employee = $this->employeeQuery($scope)
                        ->with(['defaultSite.unit', 'unit', 'aliases'])
                        ->whereHas('aliases', fn ($q) => $q->where('alias', 'LIKE', "%{$name}%"))
                        ->first();
                }

                $suggestions = $this->employeeQuery($scope)
                    ->with('defaultSite.unit', 'unit')
                    ->where('employee_name', 'LIKE', "%{$name}%")
                    ->limit(10)
                    ->get();
            } elseif ($user->employeeDefault) {
                $employee = $user->employeeDefault->load('defaultSite.unit', 'unit');
            }

            // Pegawai biasa tidak boleh membuka kalender orang lain.
            if ($employee && ! $canManage && (int) $user->employeeDefault?->id !== (int) $employee->id) {
                abort(403, 'Anda hanya dapat mengubah jadwal makan Anda sendiri.');
            }

            if (! $canManage && ! $employee) {
                $employee = $user->employeeDefault?->load('defaultSite.unit', 'unit');
            }
        } else {
            // Anonim: wajib slug pribadi.
            if ($request->filled('slug')) {
                $employee = EmployeeDefault::with('defaultSite.unit', 'unit')
                    ->where('slug', $request->slug)
                    ->first();
            }

            if ($employee) {
                session(['lunch_self_booking_slug' => $employee->slug]);
            } else {
                session()->forget('lunch_self_booking_slug');
            }
        }

        $startDate = Carbon::parse($request->get('start_date', now()->addDay()->toDateString()));
        $days = max(1, min(60, (int) $request->get('days', 14)));

        $weekHeaders = collect();
        for ($i = 0; $i < 7; $i++) {
            $weekHeaders->push($startDate->copy()->addDays($i)->locale('id')->isoFormat('dddd'));
        }

        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $d = $startDate->copy()->addDays($i);
            $dates->push([
                'date'       => $d->toDateString(),
                'day_name'   => $d->locale('id')->isoFormat('dddd'),
                'day_date'   => $d->locale('id')->isoFormat('D MMMM YYYY'),
                'is_weekend' => $d->isWeekend(),
            ]);
        }

        $start = $startDate->toDateString();
        $end = $startDate->copy()->addDays($days - 1)->toDateString();

        $existingForecasts = $employee
            ? LunchForecast::where('employee_default_id', $employee->id)
                ->whereBetween('lunch_date', [$start, $end])
                ->get()
                ->keyBy(fn ($f) => $f->lunch_date->toDateString())
            : collect();

        foreach ($dates as &$d) {
            $forecast = $existingForecasts->get($d['date']);
            $d['forecast'] = $forecast;
            $d['default_site_id'] = $employee?->default_site_id;
            $d['selected_site_id'] = $forecast?->predicted_site_id ?? $employee?->default_site_id;
            $d['is_self_booked'] = $forecast && $forecast->prediction_source === 'self_booking';
            $d['is_admin_override'] = $forecast && $forecast->prediction_source === 'manual';
            $d['has_forecast'] = $forecast !== null;
        }
        unset($d);

        // Site yang boleh dipilih HANYA site di unit karyawan ini.
        $sites = $employee ? $employee->selectableSites() : collect();

        $employees = $user
            ? $this->employeeQuery($scope)
                ->active()
                ->orderBy('employee_name')
                ->get(['id', 'slug', 'employee_name', 'default_site_id', 'unit_id'])
            : collect();

        return view('lunch.self-booking.calendar', compact(
            'employee', 'dates', 'startDate', 'days',
            'sites', 'employees', 'start', 'end', 'suggestions', 'weekHeaders'
        ));
    }

    /**
     * Simpan booking mandiri — bulk update LunchForecast dengan source self_booking.
     *
     * Site divalidasi terhadap unit karyawan. Ini menutup celah lama di mana
     * pegawai Patuha bisa memilih site Dieng dari halaman publik.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug'               => 'required|exists:employee_defaults,slug',
            'bookings'           => 'required|array|min:1',
            'bookings.*.date'    => 'required|date',
            'bookings.*.site_id' => 'nullable|exists:sites,id',
        ]);

        $employee = EmployeeDefault::with(['defaultSite.unit', 'unit'])
            ->where('slug', $validated['slug'])
            ->firstOrFail();

        $user = Auth::user();
        $scope = UnitScope::for($user);

        if (! $user) {
            if (session('lunch_self_booking_slug') !== $employee->slug) {
                abort(403, 'Silakan buka link booking Anda terlebih dahulu.');
            }
        } else {
            $scope->ensureEmployee($employee);

            if (! $user->hasPermission('lunch.manage')
                && (int) $user->employeeDefault?->id !== (int) $employee->id) {
                abort(403, 'Anda hanya dapat mengubah jadwal makan Anda sendiri.');
            }
        }

        $allowedSiteIds = $employee->selectableSites()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $userId = $user?->id;
        $count = 0;
        $skipped = 0;

        foreach ($validated['bookings'] as $booking) {
            $bookingDate = $booking['date'] ?? null;
            $siteId = ! empty($booking['site_id']) ? (int) $booking['site_id'] : null;

            if (! $bookingDate) {
                continue;
            }

            // Guard unit: tolak site di luar unit karyawan.
            if ($siteId && ! in_array($siteId, $allowedSiteIds, true)) {
                $skipped++;
                continue;
            }

            $existing = LunchForecast::where('employee_default_id', $employee->id)
                ->whereDate('lunch_date', $bookingDate)
                ->first();

            // Override admin tidak pernah ditimpa pegawai.
            if ($existing && $existing->prediction_source === 'manual') {
                $skipped++;
                continue;
            }

            LunchForecast::updateOrCreate(
                [
                    'employee_default_id' => $employee->id,
                    'lunch_date'          => $bookingDate,
                ],
                [
                    'user_id'           => $employee->user_id,
                    'predicted_site_id' => $siteId,
                    'prediction_source' => 'self_booking',
                    'confidence'        => $siteId ? 100.0 : 0.0,
                    'created_by'        => $userId,
                    'updated_by'        => $userId,
                ]
            );
            $count++;
        }

        $message = "Booking untuk {$employee->employee_name} tersimpan ({$count} hari).";
        if ($skipped > 0) {
            $message .= " {$skipped} hari dilewati karena site di luar unit atau sudah di-override admin.";
        }

        return redirect()
            ->route('my.lunch-plan', ['slug' => $employee->slug])
            ->with('success', $message);
    }

    private function employeeQuery(UnitScope $scope)
    {
        return EmployeeDefault::query()->inUnit($scope->siteIds());
    }
}
