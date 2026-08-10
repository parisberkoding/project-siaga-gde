<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPD &amp; LPJ - {{ $surat->nomor_surat }}</title>
    <style>
        /* ═══════════════════════════════════════════════════════════
           DOKUMEN GABUNGAN SPD + LPJ (A4, margin 3 cm semua sisi)
           ═══════════════════════════════════════════════════════════ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 3cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
        }

        /* Lebar konten: readable di layar (800-1340px), pas di halaman saat cetak */
        .sheet {
            width: 100%;
            max-width: 1340px;
            margin: 0 auto;
        }

        @media screen {
            .sheet { min-width: 800px; }
        }

        @media print {
            .sheet { min-width: 0; }
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .page-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 10px 0 4px;
        }

        .page-subtitle {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 14px;
        }

        /* ── Kop Surat ── */
        .kop {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 6px 0 10px;
            border-bottom: 4px solid #E4222C;
        }

        .kop-logo { width: 82px; flex-shrink: 0; }
        .kop-logo img { width: 100%; }

        .kop-text .company {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kop-text .address {
            font-size: 9.5pt;
            color: #333;
        }

        /* ── Tabel Info Surat / LPJ ── */
        table.info {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        table.info td {
            padding: 3px 4px;
            vertical-align: top;
        }

        table.info .label { width: 180px; }

        table.info .value {
            border-bottom: 1px dotted #000;
            min-height: 16px;
        }

        table.info td.dashed {
            border-bottom: 1px dotted #000;
        }

        /* ── Daftar Nama Penumpang ── */
        table.nama {
            width: calc(100% - 190px);
            margin-left: 190px;
            border-collapse: collapse;
        }

        table.nama td {
            padding: 2px 4px;
            vertical-align: top;
        }

        table.nama .no { width: 30px; }
        table.nama .nm { border-bottom: 1px dotted #000; }

        /* ── Ringkasan LPJ (Page 2) ── */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            margin: 10px 0 16px;
        }

        .summary-grid .item-label {
            font-weight: bold;
        }

        .box {
            border: 1.2px solid #000;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 12px 0;
            page-break-inside: avoid;
        }

        .box h3 {
            font-size: 11pt;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 8px;
        }

        /* ── Tabel Rincian (Page 3) ── */
        table.rincian {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10pt;
        }

        table.rincian th,
        table.rincian td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: center;
        }

        table.rincian th { background: #f0f0f0; font-weight: bold; }
        table.rincian td.num { text-align: right; }
        table.rincian tr.total td { font-weight: bold; background: #f7f7f7; }

        /* ── Tanda Tangan ── */
        .ttd-wrap {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 34px;
            page-break-inside: avoid;
        }

        .ttd {
            flex: 1;
            text-align: center;
            min-height: 150px;
        }

        .ttd .title { font-weight: bold; margin-bottom: 64px; }
        .ttd .name {
            border-top: 1px solid #000;
            padding-top: 6px;
            font-weight: bold;
        }
        .ttd .role { font-size: 9.5pt; margin-top: 2px; }

        /* ── Lampiran Foto (masonry grid) ── */
        .photo-page-header {
            text-align: center;
            margin: 4px 0 14px;
        }

        .photo-page-header h2 {
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .photo-page-header .desc {
            font-size: 10pt;
            margin-top: 2px;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            align-items: start;
            width: 100%;
        }

        .photo-grid .grid-divider {
            grid-column: 1 / -1;
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #a00;
            border-bottom: 1px solid #a00;
            padding: 2px 0;
            margin-top: 2px;
        }

        .photo-grid .items {
            display: flex;
            flex-direction: column;
            border: 1px solid #999;
            border-radius: 6px;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
        }

        .items .credential {
            padding: 8px 10px;
            font-size: 9pt;
            line-height: 1.4;
            background: #fafafa;
            border-bottom: 1px solid #ddd;
        }

        .items .credential .cat {
            font-weight: bold;
            text-transform: uppercase;
            color: #a00;
        }

        .items .credential .code {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            color: #444;
        }

        .items .photo-wrap {
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            background: #fff;
        }

        .items .photo-wrap img {
            max-width: 100%;
            max-height: 240px;
            object-fit: contain;
        }

        .items .no-photo {
            color: #888;
            font-size: 9pt;
            padding: 20px;
        }

        /* ── Tombol print (hidden saat cetak) ── */
        .no-print {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .btn-print, .btn-close {
            border: none;
            padding: 10px 25px;
            font-size: 13pt;
            cursor: pointer;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .btn-print { background: #28a745; color: #fff; }
        .btn-print:hover { background: #218838; }
        .btn-close { background: #dc3545; color: #fff; margin-left: 10px; }
        .btn-close:hover { background: #c82333; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .signature-barcode svg { width: 100%; height: 100%; }
        }

        @media screen {
            body { background: #e9ecef; padding: 20px; }
            .sheet {
                background: #fff;
                box-shadow: 0 0 15px rgba(0,0,0,0.2);
                padding: 2.2cm 3cm;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">PRINT / SAVE AS PDF</button>
    <button onclick="window.close()" class="btn-close">TUTUP</button>
</div>

<div class="sheet">

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 1 : RINGKASAN SURAT TUGAS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="kop">
        <div class="kop-logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
        <div class="kop-text">
            <div class="company">PT. GEO DIPA ENERGI UNIT PATUHA</div>
            <div class="address">Jl. Raya Rancabolang KM. 14, Kp. Kendeng - Ds. Sugihmukti, Kec. Pasir Jambu - Kab. Bandung 40973</div>
        </div>
    </div>

    <div class="page-title">Ringkasan Surat Tugas</div>
    <div class="page-subtitle">Nomor: <strong>{{ $surat->nomor_surat }}</strong></div>

    <table class="info">
        <tr>
            <td class="label">Nama Pengemudi</td>
            <td>:</td>
            <td class="value">{{ $surat->pengemudi_nama }}</td>
        </tr>
        <tr>
            <td class="label">Kendaraan yang dibawa</td>
            <td>:</td>
            <td class="value">{{ $laporan->kendaraan_label }}</td>
        </tr>
        <tr>
            <td class="label">Tempat Tujuan</td>
            <td>:</td>
            <td class="value">{{ $surat->tempat_tujuan }}</td>
        </tr>
        <tr>
            <td class="label">Bidang yang dilayani</td>
            <td>:</td>
            <td class="value">{{ $surat->melayani_bidang }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Pemakai</td>
            <td>:</td>
            <td class="value">{{ $surat->jumlah_pemakai }} orang</td>
        </tr>
        <tr>
            <td class="label">Tanggal Berangkat</td>
            <td>:</td>
            <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_berangkat)->locale('id')->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Kembali</td>
            <td>:</td>
            <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_kembali)->locale('id')->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Lengkap Pemakai</td>
            <td>:</td>
            <td class="value"></td>
        </tr>
    </table>

    <table class="nama">
        @php $passengerCount = $surat->details->count(); @endphp
        @foreach($surat->details as $index => $detail)
        <tr>
            <td class="no">{{ $index + 1 }}.</td>
            <td class="nm">{{ $detail->nama_lengkap }}</td>
        </tr>
        @endforeach
        @for($i = $passengerCount; $i < max($surat->jumlah_pemakai, $passengerCount); $i++)
        <tr>
            <td class="no">{{ $i + 1 }}.</td>
            <td class="nm">&nbsp;</td>
        </tr>
        @endfor
    </table>

    <div style="margin-top: 26px; text-align: right; page-break-inside: avoid;">
        <div>{{ $surat->tanggal_surat }}</div>
        <div style="font-weight: bold; margin-top: 8px;">Yang Memberi Perintah,</div>
        <div style="margin: 22mm 0 2px; font-weight: bold; text-decoration: underline;">{{ $surat->pemberi_tugas_nama }}</div>
        <div>{{ $surat->pemberi_tugas_jabatan }}</div>

        @if(!empty($qrSvgContent))
        <div class="signature-barcode" style="width: 72px; height: 72px; margin: 10px 0 0 auto;">
            {!! $qrSvgContent !!}
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 2 : RINGKASAN LAPORAN
         ═══════════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="page-title">Ringkasan Laporan</div>
    <div class="page-subtitle">Laporan Pertanggungjawaban - {{ $laporan->lpj_number }}</div>

    <table class="info">
        <tr>
            <td class="label">Nomor Surat Tugas</td>
            <td>:</td>
            <td class="value">{{ $surat->nomor_surat }}</td>
        </tr>
        <tr>
            <td class="label">Pengemudi</td>
            <td>:</td>
            <td class="value">{{ $laporan->nama ?: $surat->pengemudi_nama }}</td>
        </tr>
        <tr>
            <td class="label">Kendaraan</td>
            <td>:</td>
            <td class="value">{{ $laporan->kendaraan_label }}</td>
        </tr>
        <tr>
            <td class="label">Periode SPD</td>
            <td>:</td>
            <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_berangkat)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($surat->tanggal_kembali)->translatedFormat('d M Y') }} ({{ $surat->lama_perjalanan_hari }} hari)</td>
        </tr>
    </table>

    <div class="box">
        <h3>Rekapitulasi Pengeluaran</h3>
        <div class="summary-grid">
            <div>
                <span class="item-label">Kebutuhan BBM</span><br>
                <span>{{ $laporan->kebutuhan_bbm_liter !== null ? number_format((float) $laporan->kebutuhan_bbm_liter, 0, ',', '.') . ' liter' : '-' }}</span>
            </div>
            <div>
                <span class="item-label">Spending BBM</span><br>
                <span>Rp {{ number_format($spending['BBM'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Spending Parkir</span><br>
                <span>Rp {{ number_format($spending['Parkir'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Spending Tol</span><br>
                <span>Rp {{ number_format($spending['Tol'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Spending Lainnya</span><br>
                <span>Rp {{ number_format($spending['Lainnya'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Total Pengeluaran</span><br>
                <strong>Rp {{ number_format($laporan->total_pengeluaran ?: array_sum($spending), 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <div class="box">
        <h3>Rincian Tempuh Kendaraan</h3>
        <div class="summary-grid">
            <div>
                <span class="item-label">Odometer sebelum SPD</span><br>
                <span>{{ $laporan->odo_before !== null ? number_format((float) $laporan->odo_before, 0, ',', '.') . ' km' : '-' }}</span>
            </div>
            <div>
                <span class="item-label">Odometer setelah SPD</span><br>
                <span>{{ $laporan->odo_after !== null ? number_format((float) $laporan->odo_after, 0, ',', '.') . ' km' : '-' }}</span>
            </div>
            <div>
                <span class="item-label">Total jarak tempuh</span><br>
                <strong>{{ $laporan->getTotalJarakTempuh() !== null ? number_format($laporan->getTotalJarakTempuh(), 0, ',', '.') . ' km' : '-' }}</strong>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 3 : RINCIAN PENGGUNAAN
         ═══════════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="page-title">Rincian Penggunaan</div>
    <div class="page-subtitle">{{ $surat->nomor_surat }}</div>

    <table class="rincian">
        <thead>
            <tr>
                <th style="width: 22%;">Tanggal</th>
                <th>Tol</th>
                <th>BBM</th>
                <th>Parkir</th>
                <th>Lainnya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rincian as $row)
            <tr>
                <td>{{ $row['tanggal'] ? $row['tanggal']->translatedFormat('d M Y') : '-' }}</td>
                <td class="num">{{ $row['Tol'] > 0 ? 'Rp ' . number_format($row['Tol'], 0, ',', '.') : '' }}</td>
                <td class="num">{{ $row['BBM'] > 0 ? 'Rp ' . number_format($row['BBM'], 0, ',', '.') : '' }}</td>
                <td class="num">{{ $row['Parkir'] > 0 ? 'Rp ' . number_format($row['Parkir'], 0, ',', '.') : '' }}</td>
                <td class="num">{{ $row['Lainnya'] > 0 ? 'Rp ' . number_format($row['Lainnya'], 0, ',', '.') : '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Tidak ada rincian pengeluaran.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total">
                <td>Total</td>
                <td class="num">Rp {{ number_format($spending['Tol'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($spending['BBM'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($spending['Parkir'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($spending['Lainnya'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd-wrap">
        <div class="ttd">
            <div class="title">User</div>
            <div class="name">{{ $laporan->nama ?: '_________________' }}</div>
            <div class="role">Pengemudi / User</div>
        </div>
        <div class="ttd">
            <div class="title">General Affairs</div>
            <div class="name">{{ $laporan->approverDivisiGa?->nama_lengkap ?: '_________________' }}</div>
            <div class="role">Divisi GA</div>
        </div>
        <div class="ttd">
            <div class="title">Manager / Asst. Manager</div>
            <div class="name">_________________</div>
            <div class="role">Management</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 4+ : LAMPIRAN FOTO-FOTO
         ═══════════════════════════════════════════════════════════ --}}
    @if($photoGrid->isNotEmpty())
        @foreach($photoGrid as $pageIndex => $page)
        <div class="page-break"></div>

        <div class="photo-page-header">
            <h2>Lampiran Foto-Foto</h2>
            <div class="desc">
                {{ $surat->nomor_surat }} — Halaman {{ $pageIndex + 1 }} dari {{ $photoGrid->count() }}
            </div>
        </div>

        <div class="photo-grid">
            @php $prevKategori = null; @endphp
            @foreach($page as $entry)
                @if($prevKategori !== null && $entry['item']->struk_kategori !== $prevKategori)
                    <div class="grid-divider">{{ $entry['item']->struk_kategori }}</div>
                @endif
                <div class="items">
                    <div class="credential">
                        <span class="cat">{{ $entry['item']->struk_kategori }}</span>
                        &nbsp;|&nbsp;
                        <span>
                            {{ $entry['item']->tanggal ? $entry['item']->tanggal->translatedFormat('d M Y') : '-' }}
                        </span>
                        &nbsp;|&nbsp;
                        <span>Rp {{ number_format((float) $entry['item']->nominal, 0, ',', '.') }}</span>
                        <div class="code">{{ $entry['item']->item_code }}</div>
                        @if($entry['item']->keterangan)
                            <div>{{ $entry['item']->keterangan }}</div>
                        @endif
                    </div>
                    <div class="photo-wrap">
                        @if($entry['image_base64'])
                            <img src="data:{{ $entry['mime'] }};base64,{{ $entry['image_base64'] }}" alt="Struk">
                        @else
                            <div class="no-photo">Foto tidak tersedia</div>
                        @endif
                    </div>
                </div>
                @php $prevKategori = $entry['item']->struk_kategori; @endphp
            @endforeach
        </div>
        @endforeach
    @endif

</div>

<script>
    window.onload = function () {
        setTimeout(function () { window.print(); }, 500);
    };
</script>

</body>
</html>
