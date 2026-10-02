<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Tugas - {{ $surat->nomor_surat }}</title>
    <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

  <!-- GDE UI Templates — design system Geodipa (dimuat setelah NiceAdmin agar menimpanya) -->
  <link href="{{ asset('gde-ui-templates/fonts/poppins.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-tokens.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-base.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-components.css') }}" rel="stylesheet">

    <style>
        /* Reset & General */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.25;  /* Compressed */
            color: #000;
            margin: 0;
            padding: 0;
        }

        .budget-form-wrapper {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #16202E;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        .container {
            width: 100%;
            padding: 0;
        }

        /* HEADER - Compact */
        .header {
            padding: 20px;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .logo {
            width: 80px;
            height: auto;
            flex-shrink: 0;
        }

        .header-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }

        .company-unit {
            font-size: 15px;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }

        .address {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }

        /* Title - Compact */
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin: 12px 0 6px 0;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .nomor {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 15px;
        }

        /* Content - Compressed spacing */
        .content {
            margin: 10px 0;
        }

        .intro {
            margin: 10px 0;
            font-weight: bold;
        }

        /* Table - Compact */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .info-table td {
            padding: 3px 0;  /* Reduced from 5px */
            vertical-align: top;
        }

        .info-table .label {
            width: 170px;
            padding-right: 8px;
        }

        .info-table .colon {
            width: 12px;
            text-align: center;
        }

        .info-table .value {
            border-bottom: 1px dotted #000;
            min-height: 16px;
            padding-left: 4px;
        }

        /* Nama Lengkap - Compact */
        .nama-lengkap-table {
            width: calc(100% - 190px);
            margin-left: 190px;
            margin-top: 3px;
        }

        .nama-lengkap-table td {
            padding: 2px 0;  /* Compressed */
            vertical-align: top;
        }

        .nama-lengkap-table .number {
            width: 28px;
        }

        .nama-lengkap-table .name {
            border-bottom: 1px dotted #000;
            min-height: 16px;
            padding-left: 4px;
        }

        /* Closing Text */
        .closing-text {
            margin-top: 12px;
            text-align: justify;
        }

        /* Signature Section - Compact */
        .signature-section {
            margin-top: 20px;  /* Reduced from 30px */
            text-align: right;
            margin-right: 40px;
        }

        .signature-location {
            margin-bottom: 4px;
        }

        .signature-title {
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 50px;  /* Reduced from 70px */
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Page Break */
        .page-break {
            page-break-after: always;
        }

        /* Print Buttons */
        .no-print {
            position: sticky;
            top: 12px;
            z-index: 100;
            margin: 0 0 20px;
            padding: 12px 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
        }

        .toolbar {
            max-width: 21cm;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .toolbar-title .doc-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.03em;
            border-radius: 999px;
            white-space: nowrap;
        }

        .toolbar-title .doc-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .act-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
            padding: 10px 18px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .act-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .act-btn-print {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 1px 3px rgba(22, 163, 74, 0.35);
        }

        .act-btn-print:hover {
            background: #15803d;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(22, 163, 74, 0.3);
        }

        .act-btn-close {
            background: #ffffff;
            color: #374151;
            border-color: #d1d5db;
        }

        .act-btn-close:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }

        /* Print Specific Styles */
       @media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    body {
        padding: 0;
        background: white;
        color-adjust: exact;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .container {
        padding: 2cm 2cm;
        max-width: 100%;
        color-adjust: exact;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .logo {
        color-adjust: exact;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .no-print {
        display: none !important;
    }

    .page-break {
        page-break-after: always;
    }

    .content {
        page-break-inside: avoid;
    }

    .signature-section {
        page-break-inside: avoid;
    }

    .header {
        padding-top: 0;
    }
}

        /* Screen Only Styles */
        @media screen {
            body {
                background: #E4E9F0;
                padding: 20px;
            }

            .container {
                max-width: 21cm;
                margin: 0 auto;
                background: white;
                padding: 1.5cm 2cm;
                box-shadow: 0 0 15px rgba(0,0,0,0.2);
            }
        }

        .signature-barcode svg {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    @if(!empty($needsBudget) && $needsBudget)
        <div class="budget-form-wrapper">
            <div class="alert alert-warning">
                <strong>SPD Mendadak</strong> — Silakan isi budget perjalanan sebelum mencetak surat.
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('surat.superadmin.save-mendadak-budget', $surat) }}">
                @csrf
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nominal (Rp)</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody id="mendadakBudgetRows">
                        @php
                            $start = \Carbon\Carbon::parse($surat->tanggal_berangkat);
                            $end = \Carbon\Carbon::parse($surat->tanggal_kembali);
                            $days = $start->diffInDays($end) + 1;
                        @endphp
                        @for($i = 0; $i < $days; $i++)
                            @php $d = (clone $start)->addDays($i); @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="budgets[{{ $i }}][tanggal]" value="{{ $d->format('Y-m-d') }}">
                                    {{ $d->translatedFormat('d F Y') }}
                                </td>
                                <td>
                                    <input type="number" class="form-control" name="budgets[{{ $i }}][nominal]"
                                           min="0" step="5000" value="{{ old('budgets.' . $i . '.nominal', 1400000) }}" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="budgets[{{ $i }}][catatan]"
                                           placeholder="Opsional" maxlength="255" value="{{ old('budgets.' . $i . '.catatan') }}">
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Budget & Cetak</button>
                    <a href="{{ route('surat.superadmin.show', $surat) }}" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    @else
    {{-- Print Buttons --}}
    <div class="no-print">
        <div class="toolbar">
            <div class="toolbar-title">
                <span class="doc-badge">DOKUMEN</span>
                <span class="doc-name">Surat Tugas — {{ $surat->nomor_surat }}</span>
            </div>
            <div class="toolbar-actions">
                <button onclick="window.print()" class="act-btn act-btn-print">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak / Simpan PDF
                </button>
                <button onclick="window.close()" class="act-btn act-btn-close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Main Document --}}
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-content">
                @php
                    $imagePath = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
                    $imageData = base64_encode(file_get_contents($imagePath));
                    $base64Image = 'data:image/png;base64,' . $imageData;
                @endphp

                <img src="{{ $base64Image }}"
                    alt="Geo Dipa Energi Logo"
                    class="logo">

                <div class="header-text">
                    <div class="company-name">Geo Dipa Energi</div>
                    <div class="company-unit">{{ $surat->user?->unit?->name ?: ('Unit '.($surat->user?->defaultSite?->name ?: 'Patuha')) }}</div>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div class="title">Surat Tugas</div>
        <div class="nomor">Nomor : {{ $surat->nomor_surat }}</div>

        {{-- Content --}}
        <div class="content">
            <p class="intro">Yang bertanda tangan dibawah ini,</p>

            <table class="info-table">
                <tr>
                    <td class="label">Nama</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->pemberi_tugas_nama }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->pemberi_tugas_jabatan }}</td>
                </tr>
            </table>

            <p class="intro">Dengan ini menugaskan kepada,</p>

            <table class="info-table">
                <tr>
                    <td class="label">Nama Pengemudi</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->pengemudi_nama }}</td>
                </tr>
                <tr>
                    <td class="label">Tempat Tujuan</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->tempat_tujuan }}</td>
                </tr>
                <tr>
                    <td class="label">Melayani Bidang</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->melayani_bidang }}</td>
                </tr>
                <tr>
                    <td class="label">Jumlah Pemakai</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->jumlah_pemakai }} orang</td>
                </tr>
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="colon">:</td>
                    <td class="value"></td>
                </tr>
            </table>

            {{-- Daftar Nama Lengkap --}}
            <table class="nama-lengkap-table">
                @foreach($surat->details->take(7) as $index => $detail)
                <tr>
                    <td class="number">{{ $index + 1 }}.</td>
                    <td class="name">{{ $detail->nama_lengkap }}</td>
                </tr>
                @endforeach

                {{-- Fill empty rows if less than 7 --}}
                @for($i = $surat->details->count(); $i < 7; $i++)
                <tr>
                    <td class="number">{{ $i + 1 }}.</td>
                    <td class="name">&nbsp;</td>
                </tr>
                @endfor
            </table>

            {{-- Informasi Perjalanan --}}
            <table class="info-table" style="margin-top: 10px;">
                <tr>
                    <td class="label">Lama Perjalanan Dinas</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->lama_perjalanan_hari }} hari</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Berangkat</td>
                    <td class="colon">:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_berangkat)->locale('id')->translatedFormat('l, d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Kembali</td>
                    <td class="colon">:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_kembali)->locale('id')->translatedFormat('l, d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Kendaraan No. Pol.</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $surat->vehicle->consumerial_code }} - {{ $surat->vehicle->consumerial_name }}</td>
                </tr>
            </table>

            <p class="closing-text">Demikian agar dilaksanakan dengan sebaik-baiknya.</p>
        </div>

        {{-- Signature Section --}}
        <div class="signature-section">
            <div class="signature-location">{{ $surat->tanggal_surat }}</div>
            <div class="signature-title">Yang Memberi Perintah,</div>

            {{-- QR Signature --}}
            @if(!empty($qrSvgContent))
                <div style="margin-bottom: 4px;">
                    <div class="signature-barcode text-center" style="width: 70px; height: 70px; margin: 0 auto;">
                        {!! $qrSvgContent !!}
                        <div class="text-muted" style="font-size: 8px; color: #666;">Scan untuk verifikasi</div>
                    </div>
                </div>
            @else
                <div style="margin-bottom: 4px; color: #888; font-size: 7pt;">QR Code belum tersedia</div>
            @endif

            <div class="signature-name">{{ $surat->pemberi_tugas_nama }}</div>
            <div style="margin-top: 2px; font-size: 10pt;">{{ $surat->pemberi_tugas_jabatan }}</div>
        </div>
    </div>

    {{-- Extension Pages (if any) --}}
    @if(isset($extensions) && $extensions->count() > 0)
        @foreach($extensions as $extension)
        <div class="page-break"></div>

        <div class="container">
            {{-- Extension Header --}}
            <div class="header">

                <div class="company-name">Geo Dipa Energi</div>
                <div class="company-unit">{{ $surat->user?->unit?->name ?: ('Unit '.($surat->user?->defaultSite?->name ?: 'Patuha')) }}</div>
            </div>

            <div class="title">Perpanjangan Surat Tugas</div>
            <div class="nomor">Nomor : {{ $extension->nomor_surat }}</div>

            <div class="content">
                <p class="intro">Merujuk pada Surat Tugas Nomor: <strong>{{ $surat->nomor_surat }}</strong></p>

                <table class="info-table">
                    <tr>
                        <td class="label">Tanggal Kembali Semula</td>
                        <td class="colon">:</td>
                        <td class="value">{{ \Carbon\Carbon::parse($extension->tanggal_kembali)->locale('id')->translatedFormat('l, d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Kembali Baru</td>
                        <td class="colon">:</td>
                        <td class="value">{{ \Carbon\Carbon::parse($extension->tanggal_kembali)->locale('id')->translatedFormat('l, d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tambahan Hari</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $extension->additional_days }} hari</td>
                    </tr>
                    <tr>
                        <td class="label">Alasan Perpanjangan</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $extension->alasan_perpanjangan }}</td>
                    </tr>
                </table>

                <p class="closing-text">Demikian perpanjangan ini agar diketahui dan dilaksanakan.</p>
            </div>

           {{-- Signature Section --}}
<div class="signature-section">
    <div class="signature-location">{{ $surat->tanggal_surat }}</div>
    <div class="signature-title">Yang Memberi Perintah,</div>

    {{-- QR Signature --}}
    @if(!empty($qrSvgContent))
    <div style="margin-bottom: 4px;">
       <div class="signature-barcode text-center" style="width: 70px; height: 70px; margin: 0 auto;">
        {!! $qrSvgContent !!}
        <div class="text-muted" style="font-size: 8px; color: #666;">
            Scan untuk verifikasi
        </div>
       </div>
    </div>
    @else
    <div style="margin-bottom: 4px; color: #888; font-size: 7pt;">QR Code belum tersedia</div>
    @endif

    <div class="signature-name">{{ $surat->pemberi_tugas_nama }}</div>
    <div style="margin-top: 2px; font-size: 10pt;">{{ $surat->pemberi_tugas_jabatan }}</div>
</div>
        </div>
        @endforeach
    @endif

    {{-- Close @else for needsBudget --}}
    @endif

    {{-- Auto-print Script --}}
    <script>
        // Auto-print saat halaman dibuka (skip jika needsBudget)
        @if(!empty($needsBudget) && $needsBudget)
            // Don't auto-print, user needs to fill budget form first
        @elseif(session('print_now'))
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        @else
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        @endif
    </script>
</body>
</html>
