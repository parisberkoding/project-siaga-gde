<?php

namespace App\Exports;

use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleCheckingDetail;
use App\Models\VehiclePart;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export Excel "Form Pengecekan Dispatcher" per kendaraan per bulan.
 *
 * Layout mengikuti format cetak manual:
 *  - Kop & logo perusahaan (bisa dicustom via config/env atau per-export);
 *  - Kendaraan, Pengemudi (kosong — diisi tulis tangan), Periode;
 *  - Pengecekan Harian  : baris = item harian, kolom = Hari 1..N;
 *  - Pengecekan Mingguan: baris = item mingguan, kolom = tanggal pengecekan;
 *  - Pengecekan Bulanan : baris = item bulanan, kolom = tanggal/status/ket.;
 *  - Tanda tangan Driver & Dispatcher.
 */
class DispatcherCheckingExport
{
    /** @var Spreadsheet */
    private $spreadsheet;

    public function __construct(
        private VehicleAndToolsConsumers $vehicle,
        private Carbon $month,
        private array $header = []
    ) {}

    /**
     * Stream download file xlsx.
     */
    public function download(string $fileName)
    {
        $this->build();

        $writer = new Xlsx($this->spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    //  BUILD — susun seluruh sheet
    // ════════════════════════════════════════════════════════════════

    private function build(): void
    {
        $this->spreadsheet = new Spreadsheet;
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Form Pengecekan');

        $daysInMonth = $this->month->daysInMonth;
        $lastCol = Coordinate::stringFromColumnIndex(2 + $daysInMonth); // A=No, B=Item, C..=hari

        // ── Lebar kolom ──
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(46);
        foreach (range(3, 2 + $daysInMonth) as $i) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(4.6);
        }

        // ── Pengaturan cetak: A4 landscape, fit-to-width ──
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5);
        $sheet->getPageMargins()->setBottom(0.5);
        $sheet->getPageMargins()->setLeft(0.4);
        $sheet->getPageMargins()->setRight(0.4);

        $r = $this->buildHeader($sheet, $lastCol);
        $r = $this->buildInfo($sheet, $lastCol, $r);
        $r = $this->buildDaily($sheet, $lastCol, $daysInMonth, $r);
        $r = $this->buildWeekly($sheet, $lastCol, $r);
        $r = $this->buildMonthly($sheet, $lastCol, $r);
        $this->buildSignature($sheet, $lastCol, $r);

        // Cell default: vertical center + wrap item
        $sheet->getStyle('B1:'.$lastCol.($sheet->getHighestRow()))->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    // ════════════════════════════════════════════════════════════════
    //  KOP LAPORAN
    // ════════════════════════════════════════════════════════════════

    private function buildHeader($sheet, string $lastCol): int
    {
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Logo perusahaan (customizable)
        $logoPath = $this->logoPath();
        if ($logoPath !== '') {
            $drawing = new Drawing;
            $drawing->setName('Logo Perusahaan');
            $drawing->setDescription('Logo Perusahaan');
            $drawing->setPath($logoPath);
            $drawing->setHeight(66);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($this->spreadsheet->getActiveSheet());
        }

        $sheet->mergeCells("B1:{$lastCol}1");
        $sheet->setCellValue('B1', $this->companyName());
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("B2:{$lastCol}2");
        $sheet->setCellValue('B2', $this->unitName().' — '.$this->appName());
        $sheet->getStyle('B2')->getFont()->setSize(10);
        $sheet->getStyle('B2')->getFont()->getColor()->setRGB('6C757D');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Garis pemisah kop
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']],
            ],
        ]);

        return 3;
    }

    // ════════════════════════════════════════════════════════════════
    //  INFO KENDARAAN / PENGEMUDI / PERIODE
    // ════════════════════════════════════════════════════════════════

    private function buildInfo($sheet, string $lastCol, int $r): int
    {
        $labelStyle = ['font' => ['bold' => true]];

        // Kendaraan
        $sheet->setCellValue("A{$r}", 'Kendaraan');
        $sheet->getStyle("A{$r}")->applyFromArray($labelStyle);
        $sheet->setCellValue("B{$r}", ': '.$this->vehicleLabel());
        $sheet->mergeCells("B{$r}:{$lastCol}{$r}");
        $r++;

        // Pengemudi — dibiarkan kosong untuk diisi manual / tulis tangan
        $sheet->setCellValue("A{$r}", 'Pengemudi');
        $sheet->getStyle("A{$r}")->applyFromArray($labelStyle);
        $sheet->setCellValue("B{$r}", ': ');
        $sheet->mergeCells("B{$r}:{$lastCol}{$r}");
        $r++;

        // Periode
        $sheet->setCellValue("A{$r}", 'Periode');
        $sheet->getStyle("A{$r}")->applyFromArray($labelStyle);
        $sheet->setCellValue("B{$r}", ': '.$this->month->translatedFormat('F Y'));
        $sheet->mergeCells("B{$r}:{$lastCol}{$r}");
        $r++;

        // Baris kosong
        $sheet->getRowDimension($r)->setRowHeight(8);

        return $r + 1;
    }

    // ════════════════════════════════════════════════════════════════
    //  PENGECEKAN HARIAN
    // ════════════════════════════════════════════════════════════════

    private function buildDaily($sheet, string $lastCol, int $daysInMonth, int $r): int
    {
        $r = $this->sectionTitle($sheet, $lastCol, $r, 'PENGECEKAN HARIAN');

        // Header kolom: No | Item | Hari 1..N
        $sheet->setCellValue("A{$r}", 'No');
        $sheet->setCellValue("B{$r}", 'Item Pengecekan');
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $col = Coordinate::stringFromColumnIndex(2 + $day);
            $sheet->setCellValue($col.$r, (string) $day);
            $sheet->getStyle($col.$r)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->tableHeaderStyle($sheet, "A{$r}:{$lastCol}{$r}", 20);
        $r++;

        $parts = $this->parts('daily');
        $dailyMap = $this->dailyMap();

        $idx = 1;
        foreach ($parts as $part) {
            $sheet->setCellValue("A{$r}", $idx++);
            $sheet->setCellValue("B{$r}", $part->name);
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $col = Coordinate::stringFromColumnIndex(2 + $day);
                $status = $dailyMap[$part->id][$day] ?? null;
                if ($status) {
                    $this->writeStatus($sheet, $col.$r, $status);
                }
            }
            $sheet->getRowDimension($r)->setRowHeight(18);
            $r++;
        }

        $this->bodyBorder($sheet, 'A'.($r - $idx).':'.$lastCol.($r - 1));

        // Legenda
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->setCellValue("A{$r}", 'Keterangan: V = Baik / OK, X = Rusak / NG. Kosong berarti belum dicek.');
        $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6C757D');
        $r++;

        return $r + 1;
    }

    // ════════════════════════════════════════════════════════════════
    //  PENGECEKAN MINGGUAN
    // ════════════════════════════════════════════════════════════════

    private function buildWeekly($sheet, string $lastCol, int $r): int
    {
        $r = $this->sectionTitle($sheet, $lastCol, $r, 'PENGECEKAN MINGGUAN');

        $weekDates = $this->weeklyDates();
        $weeklyMap = $this->weeklyMap();
        $parts = $this->parts('weekly');

        if (empty($weekDates)) {
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->setCellValue("A{$r}", 'Belum ada pengecekan mingguan pada periode ini.');
            $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6C757D');

            return $r + 2;
        }

        // Header: No | Item | <tanggal minggu>
        $sheet->setCellValue("A{$r}", 'No');
        $sheet->setCellValue("B{$r}", 'Item Pengecekan');
        $weekColStart = 3;
        foreach ($weekDates as $i => $date) {
            $col = Coordinate::stringFromColumnIndex($weekColStart + $i);
            $sheet->setCellValue($col.$r, $date->translatedFormat('d/m'));
            $sheet->getStyle($col.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $weekLastCol = Coordinate::stringFromColumnIndex($weekColStart + count($weekDates) - 1);
        $this->tableHeaderStyle($sheet, "A{$r}:{$weekLastCol}{$r}", 20);
        $r++;

        $idx = 1;
        foreach ($parts as $part) {
            $sheet->setCellValue("A{$r}", $idx++);
            $sheet->setCellValue("B{$r}", $part->name);
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach ($weekDates as $i => $date) {
                $col = Coordinate::stringFromColumnIndex($weekColStart + $i);
                $status = $weeklyMap[$part->id][$date->toDateString()] ?? null;
                if ($status) {
                    $this->writeStatus($sheet, $col.$r, $status);
                }
            }
            $sheet->getRowDimension($r)->setRowHeight(18);
            $r++;
        }

        $this->bodyBorder($sheet, 'A'.($r - $idx).':'.$weekLastCol.($r - 1));

        // Legenda
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->setCellValue("A{$r}", 'Keterangan: V = Baik / OK, X = Rusak / NG.');
        $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6C757D');
        $r++;

        return $r + 1;
    }

    // ════════════════════════════════════════════════════════════════
    //  PENGECEKAN BULANAN
    // ════════════════════════════════════════════════════════════════

    private function buildMonthly($sheet, string $lastCol, int $r): int
    {
        $r = $this->sectionTitle($sheet, $lastCol, $r, 'PENGECEKAN BULANAN');

        $monthlyMap = $this->monthlyMap();
        $parts = $this->parts('monthly');

        if (empty($parts)) {
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->setCellValue("A{$r}", 'Tidak ada item pengecekan bulanan.');
            $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6C757D');

            return $r + 2;
        }

        // Header: No | Item | Tanggal | Status | Keterangan
        $sheet->setCellValue("A{$r}", 'No');
        $sheet->setCellValue("B{$r}", 'Item Pengecekan');
        $sheet->setCellValue("C{$r}", 'Tanggal');
        $sheet->setCellValue("D{$r}", 'Status');
        $sheet->setCellValue("E{$r}", 'Keterangan');
        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->mergeCells("E{$r}:{$lastCol}{$r}");
        $this->tableHeaderStyle($sheet, "A{$r}:{$lastCol}{$r}", 20);
        $r++;

        $idx = 1;
        foreach ($parts as $part) {
            $row = $monthlyMap[$part->id] ?? null;

            $sheet->setCellValue("A{$r}", $idx++);
            $sheet->setCellValue("B{$r}", $part->name);
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($row) {
                $sheet->setCellValue("C{$r}", $row['date'] ? $row['date']->translatedFormat('d/m/Y') : '-');
                $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $this->writeStatus($sheet, "D{$r}", $row['status']);
                $sheet->setCellValue("E{$r}", $row['notes'] ?? '');
                $sheet->mergeCells("E{$r}:{$lastCol}{$r}");
            }

            $sheet->getRowDimension($r)->setRowHeight(18);
            $r++;
        }

        $this->bodyBorder($sheet, 'A'.($r - $idx).':'.$lastCol.($r - 1));

        // Legenda
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->setCellValue("A{$r}", 'Keterangan: V = Baik / OK, X = Rusak / NG.');
        $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6C757D');
        $r++;

        return $r + 1;
    }

    // ════════════════════════════════════════════════════════════════
    //  TANDA TANGAN
    // ════════════════════════════════════════════════════════════════

    private function buildSignature($sheet, string $lastCol, int $r): void
    {
        $r++;

        $top = $r;
        $bottom = $top + 9;

        $checkings = $this->allCheckingDetails();
        $acknowledged = $checkings->where('driver_acknowledged', true)->sortByDesc('driver_acknowledged_at')->first();
        $anyChecked = $checkings->isNotEmpty();

        $leftEnd = Coordinate::stringFromColumnIndex((int) round(Coordinate::columnIndexFromString($lastCol) / 2));

        // Kotak kiri: Driver
        $sheet->mergeCells("A{$top}:{$leftEnd}{$bottom}");
        $sheet->setCellValue("A{$top}", 'Tanda Tangan Driver');
        $sheet->getStyle("A{$top}")->getFont()->setBold(true);
        $sheet->getStyle("A{$top}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_TOP);
        if ($acknowledged) {
            $sheet->setCellValue("A{$bottom}", 'Sudah diketahui oleh '.$acknowledged->currentDriver?->nama_lengkap ?? '-');
            $sheet->getStyle("A{$bottom}")->getFont()->setItalic(false)->setSize(9);
            $sheet->getStyle("A{$bottom}")->getFont()->getColor()->setRGB('000000');
        } else {
            $sheet->setCellValue("A{$bottom}", '(diisi manual / tulis tangan)');
            $sheet->getStyle("A{$bottom}")->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle("A{$bottom}")->getFont()->getColor()->setRGB('6C757D');
        }
        $sheet->getStyle("A{$bottom}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$top}:{$leftEnd}{$bottom}")->getBorders()->applyFromArray([
            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]);

        // Kotak kanan: Dispatcher
        $rightStart = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($leftEnd) + 1);
        $sheet->mergeCells("{$rightStart}{$top}:{$lastCol}{$bottom}");
        $sheet->setCellValue("{$rightStart}{$top}", 'Tanda Tangan Dispatcher');
        $sheet->getStyle("{$rightStart}{$top}")->getFont()->setBold(true);
        $sheet->getStyle("{$rightStart}{$top}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_TOP);
        if ($anyChecked) {
            $latest = $checkings->sortByDesc('checked_at')->first();
            $sheet->setCellValue("{$rightStart}{$bottom}", 'Sudah diperiksa oleh '.$latest->dispatcher?->nama_lengkap ?? '-');
            $sheet->getStyle("{$rightStart}{$bottom}")->getFont()->setItalic(false)->setSize(9);
            $sheet->getStyle("{$rightStart}{$bottom}")->getFont()->getColor()->setRGB('000000');
        } else {
            $sheet->setCellValue("{$rightStart}{$bottom}", '(diisi manual / tulis tangan)');
            $sheet->getStyle("{$rightStart}{$bottom}")->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle("{$rightStart}{$bottom}")->getFont()->getColor()->setRGB('6C757D');
        }
        $sheet->getStyle("{$rightStart}{$bottom}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$rightStart}{$top}:{$lastCol}{$bottom}")->getBorders()->applyFromArray([
            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]);

        // Baris nama di bawah
        $nameRow = $bottom + 1;
        if ($acknowledged) {
            $sheet->setCellValue("A{$nameRow}", 'Tanggal: '.$acknowledged->driver_acknowledged_at->translatedFormat('d F Y'));
        } else {
            $sheet->setCellValue("A{$nameRow}", 'Nama & Tanggal:');
        }
        $sheet->getStyle("A{$nameRow}")->getFont()->setSize(9);
        if ($anyChecked) {
            $latest = $checkings->sortByDesc('checked_at')->first();
            $sheet->setCellValue("{$rightStart}{$nameRow}", 'Tanggal: '.$latest->checked_at->translatedFormat('d F Y'));
        } else {
            $sheet->setCellValue("{$rightStart}{$nameRow}", 'Nama & Tanggal:');
        }
        $sheet->getStyle("{$rightStart}{$nameRow}")->getFont()->setSize(9);
    }

    private function allCheckingDetails()
    {
        return VehicleCheckingDetail::with(['dispatcher', 'currentDriver'])
            ->where('type', 'routine')
            ->whereHas('vehicleChecking', fn ($q) => $q->where('vehicle_id', $this->vehicle->id))
            ->whereBetween('checked_at', [
                $this->month->copy()->startOfMonth()->toDateString().' 00:00:00',
                $this->month->copy()->endOfMonth()->toDateString().' 23:59:59',
            ])
            ->orderBy('checked_at')
            ->get();
    }

    // ════════════════════════════════════════════════════════════════
    //  DATA
    // ════════════════════════════════════════════════════════════════

    private function parts(string $frequency): array
    {
        return VehiclePart::query()
            ->active()
            ->routine()
            ->where('check_frequency', $frequency)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    private function detailRows(string $subType)
    {
        return VehicleCheckingDetail::with(['itemResults'])
            ->where('type', 'routine')
            ->where('sub_type', $subType)
            ->whereHas('vehicleChecking', fn ($q) => $q->where('vehicle_id', $this->vehicle->id))
            ->whereBetween('checked_at', [
                $this->month->copy()->startOfMonth()->toDateString().' 00:00:00',
                $this->month->copy()->endOfMonth()->toDateString().' 23:59:59',
            ])
            ->orderBy('checked_at')
            ->get();
    }

    private function dailyMap(): array
    {
        $map = [];
        foreach ($this->detailRows('daily') as $detail) {
            $day = $detail->checked_at->day;
            foreach ($detail->itemResults as $item) {
                $map[$item->vehicle_part_id][$day] = $item->status;
            }
        }

        return $map;
    }

    private function weeklyDates(): array
    {
        return $this->detailRows('weekly')
            ->map(fn ($d) => $d->checked_at->copy()->startOfWeek())
            ->unique(fn ($c) => $c->toDateString())
            ->sortBy(fn ($c) => $c->toDateString())
            ->values()
            ->all();
    }

    private function weeklyMap(): array
    {
        $map = [];
        foreach ($this->detailRows('weekly') as $detail) {
            $key = $detail->checked_at->copy()->startOfWeek()->toDateString();
            foreach ($detail->itemResults as $item) {
                $map[$item->vehicle_part_id][$key] = $item->status;
            }
        }

        return $map;
    }

    private function monthlyMap(): array
    {
        $map = [];
        foreach ($this->detailRows('monthly') as $detail) {
            foreach ($detail->itemResults as $item) {
                // Pengecekan terakhir menang (detail diurutkan ascending)
                $map[$item->vehicle_part_id] = [
                    'date' => $detail->checked_at,
                    'status' => $item->status,
                    'notes' => $item->notes,
                ];
            }
        }

        return $map;
    }

    // ════════════════════════════════════════════════════════════════
    //  STYLE HELPERS
    // ════════════════════════════════════════════════════════════════

    private function sectionTitle($sheet, string $lastCol, int $r, string $title): int
    {
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->setCellValue("A{$r}", $title);
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(12)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1F4E79');
        $sheet->getStyle("A{$r}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(20);

        return $r + 1;
    }

    private function tableHeaderStyle($sheet, string $range, int $height): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1F4E79'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '9DC3E6'],
                ],
            ],
        ]);

        $startCell = explode(':', $range)[0];
        $row = Coordinate::coordinateFromString($startCell)[1];
        $sheet->getRowDimension((int) $row)->setRowHeight($height);
    }

    private function bodyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B4C6E7'],
                ],
            ],
        ]);
    }

    private function writeStatus($sheet, string $cell, string $status): void
    {
        $isOk = in_array($status, ['ok', 'baik']);
        $sheet->setCellValue($cell, $isOk ? 'V' : 'X');
        $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($cell)->getFont()->getColor()->setRGB($isOk ? '1E7E34' : 'C00000');
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // ════════════════════════════════════════════════════════════════
    //  HEADER CONFIG (customizable)
    // ════════════════════════════════════════════════════════════════

    private function companyName(): string
    {
        return $this->header['company_name'] ?? config('siaga.report.company_name');
    }

    private function unitName(): string
    {
        return $this->header['unit_name'] ?? config('siaga.report.unit_name');
    }

    private function appName(): string
    {
        return $this->header['app_name'] ?? config('siaga.report.app_name');
    }

    private function logoPath(): string
    {
        if (! empty($this->header['logo_path']) && is_file($this->header['logo_path'])) {
            return $this->header['logo_path'];
        }

        $default = config('siaga.report.logo_path');

        return is_string($default) && is_file($default) ? $default : '';
    }

    private function vehicleLabel(): string
    {
        $merk = $this->vehicle->detail?->merk;
        $model = $this->vehicle->detail?->model;
        $brand = trim(implode(' ', array_filter([$merk, $model])));

        if ($brand === '') {
            $brand = $this->vehicle->consumerial_name;
        }

        return trim($this->vehicle->consumerial_code.' - '.$brand);
    }
}
