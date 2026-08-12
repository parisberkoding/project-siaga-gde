<?php

namespace App\Exports;

use App\Models\Approved_Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export Excel daftar voucher + tracking approval.
 *
 * Menerima koleksi voucher (Approved_Request) yang sudah di-load relasi:
 * permohonan.requester, permohonan.vehicle, authorizer.
 */
class VoucherExport
{
    public function __construct(
        private Collection $vouchers,
        private ?string $filterDate = null
    ) {}

    public function download(string $fileName)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Voucher');

        $this->buildSheet($sheet);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function buildSheet($sheet): void
    {
        $headers = [
            'No. Request',
            'Pemohon',
            'Kode Kendaraan',
            'Nama Kendaraan / Alat',
            'Tipe',
            'Jenis BBM',
            'Nominal (Rp)',
            'Kode Voucher',
            'Status Voucher',
            'Status Permohonan',
            'Approver',
            'Tanggal Approval',
            'Valid Sampai',
            'Catatan Approval',
        ];

        // Header row
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4154F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $rows = [];
        foreach ($this->vouchers as $voucher) {
            $permohonan = $voucher->permohonan;
            $vehicle = $permohonan?->vehicle;
            $isExpired = $voucher->valid_until && Carbon::parse($voucher->valid_until)->isPast();
            $voucherStatus = $isExpired ? 'EXPIRED' : strtoupper($voucher->status);

            $rows[] = [
                $permohonan?->request_number ?? '-',
                $permohonan?->requester?->nama_lengkap ?? '-',
                $vehicle?->consumerial_code ?? '-',
                $vehicle?->consumerial_name ?? '-',
                $vehicle?->consumerial_type ?? '-',
                $permohonan?->gasoline_type ?? '-',
                (float) ($voucher->approved_amount ?? 0),
                $voucher->voucher_code,
                $voucherStatus,
                strtoupper($permohonan?->status ?? '-'),
                $voucher->authorizer?->nama_lengkap ?? '-',
                $voucher->approval_date ? Carbon::parse($voucher->approval_date)->format('d/m/Y H:i') : '-',
                $voucher->valid_until ? Carbon::parse($voucher->valid_until)->format('d/m/Y H:i') : '-',
                $voucher->approval_notes ?? $permohonan?->authorizer_notes ?? '',
            ];
        }

        $sheet->fromArray($rows, null, 'A2');

        // Body borders + nominal format
        $lastRow = $sheet->getHighestDataRow();
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A2:'.$lastCol.$lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('G2:G'.$lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');
        $sheet->getStyle('A2:'.$lastCol.$lastRow)->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP);

        // Auto width
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);

        // Freeze header
        $sheet->freezePane('A2');
    }
}
