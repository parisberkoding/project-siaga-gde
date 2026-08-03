<?php

namespace App\Exports;

use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchGuestReservation;
use App\Models\LunchTransaction;
use App\Models\Site;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export Excel catering untuk vendor (3 sheet).
 *
 * Sumber data sepenuhnya modul Lunch V2:
 *  - lunch_distributions (snapshot terkunci) atau forecast bila snapshot
 *    belum dibuat — TIDAK lagi membaca trip/vehicle;
 *  - lunch_guest_reservations untuk tamu;
 *  - lunch_forecasts + lunch_transactions untuk detail per orang.
 */
class CateringExport
{
    public function __construct(
        private string $startDate,
        private string $endDate,
        private ?int   $siteId = null
    ) {}

    public function download(string $fileName)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // hapus sheet default

        $this->buildOrderSheet($spreadsheet);
        $this->buildGuestSheet($spreadsheet);
        $this->buildDetailSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function sites()
    {
        return Site::active()
            ->when($this->siteId, fn ($q) => $q->where('id', $this->siteId))
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHEET 1: Order Catering
    // ─────────────────────────────────────────────────────────────────────────

    private function buildOrderSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Order Catering');

        $headers = ['Tanggal', 'SITE', 'Jumlah Karyawan', 'Jumlah Tamu', 'Penambahan (10%)', 'Total', 'Status'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, 'A1:G1');
        $this->autoWidth($sheet, ['A' => 14, 'B' => 12, 'C' => 18, 'D' => 14, 'E' => 18, 'F' => 10, 'G' => 14]);

        $sites = $this->sites();
        $row   = 2;

        foreach (CarbonPeriod::create($this->startDate, $this->endDate) as $date) {
            $dateStr = $date->toDateString();

            $distributions = LunchDistribution::forDate($dateStr)->get()->keyBy('site_id');

            $forecastBySite = LunchForecast::forDate($dateStr)
                ->whereNotNull('predicted_site_id')
                ->selectRaw('predicted_site_id, count(*) as total')
                ->groupBy('predicted_site_id')
                ->pluck('total', 'predicted_site_id');

            $guestBySite = LunchGuestReservation::whereDate('reservation_date', $dateStr)
                ->where('status', 'confirmed')
                ->selectRaw('site_id, sum(guest_count) as total')
                ->groupBy('site_id')
                ->pluck('total', 'site_id');

            foreach ($sites as $site) {
                $distribution = $distributions->get($site->id);

                // Snapshot distribusi (terkunci) menang atas hitung ulang.
                $karyawan = $distribution
                    ? (int) $distribution->employee_count
                    : (int) ($forecastBySite[$site->id] ?? 0);
                $tamu = $distribution
                    ? (int) $distribution->guest_count
                    : (int) ($guestBySite[$site->id] ?? 0);

                $penambahan = (int) ceil(($karyawan + $tamu) * 0.10);

                $sheet->fromArray([
                    Carbon::parse($dateStr)->format('d/m/Y'),
                    $site->code,
                    $karyawan,
                    $tamu,
                    $penambahan,
                    $karyawan + $tamu + $penambahan,
                    $distribution?->is_locked ? 'Terkunci' : 'Draft',
                ], null, "A{$row}");

                $row++;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHEET 2: Data Tamu
    // ─────────────────────────────────────────────────────────────────────────

    private function buildGuestSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Tamu');

        $headers = ['Tanggal', 'Instansi / Nama Tamu', 'SITE', 'Jumlah', 'Status', 'Keterangan'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, 'A1:F1');
        $this->autoWidth($sheet, ['A' => 14, 'B' => 30, 'C' => 12, 'D' => 10, 'E' => 12, 'F' => 30]);

        $guests = LunchGuestReservation::with('site')
            ->whereDate('reservation_date', '>=', $this->startDate)
            ->whereDate('reservation_date', '<=', $this->endDate)
            ->when($this->siteId, fn ($q) => $q->where('site_id', $this->siteId))
            ->orderBy('reservation_date')
            ->orderBy('site_id')
            ->get();

        $row = 2;
        foreach ($guests as $guest) {
            $sheet->fromArray([
                Carbon::parse($guest->reservation_date)->format('d/m/Y'),
                $guest->institution ?? '-',
                $guest->site->code ?? '-',
                $guest->guest_count ?? 0,
                $guest->statusLabel(),
                $guest->notes ?? '-',
            ], null, "A{$row}");
            $row++;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHEET 3: Detail Per Orang
    // ─────────────────────────────────────────────────────────────────────────

    private function buildDetailSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detail Per Orang');

        $headers = [
            'Tanggal', 'Nama', 'Divisi', 'Jabatan',
            'Site Prediksi', 'Site Aktual (QR)', 'Sumber Prediksi', 'Confidence', 'Keterangan',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, 'A1:I1');
        $this->autoWidth($sheet, [
            'A' => 14, 'B' => 28, 'C' => 20, 'D' => 20,
            'E' => 14, 'F' => 16, 'G' => 18, 'H' => 12, 'I' => 22,
        ]);

        $forecasts = LunchForecast::with(['employee', 'user', 'predictedSite', 'actualSite'])
            ->whereDate('lunch_date', '>=', $this->startDate)
            ->whereDate('lunch_date', '<=', $this->endDate)
            ->when($this->siteId, function ($q) {
                $q->where(fn ($sq) => $sq
                    ->where('predicted_site_id', $this->siteId)
                    ->orWhere('actual_site_id', $this->siteId));
            })
            ->orderBy('lunch_date')
            ->get();

        // Kehadiran QR per (employee, tanggal) untuk kolom keterangan.
        $attendanceKeys = LunchTransaction::resolved()
            ->whereDate('transaction_date', '>=', $this->startDate)
            ->whereDate('transaction_date', '<=', $this->endDate)
            ->get(['employee_default_id', 'transaction_date'])
            ->map(fn ($t) => $t->employee_default_id . '|' . $t->transaction_date->toDateString())
            ->flip();

        $row = 2;
        foreach ($forecasts as $record) {
            $dateStr  = Carbon::parse($record->lunch_date)->toDateString();
            $attended = $record->employee_default_id
                && $attendanceKeys->has($record->employee_default_id . '|' . $dateStr);

            $sheet->fromArray([
                Carbon::parse($record->lunch_date)->format('d/m/Y'),
                $record->employee?->employee_name ?? $record->user?->nama_lengkap ?? '-',
                $record->employee?->division ?? '-',
                $record->employee?->position ?? $record->user?->jabatan ?? '-',
                $record->predictedSite?->code ?? '-',
                $record->actualSite?->code ?? '-',
                $record->sourceLabel(),
                $record->confidence !== null ? $record->confidence . '%' : '-',
                $attended ? 'Hadir via QR' : 'Belum scan',
            ], null, "A{$row}");

            $row++;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function styleHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2D6A4F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
    }

    private function autoWidth(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $columns): void
    {
        foreach ($columns as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }
}
