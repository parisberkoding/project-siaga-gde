#!/usr/bin/env python3
"""
Script to generate Buku Panduan Dispatcher Role - DOCX format
"""
from docx import Document
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

def set_cell_shading(cell, color):
    shading_elm = OxmlElement('w:shd')
    shading_elm.set(qn('w:fill'), color)
    shading_elm.set(qn('w:val'), 'clear')
    cell._tc.get_or_add_tcPr().append(shading_elm)

def add_page_break(doc):
    doc.add_page_break()

def add_heading_styled(doc, text, level=1):
    heading = doc.add_heading(text, level=level)
    for run in heading.runs:
        run.font.color.rgb = RGBColor(1, 41, 112)
    return heading

def add_lampiran_box(doc, text):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.text = ""
    set_cell_shading(cell, "F0F4FF")
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    tcBorders = OxmlElement('w:tcBorders')
    for border_name in ['top', 'left', 'bottom', 'right']:
        border = OxmlElement(f'w:{border_name}')
        border.set(qn('w:val'), 'single')
        border.set(qn('w:sz'), '4')
        border.set(qn('w:space'), '0')
        border.set(qn('w:color'), '4154F1')
        tcBorders.append(border)
    tcPr.append(tcBorders)
    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    run = p.add_run("LAMPIRAN: ")
    run.bold = True
    run.font.size = Pt(9)
    run.font.color.rgb = RGBColor(65, 84, 241)
    run = p.add_run(text)
    run.font.size = Pt(9)
    run.font.color.rgb = RGBColor(100, 100, 100)
    doc.add_paragraph("")

def add_screenshot_placeholder(doc, caption=""):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.text = ""
    set_cell_shading(cell, "F8F9FA")
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    tcBorders = OxmlElement('w:tcBorders')
    for border_name in ['top', 'left', 'bottom', 'right']:
        border = OxmlElement(f'w:{border_name}')
        border.set(qn('w:val'), 'dashed')
        border.set(qn('w:sz'), '4')
        border.set(qn('w:space'), '0')
        border.set(qn('w:color'), 'ADB5BD')
        tcBorders.append(border)
    tcPr.append(tcBorders)
    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("[Lampiran Screenshot")
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(173, 181, 189)
    run.italic = True
    if caption:
        run = p.add_run(f" - {caption}")
        run.font.size = Pt(10)
        run.font.color.rgb = RGBColor(173, 181, 189)
        run.italic = True
    run = p.add_run("]")
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(173, 181, 189)
    run.italic = True
    p2 = cell.add_paragraph()
    p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p2.add_run("Masukkan screenshot di sini")
    run.font.size = Pt(8)
    run.font.color.rgb = RGBColor(173, 181, 189)
    run.italic = True
    doc.add_paragraph("")

def add_step(doc, number, text, has_screenshot=False, screenshot_caption=""):
    p = doc.add_paragraph()
    run = p.add_run(f"{number}. ")
    run.bold = True
    run.font.size = Pt(11)
    run.font.color.rgb = RGBColor(1, 41, 112)
    run = p.add_run(text)
    run.font.size = Pt(11)
    if has_screenshot:
        add_screenshot_placeholder(doc, screenshot_caption)

def add_info_box(doc, text):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.text = ""
    set_cell_shading(cell, "E8F4FD")
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    tcBorders = OxmlElement('w:tcBorders')
    for border_name in ['top', 'left', 'bottom', 'right']:
        border = OxmlElement(f'w:{border_name}')
        border.set(qn('w:val'), 'single')
        border.set(qn('w:sz'), '4')
        border.set(qn('w:space'), '0')
        border.set(qn('w:color'), '0DCAF0')
        tcBorders.append(border)
    tcPr.append(tcBorders)
    p = cell.paragraphs[0]
    run = p.add_run("INFO: ")
    run.bold = True
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(13, 202, 240)
    run = p.add_run(text)
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(100, 100, 100)
    doc.add_paragraph("")

def add_warning_box(doc, text):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.text = ""
    set_cell_shading(cell, "FFF3CD")
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    tcBorders = OxmlElement('w:tcBorders')
    for border_name in ['top', 'left', 'bottom', 'right']:
        border = OxmlElement(f'w:{border_name}')
        border.set(qn('w:val'), 'single')
        border.set(qn('w:sz'), '4')
        border.set(qn('w:space'), '0')
        border.set(qn('w:color'), 'FFC107')
        tcBorders.append(border)
    tcPr.append(tcBorders)
    p = cell.paragraphs[0]
    run = p.add_run("PERINGATAN: ")
    run.bold = True
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(153, 116, 4)
    run = p.add_run(text)
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(100, 100, 100)
    doc.add_paragraph("")

def create_document():
    doc = Document()
    style = doc.styles['Normal']
    font = style.font
    font.name = 'Calibri'
    font.size = Pt(11)

    # ============================================
    # HALAMAN 1: COVER
    # ============================================
    for _ in range(6):
        doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("BUKU PANDUAN")
    run.bold = True
    run.font.size = Pt(28)
    run.font.color.rgb = RGBColor(1, 41, 112)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("PENGGUNAAN SISTEM")
    run.bold = True
    run.font.size = Pt(24)
    run.font.color.rgb = RGBColor(1, 41, 112)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("ADMINISTRASI GENERAL AFFAIRS")
    run.bold = True
    run.font.size = Pt(24)
    run.font.color.rgb = RGBColor(1, 41, 112)

    doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Role: Dispatcher")
    run.font.size = Pt(18)
    run.font.color.rgb = RGBColor(65, 84, 241)
    run.italic = True

    doc.add_paragraph("")
    doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("PT Geo Dipa Energi (Persero)")
    run.bold = True
    run.font.size = Pt(16)
    run.font.color.rgb = RGBColor(100, 100, 100)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Direktorat General Affairs")
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(100, 100, 100)

    doc.add_paragraph("")
    doc.add_paragraph("")
    doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Versi 1.0.0")
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(100, 100, 100)

    doc.add_paragraph("")
    doc.add_paragraph("")
    doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Penyusun:")
    run.font.size = Pt(12)
    run.font.color.rgb = RGBColor(100, 100, 100)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Tim IT Developer")
    run.bold = True
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(1, 41, 112)

    # ============================================
    # HALAMAN 2: LEMBAR PENGESAHAN
    # ============================================
    add_page_break(doc)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("LEMBAR PENGESAHAN")
    run.bold = True
    run.font.size = Pt(18)
    run.font.color.rgb = RGBColor(1, 41, 112)

    doc.add_paragraph("")
    doc.add_paragraph("")

    table = doc.add_table(rows=6, cols=3)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Jabatan", "Nama", "Tanda Tangan & Tanggal"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
                run.font.size = Pt(11)
        set_cell_shading(cell, "E8EAF6")

    approvals = [
        ("Disusun Oleh", "Tim IT Developer", ""),
        ("Diperiksa Oleh", "Kepala Divisi General Affairs", ""),
        ("Disetujui Oleh", "Direktur General Affairs", ""),
        ("Diakui Oleh", "Sekretaris Perusahaan", ""),
        ("Ditetapkan Oleh", "Direktur Utama", ""),
    ]

    for row_idx, (jabatan, nama, ttd) in enumerate(approvals, 1):
        table.cell(row_idx, 0).text = jabatan
        table.cell(row_idx, 1).text = nama
        table.cell(row_idx, 2).text = ttd
        for col in range(3):
            for paragraph in table.cell(row_idx, col).paragraphs:
                paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
                for run in paragraph.runs:
                    run.font.size = Pt(10)

    for row in table.rows:
        row.cells[0].width = Cm(5)
        row.cells[1].width = Cm(5)
        row.cells[2].width = Cm(6)

    doc.add_paragraph("")
    doc.add_paragraph("")

    p = doc.add_paragraph()
    run = p.add_run("Catatan:")
    run.bold = True
    run.font.size = Pt(11)

    doc.add_paragraph("Dokumen ini merupakan panduan resmi penggunaan Sistem Administrasi General Affairs untuk role Dispatcher di PT Geo Dipa Energi (Persero).", style='List Bullet')
    doc.add_paragraph("Setiap dispatcher diwajibkan memahami dan mengikuti prosedur yang tercantum dalam dokumen ini.", style='List Bullet')
    doc.add_paragraph("Segala perubahan terhadap sistem akan diinformasikan melalui update dokumen ini.", style='List Bullet')

    # ============================================
    # HALAMAN 3: DAFTAR ISI
    # ============================================
    add_page_break(doc)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("DAFTAR ISI")
    run.bold = True
    run.font.size = Pt(18)
    run.font.color.rgb = RGBColor(1, 41, 112)

    doc.add_paragraph("")

    toc_items = [
        ("1.", "Pendahuluan", ""),
        ("", "1.1 Latar Belakang", ""),
        ("", "1.2 Tujuan", ""),
        ("", "1.3 Ruang Lingkup", ""),
        ("2.", "Hak Akses dan Batasan Dispatcher", ""),
        ("", "2.1 Definisi Role Dispatcher", ""),
        ("", "2.2 Hak Akses (Apa yang Bisa Dilakukan)", ""),
        ("", "2.3 Batasan Dispatcher (Apa yang Tidak Bisa Dilakukan)", ""),
        ("", "2.4 Struktur Menu Sidebar Dispatcher", ""),
        ("3.", "Prosedur Login & Logout", ""),
        ("", "3.1 Akses Sistem", ""),
        ("", "3.2 Proses Login", ""),
        ("", "3.3 Proses Logout", ""),
        ("4.", "Dashboard Dispatcher", ""),
        ("", "4.1 Mengakses Dashboard", ""),
        ("", "4.2 Kartu Statistik", ""),
        ("", "4.3 Tabel Jadwal Kendaraan", ""),
        ("5.", "Generate Periode Pengecekan", ""),
        ("", "5.1 Menggenerate Periode Bulanan", ""),
        ("6.", "Pemeriksaan Rutin Harian (Daily Check)", ""),
        ("", "6.1 Membuat Pemeriksaan Harian", ""),
        ("", "6.2 Melihat Detail Hasil Pemeriksaan", ""),
        ("7.", "Pemeriksaan Rutin Mingguan (Weekly Check)", ""),
        ("", "7.1 Membuat Pemeriksaan Mingguan", ""),
        ("8.", "Pemeriksaan Rutin Bulanan (Monthly Check)", ""),
        ("", "8.1 Membuat Pemeriksaan Bulanan", ""),
        ("9.", "Pemeriksaan Body Kendaraan", ""),
        ("", "9.1 Membuat Pemeriksaan Body", ""),
        ("", "9.2 Upload Foto Kerusakan", ""),
        ("", "9.3 Melihat Detail Hasil Body Check", ""),
        ("10.", "Scan QR / Barcode Kendaraan", ""),
        ("", "10.1 Scan untuk Melihat Riwayat", ""),
        ("", "10.2 Scan untuk Memulai Pemeriksaan Baru", ""),
        ("", "10.3 Input Manual Nomor Kendaraan", ""),
        ("11.", "Permohonan Penggantian Suku Cadang (VPR)", ""),
        ("", "11.1 Auto-Create VPR dari Hasil Pengecekan", ""),
        ("", "11.2 Membuat VPR Manual", ""),
        ("", "11.3 Melihat Status VPR", ""),
        ("12.", "Riwayat & Laporan Pengecekan", ""),
        ("", "12.1 Melihat Riwayat Semua Pengecekan", ""),
        ("", "12.2 Filter Pencarian Riwayat", ""),
        ("13.", "Export Form Pengecekan", ""),
        ("", "13.1 Export ke PDF", ""),
        ("", "13.2 Export ke Excel", ""),
        ("14.", "Penutup", ""),
    ]

    for num, title, page in toc_items:
        p = doc.add_paragraph()
        if num and num[-1] == '.':
            run = p.add_run(f"{num} {title}")
            run.bold = True
            run.font.size = Pt(12)
        else:
            run = p.add_run(f"    {num} {title}")
            run.font.size = Pt(11)

    # ============================================
    # HALAMAN 4: PENDAHULUAN
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "1. Pendahuluan", level=1)

    add_heading_styled(doc, "1.1 Latar Belakang", level=2)
    doc.add_paragraph(
        "Sistem Administrasi General Affairs (Siaga) merupakan sistem informasi yang dikembangkan "
        "untuk mendukung pengelolaan administrasi terkait Bahan Bakar Minyak (BBM), "
        "Surat Tugas Perjalanan Dinas (SPD), dan pengelolaan kendaraan di PT Geo Dipa Energi (Persero). "
        "Sistem ini dirancang untuk memudahkan proses bisnis yang sebelumnya dilakukan secara manual menjadi "
        "lebih efisien dan terintegrasi."
    )

    add_heading_styled(doc, "1.2 Tujuan", level=2)
    doc.add_paragraph("Buku panduan ini disusun dengan tujuan:")
    doc.add_paragraph("Memberikan panduan lengkap bagi pengguna role Dispatcher dalam menggunakan sistem Siaga.", style='List Bullet')
    doc.add_paragraph("Memastikan proses pengecekan kendaraan berjalan sesuai prosedur yang ditetapkan.", style='List Bullet')
    doc.add_paragraph("Mengurangi kesalahan dalam pelaksanaan pemeriksaan kendaraan.", style='List Bullet')
    doc.add_paragraph("Menjadi referensi bagi dispatcher baru dalam memahami alur kerja pengecekan kendaraan.", style='List Bullet')

    add_heading_styled(doc, "1.3 Ruang Lingkup", level=2)
    doc.add_paragraph("Buku panduan ini mencakup seluruh prosedur dan fitur yang tersedia untuk role Dispatcher, meliputi:")
    doc.add_paragraph("Hak akses dan batasan role Dispatcher", style='List Bullet')
    doc.add_paragraph("Dashboard Dispatcher dan statistik", style='List Bullet')
    doc.add_paragraph("Generate periode pengecekan bulanan", style='List Bullet')
    doc.add_paragraph("Pemeriksaan rutin (Harian, Mingguan, Bulanan)", style='List Bullet')
    doc.add_paragraph("Pemeriksaan Body kendaraan dengan foto kerusakan", style='List Bullet')
    doc.add_paragraph("Scan QR/Barcode kendaraan", style='List Bullet')
    doc.add_paragraph("Permohonan penggantian suku cadang (VPR)", style='List Bullet')
    doc.add_paragraph("Riwayat pengecekan dan export laporan", style='List Bullet')
    doc.add_paragraph("Export form pengecekan ke PDF dan Excel", style='List Bullet')

    # ============================================
    # HALAMAN 5: HAK AKSES DAN BATASAN DISPATCHER
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "2. Hak Akses dan Batasan Dispatcher", level=1)

    add_heading_styled(doc, "2.1 Definisi Role Dispatcher", level=2)
    doc.add_paragraph(
        "Dispatcher adalah petugas lapangan yang bertanggung jawab melakukan pengecekan kendaraan "
        "(harian, mingguan, bulanan, dan body inspection), mengelola jadwal pengecekan, "
        "serta mengajukan permohonan penggantian suku cadang (VPR) apabila ditemukan komponen yang "
        "memerlukan penggantian. Dispatcher juga bertugas menetapkan PIC (Person In Charge) "
        "pengecekan untuk setiap kendaraan."
    )

    add_info_box(doc, "Dalam sistem SIAGA, role Dispatcher memiliki akses terbatas pada modul pengecekan kendaraan dan tidak memiliki akses ke modul BBM, SPD, LPJ, Lunch Management, atau manajemen pengguna.")

    add_heading_styled(doc, "2.2 Hak Akses (Apa yang Bisa Dilakukan)", level=2)

    doc.add_paragraph("Berikut adalah daftar lengkap fitur yang dapat diakses oleh role Dispatcher:")

    table = doc.add_table(rows=12, cols=3)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Modul", "Fitur", "Route"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
                run.font.size = Pt(9)
        set_cell_shading(cell, "E8EAF6")

    rights = [
        ("Dashboard", "Dashboard overview pengecekan", "/dispatcher/dashboard"),
        ("Period", "Generate periode pengecekan bulanan", "/dispatcher/generate-period"),
        ("PIC", "Assign PIC ke kendaraan", "/dispatcher/assign"),
        ("Daily Check", "Pemeriksaan harian (CRUD)", "/dispatcher/daily-check/*"),
        ("Weekly Check", "Pemeriksaan mingguan (CRUD)", "/dispatcher/weekly-check/*"),
        ("Monthly Check", "Pemeriksaan bulanan (CRUD)", "/dispatcher/monthly-check/*"),
        ("Body Check", "Pemeriksaan body + foto kerusakan", "/dispatcher/body-check/*"),
        ("QR Scan", "Scan QR riwayat & mulai pemeriksaan", "/dispatcher/scan-vehicle, /dispatcher-scanner"),
        ("VPR", "Ajukan permohonan penggantian suku cadang", "/dispatcher/replacement/*"),
        ("Riwayat", "Riwayat semua pengecekan + filter", "/dispatcher/history"),
        ("Export", "Export PDF & Excel form pengecekan", "/dispatcher/export/*"),
    ]

    for row_idx, (modul, fitur, route) in enumerate(rights, 1):
        table.cell(row_idx, 0).text = modul
        table.cell(row_idx, 1).text = fitur
        table.cell(row_idx, 2).text = route
        for col in range(3):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(9)

    add_heading_styled(doc, "2.3 Batasan Dispatcher (Apa yang Tidak Bisa Dilakukan)", level=2)

    doc.add_paragraph("Dispatcher tidak dapat mengakses fitur-fitur berikut:")

    table = doc.add_table(rows=9, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Fitur", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
                run.font.size = Pt(9)
        set_cell_shading(cell, "E8EAF6")

    limits = [
        ("Approve/Reject VPR", "Dispatcher hanya bisa mengajukan; approval wewenang Admin/GA"),
        ("Penjadwalan Servis", "Tidak bisa menjadwalkan servis — wewenang Admin"),
        ("Transaksi Servis", "Tidak bisa input biaya/foto servis — wewenang Driver"),
        ("Manajemen BBM", "Tidak bisa membuat/menyetujui permohonan BBM"),
        ("SPD & LPJ", "Tidak bisa membuat/mengelola surat perjalanan dinas"),
        ("Lunch Management", "Tidak bisa mengelola data katering"),
        ("Manajemen User", "Tidak bisa CRUD pengguna, hak akses, atau RBAC"),
        ("Master Data", "Tidak bisa mengelola unit, site, atau harga BBM"),
    ]

    for row_idx, (fitur, keterangan) in enumerate(limits, 1):
        table.cell(row_idx, 0).text = fitur
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(9)

    add_heading_styled(doc, "2.4 Struktur Menu Sidebar Dispatcher", level=2)

    doc.add_paragraph("Setelah login sebagai Dispatcher, sidebar menu menampilkan item-item berikut:")

    p = doc.add_paragraph()
    run = p.add_run("Dashboard: ")
    run.bold = True
    run = p.add_run("Dashboard Dispatcher (overview jadwal pengecekan).")

    p = doc.add_paragraph()
    run = p.add_run("Scanner Kendaraan (collapsible): ")
    run.bold = True
    run = p.add_run("Scan Riwayat Kendaraan, Scan untuk Periksa.")

    p = doc.add_paragraph()
    run = p.add_run("Pemeriksaan Kendaraan (collapsible): ")
    run.bold = True
    run = p.add_run("Periksa Harian, Periksa Mingguan, Periksa Bulanan, Periksa Body.")

    p = doc.add_paragraph()
    run = p.add_run("Permohonan Penggantian: ")
    run.bold = True
    run = p.add_run("Daftar VPR dengan badge jumlah pending.")

    p = doc.add_paragraph()
    run = p.add_run("Riwayat & Laporan: ")
    run.bold = True
    run = p.add_run("Riwayat semua pengecekan.")

    p = doc.add_paragraph()
    run = p.add_run("Export Form Pengecekan: ")
    run.bold = True
    run = p.add_run("Export ke Excel (shared dengan role requester/driver).")

    add_info_box(doc, "Menu sidebar bersifat dinamis — item yang tidak memiliki akses tidak akan tampil.")

    # ============================================
    # HALAMAN 7: PROSEDUR LOGIN & LOGOUT
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "3. Prosedur Login & Logout", level=1)

    add_heading_styled(doc, "3.1 Akses Sistem", level=2)

    add_step(doc, 1, "Buka web browser (Google Chrome, Mozilla Firefox, atau browser modern lainnya).")

    add_step(doc, 2, "Akses link sistem yang sudah dibagikan oleh administrator.", has_screenshot=True, screenshot_caption="Halaman Login Sistem Siaga")

    add_lampiran_box(doc, "Route: GET /login | View: resources/views/authentication/login.blade.php")

    add_heading_styled(doc, "3.2 Proses Login", level=2)

    add_step(doc, 1, "Pada halaman login, masukkan Username yang sudah diberikan oleh administrator pada kolom \"Masukkan Username\".", has_screenshot=True, screenshot_caption="Form Input Username")

    add_step(doc, 2, "Masukkan Password yang sudah diberikan pada kolom \"Masukkan Password\".", has_screenshot=True, screenshot_caption="Form Input Password")

    add_step(doc, 3, "Centang checkbox \"Tampilkan Password\" jika ingin melihat password yang dimasukkan (opsional).")

    add_step(doc, 4, "Klik tombol \"Login\" untuk masuk ke sistem.", has_screenshot=True, screenshot_caption="Tombol Login")

    add_info_box(doc, "Jika login berhasil, Anda akan diarahkan ke halaman Dashboard Dispatcher. Jika gagal, akan muncul pesan error \"Username atau password salah\".")

    add_lampiran_box(doc, "Route: POST /login-process | Controller: AuthentiactionController@authenticate | View: resources/views/authentication/login.blade.php")

    add_heading_styled(doc, "3.3 Proses Logout", level=2)

    add_step(doc, 1, "Klik icon profil atau nama Anda di pojok kanan atas halaman.", has_screenshot=True, screenshot_caption="Icon Profil Pengguna")

    add_step(doc, 2, "Pilih opsi \"Logout\" dari dropdown menu.", has_screenshot=True, screenshot_caption="Menu Logout")

    add_step(doc, 3, "Anda akan diarahkan kembali ke halaman login.", has_screenshot=True, screenshot_caption="Halaman Login Setelah Logout")

    add_info_box(doc, "Selalu lakukan logout setelah selesai menggunakan sistem untuk menjaga keamanan akun Anda.")

    add_lampiran_box(doc, "Route: GET/POST /logout | Controller: AuthentiactionController@logout")

    # ============================================
    # HALAMAN 8: DASHBOARD DISPATCHER
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "4. Dashboard Dispatcher", level=1)

    doc.add_paragraph(
        "Dashboard Dispatcher adalah halaman utama yang menampilkan ringkasan status jadwal pengecekan "
        "kendaraan, termasuk jadwal yang overdue, jatuh tempo hari ini, dan yang akan datang."
    )

    add_heading_styled(doc, "4.1 Mengakses Dashboard", level=2)

    add_step(doc, 1, "Setelah login, Anda akan langsung diarahkan ke halaman Dashboard Dispatcher.", has_screenshot=True, screenshot_caption="Dashboard Dispatcher")

    add_step(doc, 2, "Atau, klik menu \"Dashboard\" di sidebar sebelah kiri.", has_screenshot=True, screenshot_caption="Menu Dashboard")

    add_lampiran_box(doc, "Route: GET /dispatcher/dashboard | Name: dispatcher.dispatcher-dashboard | Controller: DispatcherAdministrationController@dashboard | View: resources/views/dispatcher/dashboard.blade.php")

    add_heading_styled(doc, "4.2 Kartu Statistik", level=2)

    add_step(doc, 1, "Dashboard menampilkan empat kartu statistik di bagian atas:")

    table = doc.add_table(rows=5, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Kartu", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")

    stats = [
        ("Overdue", "Jumlah jadwal pengecekan yang sudah lewat"),
        ("Due Today", "Jadwal pengecekan yang jatuh tempo hari ini"),
        ("Pending Replacement", "Jumlah VPR yang masih menunggu approval"),
        ("Recently Serviced", "Kendaraan yang baru selesai servis (24 jam terakhir)"),
    ]

    for row_idx, (stat, keterangan) in enumerate(stats, 1):
        table.cell(row_idx, 0).text = stat
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)

    add_heading_styled(doc, "4.3 Tabel Jadwal Kendaraan", level=2)

    add_step(doc, 1, "Di bawah kartu statistik, terdapat tiga tabel jadwal kendaraan:")

    p = doc.add_paragraph()
    run = p.add_run("   a. Overdue: ")
    run.bold = True
    run = p.add_run("Daftar kendaraan yang jadwal pengecekannya sudah lewat (disorot dengan badge merah).")

    p = doc.add_paragraph()
    run = p.add_run("   b. Due Today: ")
    run.bold = True
    run = p.add_run("Kendaraan yang perlu diperiksa hari ini.")

    p = doc.add_paragraph()
    run = p.add_run("   c. Upcoming 7 Hari: ")
    run.bold = True
    run = p.add_run("Jadwal pengecekan untuk 7 hari ke depan.")

    add_step(doc, 2, "Setiap tabel menampilkan informasi kendaraan, frekuensi pengecekan, dan dispatcher PIC yang ditugaskan.", has_screenshot=True, screenshot_caption="Tabel Jadwal Kendaraan")

    add_step(doc, 3, "Dashboard juga menyediakan tombol aksi cepat (Quick Actions) untuk:", has_screenshot=True, screenshot_caption="Quick Actions")

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Harian")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Mingguan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Bulanan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Body")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Ajukan Penggantian")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Export Laporan")
    run.font.size = Pt(11)

    add_info_box(doc, "Pantau kartu Overdue dan Due Today setiap hari untuk memastikan tidak ada pengecekan yang terlewat.")

    add_lampiran_box(doc, "Controller: DispatcherAdministrationController@dashboard | View: resources/views/dispatcher/dashboard.blade.php")

    # ============================================
    # HALAMAN 10: GENERATE PERIODE
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "5. Generate Periode Pengecekan", level=1)

    doc.add_paragraph(
        "Sebelum melakukan pengecekan, sistem perlu memiliki periode pengecekan bulanan sebagai header "
        "dari setiap pengecekan. Fitur Generate Period akan membuat header pengecekan untuk semua "
        "kendaraan aktif sekaligus."
    )

    add_heading_styled(doc, "5.1 Menggenerate Periode Bulanan", level=2)

    add_step(doc, 1, "Pada Dashboard Dispatcher, klik tombol \"Generate Period\" atau tombol \"Generate Pengecekan Bulanan\".", has_screenshot=True, screenshot_caption="Tombol Generate Period")

    add_step(doc, 2, "Sistem akan secara otomatis membuat:")

    p = doc.add_paragraph()
    run = p.add_run("   - Header pengecekan bulanan (VehicleChecking) untuk setiap kendaraan aktif.")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Jadwal mingguan, bulanan, dan body untuk 1 periode ke depan.")
    run.font.size = Pt(11)

    add_step(doc, 3, "Setelah berhasil, akan muncul notifikasi konfirmasi.", has_screenshot=True, screenshot_caption="Notifikasi Sukses Generate")

    add_warning_box(doc, "Generate Period sebaiknya dilakukan di awal bulan. Jika sudah ada periode untuk bulan berjalan, sistem tidak akan membuat duplikat. Pengecekan harian tidak memerlukan jadwal — dapat dilakukan kapan saja.")

    add_lampiran_box(doc, "Route: POST /dispatcher/generate-period | Name: dispatcher.generate-period | Controller: DispatcherAdministrationController@generatePeriod")

    # ============================================
    # HALAMAN 11: DAILY CHECK
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "6. Pemeriksaan Rutin Harian (Daily Check)", level=1)

    doc.add_paragraph(
        "Pemeriksaan Harian (Daily Check) adalah pengecekan singkat mengenai kesiapan dasar kendaraan "
        "sebelum beroperasi. Pemeriksaan ini dilakukan setiap hari oleh Dispatcher."
    )

    add_heading_styled(doc, "6.1 Membuat Pemeriksaan Harian", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Periksa Harian\" di grup Pemeriksaan Kendaraan.", has_screenshot=True, screenshot_caption="Menu Periksa Harian")

    add_step(doc, 2, "Klik tombol \"Buat Pemeriksaan Baru\" atau gunakan Quick Actions di Dashboard.", has_screenshot=True, screenshot_caption="Tombol Buat Pemeriksaan Baru")

    add_lampiran_box(doc, "Route: GET /dispatcher/daily-check/create | Name: dispatcher.daily-check.create | View: resources/views/dispatcher/daily-check/create.blade.php")

    add_step(doc, 3, "Pada form, pilih:")
    p = doc.add_paragraph()
    run = p.add_run("   - Kendaraan: ")
    run.bold = True
    run = p.add_run("Pilih kendaraan yang akan diperiksa dari dropdown.")
    p = doc.add_paragraph()
    run = p.add_run("   - Driver: ")
    run.bold = True
    run = p.add_run("Pilih driver/pengemudi kendaraan.")

    add_step(doc, 4, "Untuk setiap item pemeriksaan, tentukan status:", has_screenshot=True, screenshot_caption="Form Daily Check")

    table = doc.add_table(rows=3, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Pilihan", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")

    daily_opts = [
        ("OK (Hijau)", "Komponen dalam kondisi baik dan berfungsi normal"),
        ("NG (Merah)", "Komponen tidak berfungsi/bermasalah"),
    ]

    for row_idx, (pilihan, keterangan) in enumerate(daily_opts, 1):
        table.cell(row_idx, 0).text = pilihan
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)

    add_step(doc, 5, "Jika ada komponen yang perlu diganti, centang checkbox \"Ganti\" (Needs Replacement) pada item tersebut.", has_screenshot=True, screenshot_caption="Checkbox Ganti")

    add_step(doc, 6, "Tambahkan catatan pada kolom \"Notes\" jika diperlukan (opsional).")

    add_step(doc, 7, "Klik tombol \"Simpan\" untuk menyimpan hasil pemeriksaan.", has_screenshot=True, screenshot_caption="Tombol Simpan")

    add_info_box(doc, "Jika ada item yang ditandai \"Ganti\", sistem akan menawarkan untuk membuat permohonan penggantian suku cadang (VPR) setelah penyimpanan.")

    add_lampiran_box(doc, "Route: POST /dispatcher/daily-check | Name: dispatcher.daily-check.store | Controller: DailyCheckingController@store")

    add_heading_styled(doc, "6.2 Melihat Detail Hasil Pemeriksaan", level=2)

    add_step(doc, 1, "Pada halaman daftar Daily Check, klik tombol \"Detail\" pada baris pemeriksaan yang ingin dilihat.", has_screenshot=True, screenshot_caption="Tombol Detail Daily Check")

    add_step(doc, 2, "Halaman detail menampilkan:", has_screenshot=True, screenshot_caption="Detail Daily Check")

    p = doc.add_paragraph()
    run = p.add_run("   - Informasi pengecekan: ")
    run.bold = True
    run = p.add_run("Kode pengecekan, kendaraan, jenis, waktu, dispatcher, driver.")
    p = doc.add_paragraph()
    run = p.add_run("   - Ringkasan: ")
    run.bold = True
    run = p.add_run("Jumlah total item, OK, NG, dan yang perlu diganti.")
    p = doc.add_paragraph()
    run = p.add_run("   - Hasil per item: ")
    run.bold = True
    run = p.add_run("Daftar item pemeriksaan dengan status dan catatan masing-masing.")
    p = doc.add_paragraph()
    run = p.add_run("   - Status Acknowledge: ")
    run.bold = True
    run = p.add_run("Apakah driver sudah mengakui hasil pengecekan.")
    p = doc.add_paragraph()
    run = p.add_run("   - Tautan VPR: ")
    run.bold = True
    run = p.add_run("Jika ada VPR yang dibuat dari hasil pengecekan ini.")

    add_lampiran_box(doc, "Route: GET /dispatcher/daily-check/{checkingDetailCode} | Name: dispatcher.daily-check.show | Controller: DailyCheckingController@show | View: resources/views/dispatcher/daily-check/show.blade.php")

    # ============================================
    # HALAMAN 13: WEEKLY CHECK
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "7. Pemeriksaan Rutin Mingguan (Weekly Check)", level=1)

    doc.add_paragraph(
        "Pemeriksaan Mingguan (Weekly Check) adalah pengecekan komponen mekanis ringan yang dilakukan "
        "setiap minggu. Alur dan tampilan hampir sama dengan Daily Check."
    )

    add_heading_styled(doc, "7.1 Membuat Pemeriksaan Mingguan", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Periksa Mingguan\" di grup Pemeriksaan Kendaraan.", has_screenshot=True, screenshot_caption="Menu Periksa Mingguan")

    add_step(doc, 2, "Klik tombol \"Buat Pemeriksaan Baru\".", has_screenshot=True, screenshot_caption="Tombol Buat Pemeriksaan Mingguan")

    add_lampiran_box(doc, "Route: GET /dispatcher/weekly-check/create | Name: dispatcher.weekly-check.create | View: resources/views/dispatcher/weekly-check/create.blade.php")

    add_step(doc, 3, "Pilih kendaraan dan driver, lalu lakukan pemeriksaan pada setiap item dengan status OK/NG.", has_screenshot=True, screenshot_caption="Form Weekly Check")

    add_step(doc, 4, "Centang \"Ganti\" jika ada komponen yang perlu diganti.")

    add_step(doc, 5, "Klik \"Simpan\" untuk menyimpan hasil.", has_screenshot=True, screenshot_caption="Tombol Simpan Weekly Check")

    add_info_box(doc, "Item pemeriksaan mingguan berbeda dengan harian — sistem akan menampilkan komponen-komponen yang relevan untuk frekuensi mingguan.")

    add_lampiran_box(doc, "Route: POST /dispatcher/weekly-check | Name: dispatcher.weekly-check.store | Controller: WeeklyCheckingController@store")

    # ============================================
    # HALAMAN 14: MONTHLY CHECK
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "8. Pemeriksaan Rutin Bulanan (Monthly Check)", level=1)

    doc.add_paragraph(
        "Pemeriksaan Bulanan (Monthly Check) adalah pengecekan mendalam yang dilakukan setiap bulan. "
        "Sistem memiliki jadwal bulanan yang terintegrasi dengan Generate Period."
    )

    add_heading_styled(doc, "8.1 Membuat Pemeriksaan Bulanan", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Periksa Bulanan\" di grup Pemeriksaan Kendaraan.", has_screenshot=True, screenshot_caption="Menu Periksa Bulanan")

    add_step(doc, 2, "Klik tombol \"Buat Pemeriksaan Baru\".", has_screenshot=True, screenshot_caption="Tombol Buat Pemeriksaan Bulanan")

    add_lampiran_box(doc, "Route: GET /dispatcher/monthly-check/create | Name: dispatcher.monthly-check.create | View: resources/views/dispatcher/monthly-check/create.blade.php")

    add_step(doc, 3, "Pilih kendaraan dari dropdown — sistem akan menampilkan info jadwal (jadwal bulan ini, terakhir diperiksa, status).", has_screenshot=True, screenshot_caption="Form Monthly Check dengan Info Jadwal")

    add_step(doc, 4, "Lakukan pemeriksaan pada setiap item (OK/NG) dan centang \"Ganti\" bila perlu.")

    add_step(doc, 5, "Klik \"Simpan\" untuk menyimpan hasil.", has_screenshot=True, screenshot_caption="Tombol Simpan Monthly Check")

    add_info_box(doc, "Pastikan periode bulanan sudah di-generate sebelum melakukan monthly check. Jadwal monthly check akan otomatis diperbarui (last_checked_at, next_due_date) setelah pemeriksaan disimpan.")

    add_lampiran_box(doc, "Route: POST /dispatcher/monthly-check | Name: dispatcher.monthly-check.store | Controller: MonthlyCheckingController@store")

    # ============================================
    # HALAMAN 15: BODY CHECK
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "9. Pemeriksaan Body Kendaraan", level=1)

    doc.add_paragraph(
        "Pemeriksaan Body (Body Inspection) adalah inspeksi fisik terhadap kondisi body kendaraan "
        "menggunakan skala penilaian: Baik, Cukup, Kurang, dan Tidak Layak. Dispatcher wajib "
        "menyertakan foto kerusakan visual yang dipetakan ke 4 zona utama (Depan, Belakang, Sisi Kiri, Sisi Kanan)."
    )

    add_heading_styled(doc, "9.1 Membuat Pemeriksaan Body", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Periksa Body\" di grup Pemeriksaan Kendaraan.", has_screenshot=True, screenshot_caption="Menu Periksa Body")

    add_step(doc, 2, "Klik tombol \"Buat Pemeriksaan Baru\".", has_screenshot=True, screenshot_caption="Tombol Buat Pemeriksaan Body")

    add_lampiran_box(doc, "Route: GET /dispatcher/body-check/create | Name: dispatcher.body-check.create | View: resources/views/dispatcher/body-check/create.blade.php")

    add_step(doc, 3, "Pilih kendaraan dan driver yang akan diperiksa.", has_screenshot=True, screenshot_caption="Pilih Kendaraan Body Check")

    add_step(doc, 4, "Untuk setiap zona body, pilih status penilaian:")

    table = doc.add_table(rows=5, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Status", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")

    body_statuses = [
        ("Baik (Hijau)", "Tidak ada kerusakan, kondisi prima"),
        ("Cukup (Kuning)", "Ada sedikit kerusakan ringan, masih layak"),
        ("Kurang (Merah)", "Kerusakan cukup signifikan, perlu perbaikan"),
        ("Tidak Layak (Hitam)", "Kerusakan parah, perlu tindakan segera"),
    ]

    for row_idx, (status, keterangan) in enumerate(body_statuses, 1):
        table.cell(row_idx, 0).text = status
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)

    add_heading_styled(doc, "9.2 Upload Foto Kerusakan", level=2)

    add_step(doc, 1, "Untuk setiap zona yang memiliki kerusakan, klik tombol \"Upload Foto\" atau area upload yang tersedia.", has_screenshot=True, screenshot_caption="Upload Foto Kerusakan Body")

    add_step(doc, 2, "Pilih file gambar dari perangkat Anda (format JPG/PNG).")

    add_step(doc, 3, "Tambahkan deskripsi kerusakan pada kolom yang tersedia (opsional).", has_screenshot=True, screenshot_caption="Form Upload dengan Deskripsi")

    add_info_box(doc, "Foto kerusakan akan disimpan di sistem dan ditampilkan di halaman detail body check. Pastikan foto jelas dan menunjukkan area kerusakan dengan baik.")

    add_heading_styled(doc, "9.3 Melihat Detail Hasil Body Check", level=2)

    add_step(doc, 1, "Pada halaman daftar Body Check, klik tombol \"Detail\" untuk melihat hasil.", has_screenshot=True, screenshot_caption="Detail Body Check")

    add_step(doc, 2, "Halaman detail menampilkan:", has_screenshot=True, screenshot_caption="Detail Hasil Body Check")

    p = doc.add_paragraph()
    run = p.add_run("   - Ringkasan penilaian: ")
    run.bold = True
    run = p.add_run("Jumlah Baik, Cukup, Kurang, dan Tidak Layak.")
    p = doc.add_paragraph()
    run = p.add_run("   - Galeri foto: ")
    run.bold = True
    run = p.add_run("Foto kerusakan per zona dengan deskripsi.")

    add_lampiran_box(doc, "Route: GET /dispatcher/body-check/{checkingDetailCode} | Name: dispatcher.body-check.show | Controller: BodyCheckingController@show | View: resources/views/dispatcher/body-check/show.blade.php")

    # ============================================
    # HALAMAN 16: SCAN QR
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "10. Scan QR / Barcode Kendaraan", level=1)

    doc.add_paragraph(
        "Setiap kendaraan memiliki QR Code unik yang ditempel secara fisik. Dengan memindai QR Code "
        "tersebut, Dispatcher dapat dengan cepat melihat riwayat pengecekan atau memulai "
        "pemeriksaan baru tanpa harus mencari kendaraan di menu."
    )

    add_heading_styled(doc, "10.1 Scan untuk Melihat Riwayat", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Scanner Kendaraan\" lalu \"Scan Riwayat Kendaraan\".", has_screenshot=True, screenshot_caption="Menu Scan Riwayat")

    add_step(doc, 2, "Halaman scanner akan aktifkan kamera belakang perangkat.", has_screenshot=True, screenshot_caption="Scanner Camera")

    add_step(doc, 3, "Arahkan kamera ke QR Code yang ditempel di kendaraan.", has_screenshot=True, screenshot_caption="Scan QR Code")

    add_step(doc, 4, "Setelah terdeteksi, Anda akan diarahkan ke halaman riwayat pengecekan kendaraan tersebut.", has_screenshot=True, screenshot_caption="Riwayat Pengecekan Kendaraan")

    add_step(doc, 5, "Halaman riwayat menampilkan:")

    p = doc.add_paragraph()
    run = p.add_run("   - Informasi kendaraan (nomor polisi, merk, tipe)")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Statistik pengecekan (total, body, rutin, terakhir diperiksa)")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Riwayat pengecekan per tab (Semua / Body / Harian / Mingguan / Bulanan)")
    run.font.size = Pt(11)

    add_lampiran_box(doc, "Route: GET /dispatcher/scan-vehicle | Name: dispatcher.scan-vehicle | GET /dispatcher/scan-vehicle/result?code=... | Controller: VehicleHealthHistoryController@scanView / vehicleHistory | View: resources/views/dispatcher/barcode-scan/index.blade.php")

    add_heading_styled(doc, "10.2 Scan untuk Memulai Pemeriksaan Baru", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Scan untuk Periksa\".", has_screenshot=True, screenshot_caption="Menu Scan untuk Periksa")

    add_step(doc, 2, "Arahkan kamera ke QR Code kendaraan.", has_screenshot=True, screenshot_caption="Scanner untuk Periksa")

    add_step(doc, 3, "Setelah terdeteksi, Anda akan melihat halaman dengan 4 tombol:", has_screenshot=True, screenshot_caption="Pilihan Pemeriksaan")

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Harian")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Mingguan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Bulanan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Periksa Body")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Lihat Riwayat Kendaraan")
    run.font.size = Pt(11)

    add_step(doc, 4, "Klik tombol sesuai jenis pemeriksaan yang ingin dilakukan. Sistem akan otomatis membawa Anda ke form dengan kendaraan sudah terpilih.", has_screenshot=True, screenshot_caption="Form Pemeriksaan Terisi Otomatis")

    add_lampiran_box(doc, "Route: GET /dispatcher/dispatcher-scanner | Name: dispatcher.dispatcher-scanner | GET /dispatcher/dispatcher-scanner/result?code=... | Controller: VehicleHealthHistoryController@dispatcherScanView / dispatcherScanResult | View: resources/views/dispatcher/dispatcher-scanner/index.blade.php")

    add_heading_styled(doc, "10.3 Input Manual Nomor Kendaraan", level=2)

    add_step(doc, 1, "Jika kamera tidak berfungsi atau QR Code rusak, Anda dapat memasukkan nomor polisi kendaraan secara manual.", has_screenshot=True, screenshot_caption="Input Manual Nomor Kendaraan")

    add_step(doc, 2, "Pada halaman scanner, terdapat kolom input teks. Ketik nomor polisi atau kode kendaraan.")

    add_step(doc, 3, "Klik tombol \"Cari\" untuk menampilkan hasil.", has_screenshot=True, screenshot_caption="Tombol Cari Manual")

    add_info_box(doc, "Pastikan QR Code kendaraan dalam kondisi baik dan dapat dipindai. Jika QR Code rusak/hilang, hubungi Superadmin untuk mencetak ulang.")

    # ============================================
    # HALAMAN 18: VPR
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "11. Permohonan Penggantian Suku Cadang (VPR)", level=1)

    doc.add_paragraph(
        "Vehicle Parts Replacement (VPR) adalah permohonan penggantian suku cadang kendaraan yang "
        "diajukan oleh Dispatcher ketika ditemukan komponen yang memerlukan penggantian saat pengecekan. "
        "VPR akan diproses oleh Admin/GA untuk disetujui dan dijadwalkan."
    )

    add_heading_styled(doc, "11.1 Auto-Create VPR dari Hasil Pengecekan", level=2)

    add_step(doc, 1, "Setelah menyimpan hasil pengecekan yang memiliki item bertanda \"Ganti\", akan muncul notifikasi.", has_screenshot=True, screenshot_caption="Notifikasi Usulan VPR")

    add_step(doc, 2, "Klik tombol \"Auto-Ajukan\" pada halaman detail pengecekan.", has_screenshot=True, screenshot_caption="Tombol Auto-Ajukan VPR")

    add_step(doc, 3, "Sistem akan otomatis membuat VPR yang berisi semua item yang ditandai perlu diganti.", has_screenshot=True, screenshot_caption="VPR Berhasil Dibuat")

    add_info_box(doc, "Auto-Create VPR akan membuat permohonan dengan semua item yang perlu diganti sekaligus. Anda tidak perlu memasukkan data ulang.")

    add_lampiran_box(doc, "Route: POST /dispatcher/replacement/auto-create/{checkingDetailCode} | Name: dispatcher.replacement.auto-create | Controller: DispatcherAdministrationController@replacementAutoCreate")

    add_heading_styled(doc, "11.2 Membuat VPR Manual", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Permohonan Penggantian\".", has_screenshot=True, screenshot_caption="Menu Permohonan Penggantian")

    add_step(doc, 2, "Klik tombol \"Buat Permohonan Baru\".", has_screenshot=True, screenshot_caption="Tombol Buat VPR Baru")

    add_lampiran_box(doc, "Route: GET /dispatcher/replacement/create/{checkingDetailCode} | Name: dispatcher.replacement.create | View: resources/views/dispatcher/replacement/create.blade.php")

    add_step(doc, 3, "Anda akan melihat daftar item dari hasil pengecekan yang dapat dipilih:", has_screenshot=True, screenshot_caption="Form VPR Manual")

    p = doc.add_paragraph()
    run = p.add_run("   - Centang item-item yang ingin diajukan penggantiannya.")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Tambahkan catatan per item jika perlu.")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Pilih driver yang diinginkan (opsional).")
    run.font.size = Pt(11)

    add_step(doc, 4, "Klik tombol \"Ajukan Permohonan\" di sidebar kanan.", has_screenshot=True, screenshot_caption="Tombol Ajukan Permohonan")

    add_info_box(doc, "Pastikan item yang diajukan benar-benar memerlukan penggantian. Admin akan mereview permohonan Anda.")

    add_lampiran_box(doc, "Route: POST /dispatcher/replacement/store/{checkingDetailCode} | Name: dispatcher.replacement.store | Controller: DispatcherAdministrationController@replacementStore")

    add_heading_styled(doc, "11.3 Melihat Status VPR", level=2)

    add_step(doc, 1, "Pada halaman daftar VPR, Anda dapat melihat status setiap permohonan:", has_screenshot=True, screenshot_caption="Daftar VPR dengan Status")

    table = doc.add_table(rows=6, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    headers = ["Status", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")

    vpr_statuses = [
        ("Pending", "Menunggu persetujuan Admin/GA"),
        ("Approved", "Disetujui, menunggu penjadwalan"),
        ("Scheduled", "Sudah dijadwalkan, menunggu servis"),
        ("In Service", "Kendaraan sedang dalam proses servis"),
        ("Completed", "Servis selesai — VPR selesai"),
    ]

    for row_idx, (status, keterangan) in enumerate(vpr_statuses, 1):
        table.cell(row_idx, 0).text = status
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)

    add_step(doc, 2, "Klik tombol \"Detail\" pada VPR untuk melihat informasi lengkap.")

    add_step(doc, 3, "Halaman detail menampilkan timeline status dan informasi lengkap:", has_screenshot=True, screenshot_caption="Detail VPR")

    p = doc.add_paragraph()
    run = p.add_run("   - Timeline 5 langkah: ")
    run.bold = True
    run = p.add_run("Pending -> Approved -> Scheduled -> In Service -> Completed.")
    p = doc.add_paragraph()
    run = p.add_run("   - Informasi permohonan: ")
    run.bold = True
    run = p.add_run("Kode VPR, kendaraan, dispatcher, driver, tanggal.")
    p = doc.add_paragraph()
    run = p.add_run("   - Daftar item: ")
    run.bold = True
    run = p.add_run("Komponen yang diajukan dengan kondisi saat pengecekan.")
    p = doc.add_paragraph()
    run = p.add_run("   - Informasi servis: ")
    run.bold = True
    run = p.add_run("Jika sudah selesai: rincian biaya, foto bukti.")

    add_warning_box(doc, "Dispatcher tidak dapat mengubah atau membatalkan VPR setelah dikirim. Hubungi Admin jika ada perubahan yang diperlukan.")

    add_lampiran_box(doc, "Route: GET /dispatcher/replacement/{vprCode} | Name: dispatcher.replacement.show | Controller: DispatcherAdministrationController@replacementShow | View: resources/views/dispatcher/replacement/show.blade.php")

    # ============================================
    # HALAMAN 20: RIWAYAT & LAPORAN
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "12. Riwayat & Laporan Pengecekan", level=1)

    doc.add_paragraph(
        "Menu Riwayat & Laporan menampilkan semua data pengecekan kendaraan yang pernah dilakukan. "
        "Dispatcher dapat mencari, menyaring, dan mengekspor data tersebut."
    )

    add_heading_styled(doc, "12.1 Melihat Riwayat Semua Pengecekan", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Riwayat & Laporan\".", has_screenshot=True, screenshot_caption="Menu Riwayat & Laporan")

    add_step(doc, 2, "Halaman menampilkan tabel semua pengecekan dengan informasi:", has_screenshot=True, screenshot_caption="Tabel Riwayat Pengecekan")

    p = doc.add_paragraph()
    run = p.add_run("   - Kode pengecekan dan kendaraan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Jenis pengecekan (body/daily/weekly/monthly) dengan badge warna")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Dispatcher dan Driver")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Tanggal pengecekan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Hasil ringkasan (OK/NG atau B/C/K/T)")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Status Acknowledge driver")
    run.font.size = Pt(11)

    add_lampiran_box(doc, "Route: GET /dispatcher/history | Name: dispatcher.history | Controller: DispatcherAdministrationController@history | View: resources/views/dispatcher/history/index.blade.php")

    add_heading_styled(doc, "12.2 Filter Pencarian Riwayat", level=2)

    add_step(doc, 1, "Gunakan filter di bagian atas tabel untuk mempersempit pencarian:")

    p = doc.add_paragraph()
    run = p.add_run("   - Kendaraan: ")
    run.bold = True
    run = p.add_run("Filter berdasarkan kendaraan tertentu.")
    p = doc.add_paragraph()
    run = p.add_run("   - Tipe Pengecekan: ")
    run.bold = True
    run = p.add_run("Semua / Rutin / Body.")
    p = doc.add_paragraph()
    run = p.add_run("   - Driver / Dispatcher: ")
    run.bold = True
    run = p.add_run("Filter berdasarkan petugas.")
    p = doc.add_paragraph()
    run = p.add_run("   - Rentang Tanggal: ")
    run.bold = True
    run = p.add_run("Dari tanggal s/d tanggal.")

    add_info_box(doc, "Gunakan filter untuk memudahkan pencarian riwayat pengecekan kendaraan tertentu dalam periode waktu tertentu.")

    # ============================================
    # HALAMAN 21: EXPORT
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "13. Export Form Pengecekan", level=1)

    doc.add_paragraph(
        "Dispatcher dapat mengekspor data pengecekan kendaraan ke format PDF dan Excel untuk "
        "keperluan dokumentasi dan pelaporan."
    )

    add_heading_styled(doc, "13.1 Export ke PDF", level=2)

    add_step(doc, 1, "Pada halaman Riwayat & Laporan, klik tombol \"Export PDF\".", has_screenshot=True, screenshot_caption="Tombol Export PDF di Halaman Riwayat")

    add_step(doc, 2, "Terdapat tiga opsi export PDF:")

    p = doc.add_paragraph()
    run = p.add_run("   a. Vehicle Checklist: ")
    run.bold = True
    run = p.add_run("PDF dikelompokkan per kendaraan, mencakup semua jenis pengecekan. Format A4 Landscape.")

    p = doc.add_paragraph()
    run = p.add_run("   b. Routine Check Report: ")
    run.bold = True
    run = p.add_run("PDF yang hanya berisi pengecekan rutin (harian, mingguan, bulanan). Format A4 Landscape.")

    p = doc.add_paragraph()
    run = p.add_run("   c. Body Check Report: ")
    run.bold = True
    run = p.add_run("PDF yang berisi pengecekan body termasuk foto kerusakan. Format A4 Portrait.")

    add_step(doc, 3, "Pilih jenis export, isi rentang tanggal, lalu klik tombol export.", has_screenshot=True, screenshot_caption="Form Export PDF")

    add_lampiran_box(doc, "Routes: POST /dispatcher/export/vehicle-checklist, /routine-check, /body-check | Names: dispatcher.export.* | Controller: DispatcherAdministrationController@export*")

    add_heading_styled(doc, "13.2 Export ke Excel", level=2)

    add_step(doc, 1, "Dari sidebar, pilih menu \"Export Form Pengecekan\".", has_screenshot=True, screenshot_caption="Menu Export Form Pengecekan")

    add_step(doc, 2, "Pilih kendaraan, bulan, dan tahun yang ingin diexport.", has_screenshot=True, screenshot_caption="Form Export Excel")

    add_step(doc, 3, "Klik tombol \"Download\" untuk mengunduh file Excel.", has_screenshot=True, screenshot_caption="Tombol Download Excel")

    add_step(doc, 4, "File Excel berisi format:")

    p = doc.add_paragraph()
    run = p.add_run("   - Kolom harian (V = OK, X = NG)")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Baris mingguan dan item bulanan")
    run.font.size = Pt(11)

    p = doc.add_paragraph()
    run = p.add_run("   - Baris driver dan tanda tangan (dikosongkan untuk pengisian manual)")
    run.font.size = Pt(11)

    add_info_box(doc, "Format Excel dapat langsung dicetak sebagai form checklist fisik. Baris driver dan tanda tangan sengaja dikosongkan untuk diisi manual.")

    add_lampiran_box(doc, "Route: GET /dispatcher/export-checking | Name: dispatcher.export-checking.index | POST /dispatcher/export-checking | Name: dispatcher.export-checking.download | View: resources/views/dispatcher/export-checking/index.blade.php")

    # ============================================
    # HALAMAN PENUTUP
    # ============================================
    add_page_break(doc)

    add_heading_styled(doc, "14. Penutup", level=1)

    doc.add_paragraph(
        "Buku panduan ini disusun sebagai referensi bagi pengguna role Dispatcher dalam menggunakan "
        "Sistem Administrasi General Affairs (Siaga) di PT Geo Dipa Energi (Persero). "
        "Diharapkan dengan adanya panduan ini, dispatcher dapat memahami hak akses dan batasannya, "
        "serta menjalankan seluruh prosedur pengecekan kendaraan — mulai dari generate period, "
        "pemeriksaan rutin dan body, scan QR, permohonan penggantian suku cadang, "
        "hingga export laporan — dengan baik dan benar."
    )

    doc.add_paragraph("")

    doc.add_paragraph("Apabila terdapat pertanyaan, saran, atau kendala terkait penggunaan sistem, "
                      "silakan hubungi Tim IT Developer melalui:")

    doc.add_paragraph("Email: it.developer@geodipa.co.id", style='List Bullet')
    doc.add_paragraph("Telepon: Ext. XXXX", style='List Bullet')
    doc.add_paragraph("Meeting: Tim IT Developer", style='List Bullet')

    doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Terima Kasih")
    run.bold = True
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(1, 41, 112)

    doc.add_paragraph("")

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Tim IT Developer")
    run.font.size = Pt(12)
    run.font.color.rgb = RGBColor(100, 100, 100)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("PT Geo Dipa Energi (Persero)")
    run.font.size = Pt(12)
    run.font.color.rgb = RGBColor(100, 100, 100)

    # Save document
    output_path = "/Users/farisiftikharalfarisi/Desktop/Laravel_Project/Geodipa/BBM_Project/Buku_Panduan_Dispatcher_Siaga_v1.0.0.docx"
    doc.save(output_path)
    print(f"Dokumen berhasil disimpan di: {output_path}")
    return output_path

if __name__ == "__main__":
    create_document()