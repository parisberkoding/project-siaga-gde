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
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Public access - show page immediately
        // Only require employee data when name is provided
        $employee = null;
        $suggestions = collect();

        if ($request->filled('slug')) {
            $employee = EmployeeDefault::with('defaultSite')->where('slug', $request->slug)->first();
        } elseif ($request->filled('name')) {
            // Public name-based lookup - search both employee_name and aliases
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
        } elseif ($user && $user->employeeDefault) {
            $employee = $user->employeeDefault->load('defaultSite');
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
        $employees = EmployeeDefault::active()->orderBy('employee_name')->get(['id', 'slug', 'employee_name', 'default_site_id']);

        return view('lunch.self-booking.calendar', compact(
            'employee', 'dates', 'startDate', 'days',
            'sites', 'employees', 'start', 'end', 'suggestions', 'weekHeaders'
        ));
    }

    /**
     * Simpan booking mandiri — bulk update LunchForecast dengan source self_booking.
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
