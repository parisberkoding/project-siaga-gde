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

class BodyCheckingExport
{
    private $spreadsheet;

    public function __construct(
        private VehicleAndToolsConsumers $vehicle,
        private Carbon $month,
        private array $header = []
    ) {}

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

    private function build(): void
    {
        $this->spreadsheet = new Spreadsheet;
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Body Checking');

        // Kolom: A=No, B=Body Part, C=Status, D=Keterangan, E=Foto
        $lastCol = 'E';

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(30);

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5);
        $sheet->getPageMargins()->setBottom(0.5);
        $sheet->getPageMargins()->setLeft(0.4);
        $sheet->getPageMargins()->setRight(0.4);

        $r = $this->buildHeader($sheet, $lastCol);
        $r = $this->buildInfo($sheet, $lastCol, $r);

        $checkings = $this->bodyCheckings();
        if ($checkings->isEmpty()) {
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->setCellValue("A{$r}", 'Belum ada pengecekan body pada periode ini.');
            $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(10);
            $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6C757D');
            $r += 2;
        } else {
            foreach ($checkings as $checking) {
                $r = $this->buildSession($sheet, $lastCol, $r, $checking);
                $r += 1;
            }
        }

        $this->buildSignature($sheet, $lastCol, $r, $checkings);
    }

    private function buildHeader($sheet, string $lastCol): int
    {
        $sheet->getRowDimension(1)->setRowHeight(26);

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

        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']],
            ],
        ]);

        return 3;
    }

    private function buildInfo($sheet, string $lastCol, int $r): int
    {
        $labelStyle = ['font' => ['bold' => true]];

        $sheet->setCellValue("A{$r}", 'Kendaraan');
        $sheet->getStyle("A{$r}")->applyFromArray($labelStyle);
        $sheet->setCellValue("B{$r}", ': '.$this->vehicleLabel());
        $sheet->mergeCells("B{$r}:{$lastCol}{$r}");
        $r++;

        $sheet->setCellValue("A{$r}", 'Periode');
        $sheet->getStyle("A{$r}")->applyFromArray($labelStyle);
        $sheet->setCellValue("B{$r}", ': '.$this->month->translatedFormat('F Y'));
        $sheet->mergeCells("B{$r}:{$lastCol}{$r}");
        $r++;

        $sheet->getRowDimension($r)->setRowHeight(8);
        return $r + 1;
    }

    private function buildSession($sheet, string $lastCol, int $r, $checking): int
    {
        // Session header
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->setCellValue("A{$r}", 'Pengecekan: '.$checking->checked_at->translatedFormat('d/m/Y H:i'));
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1F4E79');
        $sheet->getStyle("A{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(22);
        $r++;

        // Dispatcher & Driver info
        $sheet->setCellValue("A{$r}", 'Dispatcher');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        $sheet->mergeCells("B{$r}:C{$r}");
        $sheet->setCellValue("B{$r}", ': '.($checking->dispatcher->nama_lengkap ?? '-'));
        $sheet->setCellValue("D{$r}", 'Driver');
        $sheet->getStyle("D{$r}")->getFont()->setBold(true);
        $sheet->setCellValue("E{$r}", ': '.($checking->currentDriver->nama_lengkap ?? '-'));
        $sheet->getRowDimension($r)->setRowHeight(16);
        $r++;

        // Separator
        $sheet->getRowDimension($r)->setRowHeight(4);
        $r++;

        // Table header
        $sheet->setCellValue("A{$r}", 'No');
        $sheet->setCellValue("B{$r}", 'Body Part');
        $sheet->setCellValue("C{$r}", 'Status');
        $sheet->setCellValue("D{$r}", 'Keterangan');
        $sheet->setCellValue("E{$r}", 'Foto');
        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->tableHeaderStyle($sheet, "A{$r}:{$lastCol}{$r}", 18);
        $headerRow = $r;
        $r++;

        $idx = 1;
        $startRow = $r;
        $photoDrawings = [];

        foreach ($checking->itemResults as $item) {
            $sheet->setCellValue("A{$r}", $idx++);
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue("B{$r}", $item->vehiclePart->name ?? '-');
            $sheet->setCellValue("C{$r}", $item->getStatusLabel());
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->writeStatusStyle($sheet, "C{$r}", $item->status);

            $notes = $item->notes ?? '';
            if ($item->needs_replacement) {
                $notes = ($notes ? $notes.'; ' : '').'PERLU PENGGANTIAN';
            }
            $sheet->setCellValue("D{$r}", $notes);

            // Check for damage photos linked to this item
            $itemPhotos = $checking->damagePhotos->filter(function ($photo) use ($item, $checking) {
                return $photo->zone === $item->vehiclePart?->category || !$checking->damagePhotos->where('zone', $item->vehiclePart?->category)->count();
            });

            if ($itemPhotos->isNotEmpty()) {
                $sheet->setCellValue("E{$r}", 'Ada foto');
                $sheet->getStyle("E{$r}")->getFont()->setSize(8)->setItalic(true);
                $sheet->getStyle("E{$r}")->getFont()->getColor()->setRGB('0D6EFD');
            } else {
                $sheet->setCellValue("E{$r}", '-');
                $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $bodyRange = 'A'.$startRow.':'.$lastCol.($r - 1);
        $this->bodyBorder($sheet, $bodyRange);

        // Embed photos below the table
        if ($checking->damagePhotos->isNotEmpty()) {
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->setCellValue("A{$r}", 'Foto Kerusakan:');
            $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(10);
            $sheet->getRowDimension($r)->setRowHeight(16);
            $r++;

            foreach ($checking->damagePhotos as $photo) {
                $photoPath = storage_path('app/public/'.$photo->photo);
                $zoneLabel = ['depan' => 'Depan', 'belakang' => 'Belakang', 'kiri' => 'Kiri', 'kanan' => 'Kanan', 'atas' => 'Atas', 'bawah' => 'Bawah', 'interior' => 'Interior'][$photo->zone] ?? $photo->zone;

                $sheet->setCellValue("A{$r}", $zoneLabel);
                $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9);
                $sheet->setCellValue("B{$r}", $photo->description ?? '');
                $sheet->mergeCells("B{$r}:D{$r}");

                if (file_exists($photoPath)) {
                    $drawing = new Drawing;
                    $drawing->setName('Foto kerusakan');
                    $drawing->setPath($photoPath);
                    $drawing->setHeight(80);
                    $drawing->setCoordinates('E'.$r);
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($this->spreadsheet->getActiveSheet());
                    $sheet->getRowDimension($r)->setRowHeight(85);
                } else {
                    $sheet->setCellValue("E{$r}", '[File tidak tersedia]');
                    $sheet->getStyle("E{$r}")->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('999999');
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }
                $r++;
            }
        }

        // Summary row
        $total = $checking->itemResults->count();
        $baik = $checking->itemResults->where('status', 'baik')->count();
        $cukup = $checking->itemResults->where('status', 'cukup')->count();
        $kurang = $checking->itemResults->where('status', 'kurang')->count();
        $tidak = $checking->itemResults->where('status', 'tidak')->count();
        $ganti = $checking->itemResults->where('needs_replacement', true)->count();

        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->setCellValue("A{$r}", "Ringkasan: Baik {$baik} | Cukup {$cukup} | Kurang {$kurang} | Tidak {$tidak} | Perlu Ganti {$ganti}");
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9);
        $sheet->getRowDimension($r)->setRowHeight(16);
        $r++;

        return $r;
    }

    private function buildSignature($sheet, string $lastCol, int $r, $checkings): void
    {
        $r++;
        $top = $r;
        $bottom = $top + 8;

        $acknowledged = $checkings->where('driver_acknowledged', true)->sortByDesc('driver_acknowledged_at')->first();
        $anyChecked = $checkings->isNotEmpty();

        $midCol = 'C';

        // Left: Driver signature
        $sheet->mergeCells("A{$top}:{$midCol}{$bottom}");
        $sheet->setCellValue("A{$top}", 'Tanda Tangan Pengemudi');
        $sheet->getStyle("A{$top}")->getFont()->setBold(true);
        $sheet->getStyle("A{$top}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_TOP);
        if ($acknowledged) {
            $sheet->setCellValue("A{$bottom}", 'Diketahui: '.($acknowledged->currentDriver?->nama_lengkap ?? '-'));
            $sheet->getStyle("A{$bottom}")->getFont()->setSize(9);
        } else {
            $sheet->setCellValue("A{$bottom}", '(diisi manual)');
            $sheet->getStyle("A{$bottom}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6C757D');
        }
        $sheet->getStyle("A{$bottom}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$top}:{$midCol}{$bottom}")->getBorders()->applyFromArray([
            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]);

        // Right: Dispatcher signature
        $sheet->mergeCells("D{$top}:{$lastCol}{$bottom}");
        $sheet->setCellValue("D{$top}", 'Tanda Tangan Dispatcher');
        $sheet->getStyle("D{$top}")->getFont()->setBold(true);
        $sheet->getStyle("D{$top}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_TOP);
        if ($anyChecked) {
            $latest = $checkings->sortByDesc('checked_at')->first();
            $sheet->setCellValue("D{$bottom}", 'Diperiksa: '.($latest->dispatcher?->nama_lengkap ?? '-'));
            $sheet->getStyle("D{$bottom}")->getFont()->setSize(9);
        } else {
            $sheet->setCellValue("D{$bottom}", '(diisi manual)');
            $sheet->getStyle("D{$bottom}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6C757D');
        }
        $sheet->getStyle("D{$bottom}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$top}:{$lastCol}{$bottom}")->getBorders()->applyFromArray([
            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]);

        // Date rows below
        $nameRow = $bottom + 1;
        if ($acknowledged) {
            $sheet->setCellValue("A{$nameRow}", 'Tanggal: '.$acknowledged->driver_acknowledged_at->translatedFormat('d F Y'));
        } else {
            $sheet->setCellValue("A{$nameRow}", 'Nama & Tanggal:');
        }
        $sheet->getStyle("A{$nameRow}")->getFont()->setSize(9);
        if ($anyChecked) {
            $latest = $checkings->sortByDesc('checked_at')->first();
            $sheet->setCellValue("D{$nameRow}", 'Tanggal: '.$latest->checked_at->translatedFormat('d F Y'));
        } else {
            $sheet->setCellValue("D{$nameRow}", 'Nama & Tanggal:');
        }
        $sheet->getStyle("D{$nameRow}")->getFont()->setSize(9);
    }

    private function bodyCheckings()
    {
        return VehicleCheckingDetail::with([
            'vehicleChecking.vehicle', 'dispatcher', 'currentDriver',
            'itemResults.vehiclePart', 'damagePhotos',
        ])
            ->where('type', 'body')
            ->whereHas('vehicleChecking', fn ($q) => $q->where('vehicle_id', $this->vehicle->id))
            ->whereBetween('checked_at', [
                $this->month->copy()->startOfMonth()->toDateString().' 00:00:00',
                $this->month->copy()->endOfMonth()->toDateString().' 23:59:59',
            ])
            ->orderBy('checked_at', 'desc')
            ->get();
    }

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

    private function writeStatusStyle($sheet, string $cell, string $status): void
    {
        $colors = [
            'baik' => ['rgb' => '1E7E34'],
            'cukup' => ['rgb' => '0DCAF0'],
            'kurang' => ['rgb' => 'FFC107'],
            'tidak' => ['rgb' => 'C00000'],
        ];
        $color = $colors[$status] ?? ['rgb' => '000000'];
        $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle($cell)->getFont()->getColor()->setRGB($color['rgb']);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

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