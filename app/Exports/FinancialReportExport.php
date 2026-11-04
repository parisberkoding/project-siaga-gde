<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export Excel untuk Laporan Keuangan Service (VPR).
 *
 * Data yang diterima controller SUDAH dibatasi unit (VehiclePartsReplacementTransaction::inUnit),
 * jadi kelas ini hanya memformat — tidak melakukan query sendiri.
 */
class FinancialReportExport
{
    public function __construct(private readonly array $data)
    {
    }

    /**
     * Bangun file Excel di direktori sementara dan kembalikan path-nya.
     * Controller memanggil response()->download($path)->deleteFileAfterSend(true).
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Keuangan');

        $summary = $this->data['summary'] ?? [];
        $month   = (string) ($this->data['month'] ?? now()->format('Y-m'));

        $sheet->setCellValue('A1', 'LAPORAN KEUANGAN SERVICE');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode: '.$month);
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Ringkasan
        $summaryRows = [
            ['Total Biaya', $this->rupiah($summary['total_cost'] ?? 0)],
            ['Jumlah Transaksi', (string) ($summary['total_vpr'] ?? 0)],
            ['Selesai', (string) ($summary['completed_count'] ?? 0)],
            ['Overdue', (string) ($summary['overdue_count'] ?? 0)],
            ['Total Item', (string) ($summary['total_items'] ?? 0)],
            ['Rata-rata per Transaksi', $this->rupiah($summary['avg_cost_per_trx'] ?? 0)],
        ];

        $row = 4;
        $sheet->setCellValue("A{$row}", 'Ringkasan');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($summaryRows as [$label, $value]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $row++;
        }

        // Transaksi
        $row += 2;
        $sheet->setCellValue("A{$row}", 'Rincian Transaksi');
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $headers = ['No', 'Kode Transaksi', 'Tanggal', 'Kendaraan', 'Driver', 'Biaya'];
        foreach ($headers as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue("{$column}{$row}", $header);
            $sheet->getStyle("{$column}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$column}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E2F3');
        }
        $headerRow = $row;
        $row++;

        $transactions = $this->data['transactions'] ?? collect();
        $no = 1;

        foreach ($transactions as $trx) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $trx->vprt_code ?? '-');
            $sheet->setCellValue("C{$row}", optional($trx->service_date)->format('d/m/Y') ?? '-');
            $sheet->setCellValue("D{$row}", $trx->replacement?->nopol ?? '-');
            $sheet->setCellValue("E{$row}", $trx->driver?->nama_lengkap ?? '-');
            $sheet->setCellValue("F{$row}", (float) ($trx->total_amount ?? 0));
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        if ($transactions->isEmpty()) {
            $sheet->setCellValue("A{$row}", 'Tidak ada transaksi pada periode ini.');
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
            $row++;
        }

        $sheet->getStyle("A{$headerRow}:F".($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Kendaraan & item teratas
        $row += 2;
        $sheet->setCellValue("A{$row}", 'Kendaraan dengan Biaya Tertinggi');
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        foreach (($this->data['topVehicles'] ?? collect()) as $vehicle) {
            $sheet->setCellValue("A{$row}", $vehicle->nopol ?? '-');
            $sheet->setCellValue("B{$row}", (string) ($vehicle->trx_count ?? 0).' transaksi');
            $sheet->setCellValue("C{$row}", (float) ($vehicle->total_cost ?? 0));
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $row += 2;
        $sheet->setCellValue("A{$row}", 'Sparepart Paling Banyak Diganti');
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        foreach (($this->data['topItems'] ?? collect()) as $item) {
            $sheet->setCellValue("A{$row}", $item->part_name ?? '-');
            $sheet->setCellValue("B{$row}", (string) ($item->total_qty ?? 0).' unit');
            $sheet->setCellValue("C{$row}", (float) ($item->total_cost ?? 0));
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // Tren 6 bulan
        $trend = $this->data['trendData'] ?? [];
        if (! empty($trend['labels'])) {
            $row += 2;
            $sheet->setCellValue("A{$row}", 'Tren 6 Bulan');
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            $sheet->setCellValue("A{$row}", 'Bulan');
            $sheet->setCellValue("B{$row}", 'Jumlah Transaksi');
            $sheet->setCellValue("C{$row}", 'Total Biaya');
            $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($trend['labels'] as $index => $label) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->setCellValue("B{$row}", (int) ($trend['counts'][$index] ?? 0));
                $sheet->setCellValue("C{$row}", (float) ($trend['costs'][$index] ?? 0));
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $row++;
            }
        }

        foreach (['A' => 6, 'B' => 24, 'C' => 16, 'D' => 18, 'E' => 24, 'F' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $path = $this->tempPath();
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function tempPath(): string
    {
        $tmpDir = storage_path('app/tmp');

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        return $tmpDir.'/laporan-keuangan-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.xlsx';
    }

    private function rupiah($value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
