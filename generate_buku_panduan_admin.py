#!/usr/bin/env python3
"""
Script to generate Buku Panduan Admin Role - BBM Approval - DOCX format
"""

from docx import Document
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

def set_cell_shading(cell, color):
    """Set cell background color"""
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
    for _ in range(5):
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
    
    # Untuk: Admin
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Untuk : Admin")
    run.font.size = Pt(18)
    run.font.color.rgb = RGBColor(65, 84, 241)
    run.italic = True
    
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
    
    doc.add_paragraph("Dokumen ini merupakan panduan resmi penggunaan Sistem Administrasi General Affairs untuk role Admin di PT Geo Dipa Energi (Persero).", style='List Bullet')
    doc.add_paragraph("Setiap admin diwajibkan memahami dan mengikuti prosedur yang tercantum dalam dokumen ini.", style='List Bullet')
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
        ("2.", "Hak Akses dan Batasan Admin", ""),
        ("", "2.1 Definisi Role Admin (General Affairs)", ""),
        ("", "2.2 Hak Akses (Apa yang Bisa Dilakukan)", ""),
        ("", "2.3 Batasan Admin (Apa yang Tidak Bisa Dilakukan)", ""),
        ("", "2.4 Unit Scope (Pembatasan Data per Unit)", ""),
        ("", "2.5 Struktur Menu Sidebar Admin", ""),
        ("3.", "Prosedur Login & Logout", ""),
        ("", "3.1 Akses Sistem", ""),
        ("", "3.2 Proses Login", ""),
        ("", "3.3 Proses Logout", ""),
        ("4.", "Prosedur Approval BBM", ""),
        ("", "4.1 Dashboard Admin", ""),
        ("", "4.2 Melihat Permohonan Pending", ""),
        ("", "4.3 Approval/Rejection Permohonan (Satuan)", ""),
        ("", "4.4 Approval/Rejection Bulk (Massal)", ""),
        ("", "4.5 Melihat Riwayat Approval Saya", ""),
        ("", "4.6 Melihat Semua History & Laporan", ""),
        ("", "4.7 Melihat Kode Voucher Kemarin", ""),
        ("5.", "Master Harga BBM & Benchmark Kendaraan", ""),
        ("", "5.1 Melihat Master Harga BBM", ""),
        ("", "5.2 Sinkronisasi Harga BBM", ""),
        ("", "5.3 Melihat Benchmark BBM per Kendaraan", ""),
        ("6.", "Persetujuan Penggantian Suku Cadang (VPR)", ""),
        ("", "6.1 Mengakses Daftar Permohonan VPR", ""),
        ("", "6.2 Menyetujui atau Menolak VPR", ""),
        ("", "6.3 Penjadwalan Servis", ""),
        ("7.", "Penjadwalan Servis Kendaraan", ""),
        ("", "7.1 Mengakses Kalender Servis", ""),
        ("8.", "Monitoring Transaksi Servis", ""),
        ("", "8.1 Melihat Daftar Transaksi Servis", ""),
        ("9.", "Manajemen Makan Siang (Lunch Management)", ""),
        ("", "9.1 Dashboard Lunch", ""),
        ("", "9.2 Mengelola Master Pekerja", ""),
        ("", "9.3 Generate Forecast H+1", ""),
        ("", "9.4 Distribusi & Lock untuk Vendor", ""),
        ("", "9.5 Export Laporan untuk Vendor", ""),
        ("", "9.6 Operasional Harian Lunch", ""),
        ("10.", "Asisten AI — Gaga", ""),
        ("", "10.1 Mengakses Gaga", ""),
        ("", "10.2 Bertanya pada Gaga", ""),
        ("", "10.3 Contoh Pertanyaan", ""),
        ("11.", "Penutup", ""),
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
    doc.add_paragraph("Memberikan panduan lengkap bagi pengguna role Admin dalam menggunakan sistem Siaga.", style='List Bullet')
    doc.add_paragraph("Memastikan seluruh proses operasional GA berjalan sesuai prosedur yang ditetapkan.", style='List Bullet')
    doc.add_paragraph("Mengurangi kesalahan penggunaan sistem dalam proses approval, servis, dan manajemen lunch.", style='List Bullet')
    doc.add_paragraph("Menjadi referensi bagi admin baru dalam memahami alur kerja dan fitur-fitur sistem.", style='List Bullet')
    
    add_heading_styled(doc, "1.3 Ruang Lingkup", level=2)
    doc.add_paragraph("Buku panduan ini mencakup seluruh prosedur dan fitur yang tersedia untuk role Admin (General Affairs) dalam sistem SIAGA, meliputi:")
    doc.add_paragraph("Hak akses dan batasan role Admin (Unit Scope)", style='List Bullet')
    doc.add_paragraph("Login dan Logout dari sistem", style='List Bullet')
    doc.add_paragraph("Dashboard Admin dan approval BBM (persetujuan dan penolakan permohonan BBM)", style='List Bullet')
    doc.add_paragraph("Melihat riwayat approval, laporan transaksi, dan kode voucher", style='List Bullet')
    doc.add_paragraph("Master Harga BBM dan benchmark kendaraan", style='List Bullet')
    doc.add_paragraph("Persetujuan penggantian suku cadang kendaraan (VPR)", style='List Bullet')
    doc.add_paragraph("Penjadwalan servis kendaraan", style='List Bullet')
    doc.add_paragraph("Monitoring transaksi servis kendaraan", style='List Bullet')
    doc.add_paragraph("Manajemen katering makan siang (Lunch Management)", style='List Bullet')
    doc.add_paragraph("Asisten AI (Gaga) untuk konsultasi data operasional", style='List Bullet')
    
    # ============================================
    # HALAMAN 5: HAK AKSES DAN BATASAN ADMIN
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "2. Hak Akses dan Batasan Admin", level=1)
    
    doc.add_paragraph(
        "Bab ini menjelaskan secara rinci tentang hak akses (permissions) yang dimiliki oleh role Admin "
        "(dikenal juga sebagai General Affairs / GA) serta batasan-batasan yang membedakannya dari role Superadmin. "
        "Memahami hak akses ini penting agar Admin dapat menjalankan tugas sesuai wewenangnya."
    )
    
    add_heading_styled(doc, "2.1 Definisi Role Admin (General Affairs)", level=2)
    doc.add_paragraph(
        "Admin atau General Affairs (GA) adalah pengguna sistem yang bertanggung jawab mengelola "
        "operasional administrasi harian, meliputi persetujuan BBM, persetujuan penggantian suku cadang, "
        "penjadwalan servis kendaraan, dan manajemen katering makan siang. Admin dapat memiliki lingkup "
        "data yang terbatas pada Unit Bisnis tertentu (Unit Scope) atau bersifat global (seluruh unit)."
    )
    
    add_info_box(doc, "Dalam sistem SIAGA, role Admin disimpan dengan kode 'general_affairs' atau 'admin' pada kolom user.role, dan dapat memiliki permission tambahan melalui sistem RBAC (Role-Based Access Control).")
    
    add_heading_styled(doc, "2.2 Hak Akses (Apa yang Bisa Dilakukan)", level=2)
    
    doc.add_paragraph("Berikut adalah daftar lengkap fitur yang dapat diakses oleh role Admin:")
    
    table = doc.add_table(rows=11, cols=3)
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
        ("BBM", "Dashboard Admin, approval/rejection permohonan, export voucher", "/admin/dashboard, /admin/approval/*"),
        ("BBM", "Melihat semua history & laporan transaksi", "admin-dashboard (tab History)"),
        ("BBM", "Melihat & export kode voucher kemarin", "/vouchers/list, /vouchers/export"),
        ("Suku Cadang", "Persetujuan/rejection VPR & penjadwalan servis", "/admin/replacement-approval/*"),
        ("Servis", "Kalender penjadwalan servis kendaraan", "/admin/service-scheduling"),
        ("Servis", "Monitoring transaksi servis", "/admin/transaction-monitoring"),
        ("Master Data", "Master Harga BBM & sync dari API", "/master-gasoline"),
        ("Lunch", "Seluruh modul Lunch Management (dashboard, forecast, distribusi, dll)", "/lunch/*"),
        ("Laporan", "Cetak laporan BBM", "/print-report"),
        ("AI", "Asisten AI Gaga (konsultasi data)", "/ai-assistant"),
    ]
    
    for row_idx, (modul, fitur, route) in enumerate(rights, 1):
        table.cell(row_idx, 0).text = modul
        table.cell(row_idx, 1).text = fitur
        table.cell(row_idx, 2).text = route
        for col in range(3):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(9)
    
    add_heading_styled(doc, "2.3 Batasan Admin (Apa yang Tidak Bisa Dilakukan)", level=2)
    
    doc.add_paragraph(
        "Berikut adalah fitur-fitur yang TIDAK dapat diakses oleh Admin. Fitur-fitur ini hanya tersedia "
        "untuk role Superadmin:"
    )
    
    table = doc.add_table(rows=11, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    headers = ["Fitur Superadmin", "Keterangan"]
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
        ("Manajemen Pengguna", "Tambah/edit/hapus user, reset password"),
        ("Manajemen Kendaraan", "CRUD kendaraan, approve/reject aktivasi kendaraan baru"),
        ("CRUD Unit & Site", "Tambah/edit/hapus Unit Bisnis dan Site/Lokasi"),
        ("RBAC", "Kelola role, permission, dan grup permission"),
        ("Manajemen SPD & LPJ", "Approve/reject surat SPD, perpanjangan, LPJ — ini wewenang Superadmin"),
        ("Dashboard Bisnis", "Dashboard analitik dengan grafik & pivot table"),
        ("Trip Analysis", "Analisis efisiensi BBM, rekap trip, validasi"),
        ("Activity Log", "Log aktivitas seluruh pengguna"),
        ("Vehicle Health Matrix", "Matriks kesehatan kendaraan dan financial report"),
        ("Download Barcode", "Download barcode/QR kendaraan (single & ZIP)"),
    ]
    
    for row_idx, (fitur, keterangan) in enumerate(limits, 1):
        table.cell(row_idx, 0).text = fitur
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(9)
    
    add_heading_styled(doc, "2.4 Unit Scope (Pembatasan Data per Unit)", level=2)
    
    doc.add_paragraph(
        "Sistem SIAGA menerapkan konsep Unit Scope, yaitu Admin yang ditugaskan pada Unit Bisnis tertentu "
        "hanya dapat melihat dan memproses data yang terkait dengan site-site di bawah Unit tersebut."
    )
    
    doc.add_paragraph("Contoh: Admin yang ditugaskan di Unit Patuha hanya dapat:", style='List Bullet')
    doc.add_paragraph("Melihat permohonan BBM dari site PATUHA, PTH-BC, dan SUMUR-1.", style='List Bullet')
    doc.add_paragraph("Memproses VPR dan jadwal servis kendaraan yang terdaftar di site Patuha.", style='List Bullet')
    doc.add_paragraph("Mengelola data lunch (forecast, distribusi, transaksi) untuk site Patuha.", style='List Bullet')
    doc.add_paragraph("Tidak dapat melihat data dari site DIENG (Unit Dieng) atau site PUSAT (Unit Pusat).", style='List Bullet')
    
    doc.add_paragraph("")
    doc.add_paragraph("Admin tanpa unit_id (Global/Pusat) dapat melihat data dari seluruh Unit Bisnis.", style='List Bullet')
    
    add_warning_box(doc, "Unit Scope diterapkan secara otomatis oleh sistem melalui scope query inUnit(). Admin tidak perlu melakukan konfigurasi apa pun. Jika merasa data yang seharusnya muncul tidak tampil, hubungi Superadmin untuk memeriksa konfigurasi unit Anda.")
    
    add_heading_styled(doc, "2.5 Struktur Menu Sidebar Admin", level=2)
    
    doc.add_paragraph("Setelah login sebagai Admin, sidebar menu menampilkan item-item berikut:")
    
    p = doc.add_paragraph()
    run = p.add_run("Dashboard: ")
    run.bold = True
    run = p.add_run("Dashboard Admin (approval BBM).")
    
    p = doc.add_paragraph()
    run = p.add_run("Master Data: ")
    run.bold = True
    run = p.add_run("Master Harga BBM.")
    
    p = doc.add_paragraph()
    run = p.add_run("Vehicle Checking (collapsible): ")
    run.bold = True
    run = p.add_run("Replacement Approval, Service Scheduling, Transaction Monitoring.")
    
    p = doc.add_paragraph()
    run = p.add_run("Lunch Management (collapsible, 10 sub-menu): ")
    run.bold = True
    run = p.add_run("Dashboard Lunch, Employee Default, Guest Reservation, Forecast, Distribution, Transaction, Analytics, QR Lokasi, Export Vendor, Booking Mandiri.")
    
    p = doc.add_paragraph()
    run = p.add_run("Laporan & Analisis: ")
    run.bold = True
    run = p.add_run("Cetak Laporan BBM, Gaga (AI Assistant).")
    
    add_info_box(doc, "Menu sidebar bersifat dinamis — item yang tidak memiliki akses (mis. SPD, RBAC, Users) tidak akan tampil. Menu aktif akan terbuka secara otomatis tanpa efek lompatan halaman.")
    
    # ============================================
    # HALAMAN 10: PROCEDURE LOGIN & LOGOUT
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
    
    add_info_box(doc, "Jika login berhasil, Anda akan diarahkan ke halaman Dashboard Admin. Jika gagal, akan muncul pesan error \"Username atau password salah\".")
    
    add_lampiran_box(doc, "Route: POST /login-process | Controller: AuthentiactionController@authenticate | View: resources/views/authentication/login.blade.php")
    
    add_heading_styled(doc, "3.3 Proses Logout", level=2)
    
    add_step(doc, 1, "Klik icon profil atau nama Anda di pojok kanan atas halaman.", has_screenshot=True, screenshot_caption="Icon Profil Pengguna")
    
    add_step(doc, 2, "Pilih opsi \"Logout\" dari dropdown menu.", has_screenshot=True, screenshot_caption="Menu Logout")
    
    add_step(doc, 3, "Anda akan diarahkan kembali ke halaman login.", has_screenshot=True, screenshot_caption="Halaman Login Setelah Logout")
    
    add_info_box(doc, "Selalu lakukan logout setelah selesai menggunakan sistem untuk menjaga keamanan akun Anda.")
    
    add_lampiran_box(doc, "Route: GET/POST /logout | Controller: AuthentiactionController@logout")
    
    # ============================================
    # HALAMAN 6-N: PROCEDURE APPROVAL BBM
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "4. Prosedur Approval BBM", level=1)
    
    doc.add_paragraph(
        "Bagian ini menjelaskan langkah-langkah approval permohonan BBM, termasuk melihat dashboard, "
        "memproses permohonan pending, melihat riwayat approval, dan melihat laporan transaksi."
    )
    
    # 4.1 Dashboard Admin
    add_heading_styled(doc, "4.1 Dashboard Admin", level=2)
    
    add_step(doc, 1, "Setelah login, Anda akan diarahkan ke halaman Dashboard Admin.", has_screenshot=True, screenshot_caption="Dashboard Admin")
    
    add_step(doc, 2, "Dashboard menampilkan empat kartu statistik:")
    
    table = doc.add_table(rows=5, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    headers = ["Statistik", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")
    
    stats = [
        ("Pending", "Jumlah permohonan yang menunggu approval"),
        ("Approved", "Jumlah permohonan yang sudah disetujui"),
        ("Rejected", "Jumlah permohonan yang ditolak"),
        ("Total Permohonan", "Semua permohonan (semua status)"),
    ]
    
    for row_idx, (stat, keterangan) in enumerate(stats, 1):
        table.cell(row_idx, 0).text = stat
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_step(doc, 3, "Dashboard juga memiliki empat tab utama:")
    
    p = doc.add_paragraph()
    run = p.add_run("   a. Permohonan Pending: ")
    run.bold = True
    run = p.add_run("Menampilkan daftar permohonan yang perlu diproses.")
    
    p = doc.add_paragraph()
    run = p.add_run("   b. Riwayat Approval Saya: ")
    run.bold = True
    run = p.add_run("Menampilkan riwayat approval yang sudah Anda proses.")
    
    p = doc.add_paragraph()
    run = p.add_run("   c. Semua History & Laporan: ")
    run.bold = True
    run = p.add_run("Menampilkan semua permohonan dan laporan transaksi.")
    
    p = doc.add_paragraph()
    run = p.add_run("   d. Kode Voucher Kemarin: ")
    run.bold = True
    run = p.add_run("Menampilkan kode voucher yang terbit kemarin.")
    
    add_lampiran_box(doc, "Route: GET /admin/dashboard | Name: admin-dashboard | View: resources/views/admin/index.blade.php")
    
    # 3.2 Melihat Permohonan Pending
    add_heading_styled(doc, "4.2 Melihat Permohonan Pending", level=2)
    
    add_step(doc, 1, "Klik tab \"Permohonan Pending\" pada Dashboard Admin.", has_screenshot=True, screenshot_caption="Tab Permohonan Pending")
    
    add_step(doc, 2, "Anda akan melihat daftar permohonan yang menunggu approval. Setiap permohonan ditampilkan dalam bentuk kartu dengan informasi:", has_screenshot=True, screenshot_caption="Daftar Permohonan Pending")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Request ID: ")
    run.bold = True
    run = p.add_run("Nomor permohonan unik.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Pemohon: ")
    run.bold = True
    run = p.add_run("Nama lengkap pemohon.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Tanggal: ")
    run.bold = True
    run = p.add_run("Tanggal pengajuan permohonan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kendaraan: ")
    run.bold = True
    run = p.add_run("Kode dan nama kendaraan/alat.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - BBM: ")
    run.bold = True
    run = p.add_run("Jenis BBM yang dimohonkan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jumlah: ")
    run.bold = True
    run = p.add_run("Nominal permohonan dalam Rupiah.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Catatan: ")
    run.bold = True
    run = p.add_run("Catatan dari pemohon (jika ada).")
    
    add_info_box(doc, "Permohonan pending ditampilkan berdasarkan tanggal pengajuan terbaru (descending). Gunakan fitur pencarian untuk menemukan permohonan tertentu.")
    
    add_lampiran_box(doc, "View: resources/views/admin/index.blade.php (tab Permohonan Pending) | resources/views/admin/approval.blade.php")
    
    # 3.3 Approval/Rejection Satuan
    add_heading_styled(doc, "4.3 Approval/Rejection Permohonan (Satuan)", level=2)
    
    add_step(doc, 1, "Pada kartu permohonan yang ingin diproses, Anda dapat menambahkan komentar admin (opsional).", has_screenshot=True, screenshot_caption="Input Komentar Admin")
    
    add_step(doc, 2, "Klik tombol \"Approve\" untuk menyetujui permohonan, atau \"Reject\" untuk menolak.", has_screenshot=True, screenshot_caption="Tombol Approve dan Reject")
    
    add_step(doc, 3, "Tombol akan berubah warna saat dipilih:")
    
    table = doc.add_table(rows=3, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    headers = ["Tombol", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")
    
    buttons = [
        ("Approve (Hijau)", "Permohonan disetujui, voucher akan otomatis terbuat"),
        ("Reject (Merah)", "Permohonan ditolak, pemohon akan diberitahu"),
    ]
    
    for row_idx, (tombol, keterangan) in enumerate(buttons, 1):
        table.cell(row_idx, 0).text = tombol
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_info_box(doc, "Saat Anda menyetujui permohonan, sistem akan otomatis membuat kode voucher yang berlaku selama 50 jam. Pemohon akan menerima notifikasi dan dapat mencetak voucher untuk pengisian BBM di SPBU.")
    
    add_lampiran_box(doc, "Route: POST /admin/approval/{id}/process | Name: admin-process | Controller: RequestManagementController@admin_process_request")
    
    # 3.4 Approval/Rejection Bulk
    add_heading_styled(doc, "4.4 Approval/Rejection Bulk (Massal)", level=2)
    
    add_step(doc, 1, "Pada tab \"Permohonan Pending\", centang checkbox \"Approve\" atau \"Reject\" pada setiap permohonan yang ingin diproses.", has_screenshot=True, screenshot_caption="Checkbox Approve/Reject")
    
    add_step(doc, 2, "Bulk Action Bar akan muncul di atas daftar permohonan yang menunjukkan:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jumlah permohonan yang dipilih")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jumlah approve dan reject")
    run.font.size = Pt(11)
    
    add_step(doc, 3, "Anda juga dapat menambahkan komentar admin per permohonan pada kolom \"Komentar Admin\".", has_screenshot=True, screenshot_caption="Bulk Action Bar")
    
    add_step(doc, 4, "Klik tombol \"Simpan Keputusan\" pada Bulk Action Bar.", has_screenshot=True, screenshot_caption="Tombol Simpan Keputusan")
    
    add_step(doc, 5, "Konfirmasi keputusan dengan klik \"OK\" pada dialog konfirmasi.", has_screenshot=True, screenshot_caption="Dialog Konfirmasi")
    
    add_warning_box(doc, "Pastikan Anda sudah memeriksa dengan teliti sebelum melakukan approval/rejection massal. Keputusan yang sudah disimpan tidak dapat dibatalkan.")
    
    add_lampiran_box(doc, "Route: POST /admin/approval/bulk-process | Name: admin-bulk-process | Controller: RequestManagementController@admin_bulk_process")
    
    # 3.5 Melihat Riwayat Approval Saya
    add_heading_styled(doc, "4.5 Melihat Riwayat Approval Saya", level=2)
    
    add_step(doc, 1, "Klik tab \"Riwayat Approval Saya\" pada Dashboard Admin.", has_screenshot=True, screenshot_caption="Tab Riwayat Approval Saya")
    
    add_step(doc, 2, "Anda akan melihat daftar semua permohonan yang pernah Anda proses.", has_screenshot=True, screenshot_caption="Daftar Riwayat Approval")
    
    add_step(doc, 3, "Informasi yang ditampilkan meliputi:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Request ID dan Pemohon")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Tanggal Pengajuan dan Tanggal Approval")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jenis BBM dan Jumlah")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Status (Approved/Rejected/Completed)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kode Voucher (jika approved)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Catatan Admin")
    run.font.size = Pt(11)
    
    add_info_box(doc, "Gunakan fitur pencarian untuk menemukan approval tertentu. Anda dapat mencari berdasarkan Request ID, nama pemohon, atau tanggal.")
    
    add_lampiran_box(doc, "View: resources/views/admin/myapproval.blade.php (di-include dalam admin/index.blade.php)")
    
    # 3.6 Melihat Semua History & Laporan
    add_heading_styled(doc, "4.6 Melihat Semua History & Laporan", level=2)
    
    add_step(doc, 1, "Klik tab \"Semua History & Laporan\" pada Dashboard Admin.", has_screenshot=True, screenshot_caption="Tab Semua History & Laporan")
    
    add_step(doc, 2, "Tab ini memiliki tiga sub-tab:")
    
    p = doc.add_paragraph()
    run = p.add_run("   a. Semua Permohonan: ")
    run.bold = True
    run = p.add_run("Menampilkan semua permohonan dari semua pemohon dengan berbagai status.")
    
    p = doc.add_paragraph()
    run = p.add_run("   b. Laporan Transaksi: ")
    run.bold = True
    run = p.add_run("Menampilkan semua laporan transaksi BBM yang sudah dikirim oleh pemohon.")
    
    p = doc.add_paragraph()
    run = p.add_run("   c. Prognosa Transaksi: ")
    run.bold = True
    run = p.add_run("Menampilkan daftar voucher yang sudah terbit dengan status aktif/expired.")
    
    add_step(doc, 3, "Pada sub-tab \"Semua Permohonan\", Anda dapat melihat detail semua permohonan.", has_screenshot=True, screenshot_caption="Daftar Semua Permohonan")
    
    add_step(doc, 4, "Pada sub-tab \"Laporan Transaksi\", Anda dapat melihat detail laporan pembelian BBM dari pemohon.", has_screenshot=True, screenshot_caption="Daftar Laporan Transaksi")
    
    add_step(doc, 5, "Pada sub-tab \"Prognosa Transaksi\", Anda dapat melihat kode voucher yang sudah terbit dan statusnya.", has_screenshot=True, screenshot_caption="Daftar Prognosa Transaksi")
    
    add_lampiran_box(doc, "View: resources/views/admin/requester_reports.blade.php (di-include dalam admin/index.blade.php)")
    
    # 3.7 Melihat Kode Voucher Kemarin
    add_heading_styled(doc, "4.7 Melihat Kode Voucher Kemarin", level=2)
    
    add_step(doc, 1, "Klik tab \"Kode Voucher Kemarin\" pada Dashboard Admin.", has_screenshot=True, screenshot_caption="Tab Kode Voucher Kemarin")
    
    add_step(doc, 2, "Anda akan melihat daftar kode voucher yang terbit pada hari sebelumnya.", has_screenshot=True, screenshot_caption="Daftar Kode Voucher")
    
    add_step(doc, 3, "Informasi yang ditampilkan meliputi:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Pemohon dan Kendaraan/Alat")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Nominal dan Kode Voucher")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Status (Aktif/Expired)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Valid Until (Berlaku hingga)")
    run.font.size = Pt(11)
    
    add_step(doc, 4, "Anda dapat menggunakan filter untuk melihat voucher berdasarkan status (Semua/Aktif/Expired).", has_screenshot=True, screenshot_caption="Filter Status Voucher")
    
    add_step(doc, 5, "Untuk export voucher ke Excel, klik tombol \"Export CSV\".", has_screenshot=True, screenshot_caption="Tombol Export CSV")
    
    add_info_box(doc, "Voucher berlaku selama 50 jam sejak approval. Jika tidak digunakan dalam waktu tersebut, voucher akan otomatis expired dan permohonan akan dibatalkan.")
    
    add_lampiran_box(doc, "Route: GET /vouchers/list | Name: admin.vouchers.list | Controller: VoucherController@voucherList")
    add_lampiran_box(doc, "Route: POST /vouchers/export | Name: admin.vouchers.export | Controller: VoucherController@voucherExport")
    
    # ============================================
    # HALAMAN 10: MASTER HARGA BBM
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "5. Master Harga BBM & Benchmark Kendaraan", level=1)
    
    doc.add_paragraph(
        "Menu Master Harga BBM menampilkan data harga bahan bakar resmi per region/provinsi "
        "serta informasi benchmark (acuan) nominal pengisian BBM penuh untuk setiap kendaraan. "
        "Data harga diperbarui secara otomatis setiap hari melalui sinkronasi dengan API publik."
    )
    
    add_heading_styled(doc, "5.1 Melihat Master Harga BBM", level=2)
    
    add_step(doc, 1, "Dari sidebar, pilih menu \"Master Harga BBM\" di grup Master Data.", has_screenshot=True, screenshot_caption="Menu Master Harga BBM")
    
    add_step(doc, 2, "Halaman menampilkan tabel Harga BBM per Region yang menunjukkan:", has_screenshot=True, screenshot_caption="Tabel Harga BBM per Region")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Region/Provinsi: ")
    run.bold = True
    run = p.add_run("Lokasi tempat harga berlaku (mis. DKI Jakarta, Jawa Barat, Jawa Tengah).")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jenis BBM: ")
    run.bold = True
    run = p.add_run("Tipe bahan bakar (PERTAMAX, PERTAMINA DEX).")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Harga per Liter: ")
    run.bold = True
    run = p.add_run("Harga terkini dalam Rupiah.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Status Ketersediaan: ")
    run.bold = True
    run = p.add_run("Tersedia / Tidak tersedia.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Tanggal Harga: ")
    run.bold = True
    run = p.add_run("Tanggal harga tersebut berlaku.")
    
    add_heading_styled(doc, "5.2 Sinkronisasi Harga BBM", level=2)
    
    add_step(doc, 1, "Klik tombol \"Sync from API\" di halaman Master Harga BBM.", has_screenshot=True, screenshot_caption="Tombol Sync Harga BBM")
    
    add_step(doc, 2, "Sistem akan mengambil data harga BBM terbaru dari sumber data eksternal.", has_screenshot=True, screenshot_caption="Proses Sinkronisasi")
    
    add_step(doc, 3, "Setelah selesai, tabel akan diperbarui dengan harga terbaru.")
    
    add_info_box(doc, "Sinkronisasi harga BBM juga berjalan otomatis setiap pukul 06:00 pagi melalui sistem penjadwalan (cron job).")
    
    add_lampiran_box(doc, "Route: GET /master-gasoline | Name: master-gasoline.index | POST /master-gasoline/sync | Controller: MasterGasolineController")
    
    add_heading_styled(doc, "5.3 Melihat Benchmark BBM per Kendaraan", level=2)
    
    add_step(doc, 1, "Pada halaman yang sama, gulir ke bagian \"Benchmark BBM per Kendaraan\".", has_screenshot=True, screenshot_caption="Tabel Benchmark Kendaraan")
    
    add_step(doc, 2, "Tabel menunjukkan untuk setiap kendaraan:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kode dan nama kendaraan")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Unit/Region kendaraan beroperasi")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Volume tangki (liter)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kapasitas penumpang")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jenis BBM yang digunakan")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Nominal benchmark (perkiraan biaya isi penuh)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Rentang nominal (benchmark +/- Rp500.000, kelipatan Rp50.000)")
    run.font.size = Pt(11)
    
    add_info_box(doc, "Data benchmark ini digunakan sebagai acuan saat admin menyetujui permohonan BBM. Nominal yang diajukan harus berada dalam rentang benchmark yang wajar.")
    
    add_lampiran_box(doc, "Model: MasterGasoline, VehicleAndToolsConsumers | Service: FuelBenchmarkService | View: resources/views/admin/gasoline/index.blade.php")
    
    # ============================================
    # HALAMAN 11: PER SETUJUAN PENGGANTIAN SUKU CADANG
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "6. Persetujuan Penggantian Suku Cadang (VPR)", level=1)
    
    doc.add_paragraph(
        "Modul ini menangani persetujuan permohonan penggantian suku cadang kendaraan (Vehicle Parts Replacement / VPR) "
        "yang diajukan oleh Dispatcher. Alurnya dimulai dari temuan kerusakan saat pengecekan kendaraan, "
        "pengajuan VPR oleh Dispatcher, kemudian Admin melakukan review, approve/reject, dan penjadwalan servis."
    )
    
    add_heading_styled(doc, "6.1 Mengakses Daftar Permohonan VPR", level=2)
    
    add_step(doc, 1, "Dari sidebar, pilih menu \"Replacement Approval\" di grup Vehicle Checking.", has_screenshot=True, screenshot_caption="Menu Replacement Approval")
    
    add_step(doc, 2, "Halaman menampilkan kartu statistik dan daftar permohonan VPR:", has_screenshot=True, screenshot_caption="Daftar Permohonan VPR")
    
    add_step(doc, 3, "Kartu statistik menampilkan jumlah permohonan per status:")
    
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
    
    vpr_stats = [
        ("Menunggu Approval", "VPR baru yang perlu direview"),
        ("Approved", "VPR yang sudah disetujui, menunggu penjadwalan"),
        ("In Service", "Kendaraan sedang dalam proses servis"),
        ("Selesai", "Servis sudah completed"),
    ]
    
    for row_idx, (stat, keterangan) in enumerate(vpr_stats, 1):
        table.cell(row_idx, 0).text = stat
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_step(doc, 4, "Gunakan filter untuk mencari VPR berdasarkan kendaraan, dispatcher, atau rentang tanggal.", has_screenshot=True, screenshot_caption="Filter VPR")
    
    add_lampiran_box(doc, "Route: GET /admin/replacement-approval | Name: admin.replacement-approval.index | View: resources/views/admin/replacement-approval/index.blade.php")
    
    add_heading_styled(doc, "6.2 Menyetujui atau Menolak VPR", level=2)
    
    add_step(doc, 1, "Klik tombol \"Review\" pada VPR yang berstatus \"Menunggu Approval\".", has_screenshot=True, screenshot_caption="Tombol Review VPR")
    
    add_step(doc, 2, "Halaman detail menampilkan informasi VPR:", has_screenshot=True, screenshot_caption="Detail VPR")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Informasi Permohonan: ")
    run.bold = True
    run = p.add_run("Kode VPR, kendaraan, dispatcher pengaju, tanggal pengajuan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Daftar Item: ")
    run.bold = True
    run = p.add_run("Komponen/suku cadang yang perlu diganti beserta kondisi saat pengecekan.")
    
    add_step(doc, 3, "Untuk menyetujui, pilih driver penanggung jawab dan klik tombol \"Approve\".", has_screenshot=True, screenshot_caption="Tombol Approve VPR")
    
    add_step(doc, 4, "Untuk menolak, isi alasan penolakan pada form dan klik \"Reject\".", has_screenshot=True, screenshot_caption="Tombol Reject VPR")
    
    add_warning_box(doc, "Penolakan VPR membutuhkan alasan yang jelas. Dispatcher akan melihat alasan ini dan dapat mengajukan ulang setelah masalah diperbaiki.")
    
    add_lampiran_box(doc, "Route: POST /admin/replacement-approval/{vprCode}/approve | Name: admin.replacement-approval.approve | Controller: VehicleSparepartRequestRepairController@approve")
    add_lampiran_box(doc, "Route: POST /admin/replacement-approval/{vprCode}/reject | Name: admin.replacement-approval.reject | Controller: VehicleSparepartRequestRepairController@reject")
    
    add_heading_styled(doc, "6.3 Penjadwalan Servis", level=2)
    
    add_step(doc, 1, "Setelah VPR di-approve, halaman detail akan menampilkan form penjadwalan.", has_screenshot=True, screenshot_caption="Form Penjadwalan Servis")
    
    add_step(doc, 2, "Isi informasi berikut:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Driver: ")
    run.bold = True
    run = p.add_run("Pilih driver yang akan membawa kendaraan ke bengkel.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Tanggal Servis: ")
    run.bold = True
    run = p.add_run("Tanggal pelaksanaan servis (minimal hari ini).")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Lokasi Servis: ")
    run.bold = True
    run = p.add_run("Nama bengkel atau lokasi servis.")
    
    add_step(doc, 3, "Klik tombol \"Schedule\" untuk mengonfirmasi jadwal.", has_screenshot=True, screenshot_caption="Tombol Schedule")
    
    add_info_box(doc, "Batas waktu pelaporan servis otomatis dihitung H+7 dari tanggal servis (deadline). Driver harus menyelesaikan servis dan melaporkan biaya sebelum deadline.")
    
    add_lampiran_box(doc, "Route: POST /admin/replacement-approval/{vprCode}/schedule | Name: admin.replacement-approval.schedule | Controller: VehicleSparepartRequestRepairController@schedule")
    
    # ============================================
    # HALAMAN 15: PENJADWALAN SERVIS KENDARAAN
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "7. Penjadwalan Servis Kendaraan", level=1)
    
    doc.add_paragraph(
        "Menu Penjadwalan Servis (Service Scheduling) menampilkan kalender servis kendaraan yang sudah dijadwalkan. "
        "Admin dapat memantau semua jadwal servis dalam tampilan kalender bulanan."
    )
    
    add_heading_styled(doc, "7.1 Mengakses Kalender Servis", level=2)
    
    add_step(doc, 1, "Dari sidebar, pilih menu \"Service Scheduling\" di grup Vehicle Checking.", has_screenshot=True, screenshot_caption="Menu Service Scheduling")
    
    add_step(doc, 2, "Halaman menampilkan kalender bulanan dengan kartu-kartu servis yang dikelompokkan berdasarkan status:", has_screenshot=True, screenshot_caption="Kalender Service Scheduling")
    
    table = doc.add_table(rows=5, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    headers = ["Kategori", "Warna Border"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")
    
    sched_cats = [
        ("Overdue (Terlambat)", "Merah — sudah lewat dari jadwal"),
        ("Hari Ini", "Kuning — jadwal hari ini"),
        ("Terjadwal", "Biru — jadwal mendatang"),
        ("Selesai", "Hijau — sudah completed"),
    ]
    
    for row_idx, (kategori, warna) in enumerate(sched_cats, 1):
        table.cell(row_idx, 0).text = kategori
        table.cell(row_idx, 1).text = warna
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_step(doc, 3, "Setiap kartu servis menampilkan: kendaraan (nopol), driver, lokasi servis, dan batas waktu (deadline).", has_screenshot=True, screenshot_caption="Detail Kartu Servis")
    
    add_step(doc, 4, "Klik tombol \"Detail\" pada kartu untuk melihat informasi lengkap VPR terkait.", has_screenshot=True, screenshot_caption="Tombol Detail Servis")
    
    add_step(doc, 5, "Gunakan navigasi bulan sebelumnya/berikutnya untuk melihat jadwal di bulan lain.")
    
    add_info_box(doc, "Pastikan untuk memantau kartu Overdue (merah) dan Hari Ini (kuning) secara rutin agar tidak ada servis yang terlewat.")
    
    add_lampiran_box(doc, "Route: GET /admin/service-scheduling | Name: admin.service-scheduling.index | View: resources/views/admin/service-scheduling/index.blade.php | Controller: VehicleSparepartRequestRepairController")
    
    # ============================================
    # HALAMAN 16: MONITORING TRANSAKSI SERVIS
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "8. Monitoring Transaksi Servis", level=1)
    
    doc.add_paragraph(
        "Menu Monitoring Transaksi Servis menampilkan semua transaksi servis kendaraan yang sedang berlangsung "
        "maupun yang sudah selesai. Admin dapat memantau biaya, status, dan detail pelaksanaan servis."
    )
    
    add_heading_styled(doc, "8.1 Melihat Daftar Transaksi Servis", level=2)
    
    add_step(doc, 1, "Dari sidebar, pilih menu \"Transaction Monitoring\" di grup Vehicle Checking.", has_screenshot=True, screenshot_caption="Menu Transaction Monitoring")
    
    add_step(doc, 2, "Halaman menampilkan kartu statistik:", has_screenshot=True, screenshot_caption="Dashboard Transaction Monitoring")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Overdue: ")
    run.bold = True
    run = p.add_run("Jumlah servis yang melewati deadline.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Dalam Proses: ")
    run.bold = True
    run = p.add_run("Servis yang sedang berjalan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Selesai (Bulan Ini): ")
    run.bold = True
    run = p.add_run("Servis yang sudah selesai bulan ini.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Total Biaya (Bulan Ini): ")
    run.bold = True
    run = p.add_run("Akumulasi biaya servis bulan ini.")
    
    add_step(doc, 3, "Tabel di bawah menampilkan daftar transaksi dengan informasi:", has_screenshot=True, screenshot_caption="Daftar Transaksi Servis")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kode Transaksi (VPRT)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Tanggal Servis dan Kendaraan")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Driver dan Lokasi Servis")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Total Biaya dan Deadline")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Status VPR dan Status Deadline")
    run.font.size = Pt(11)
    
    add_step(doc, 4, "Baris yang overdue akan ditandai dengan warna merah.", has_screenshot=True, screenshot_caption="Baris Overdue")
    
    add_step(doc, 5, "Gunakan filter kendaraan, status, dan tanggal untuk mempersempit pencarian.")
    
    add_info_box(doc, "Klik tombol \"Detail\" pada baris transaksi untuk melihat informasi lengkap termasuk rincian biaya dan foto bukti servis.")
    
    add_lampiran_box(doc, "Route: GET /admin/transaction-monitoring | Name: admin.transaction-monitoring.index | View: resources/views/admin/transaction-monitoring/index.blade.php | Controller: VehicleSparepartRequestRepairController")
    
    # ============================================
    # HALAMAN 17: LUNCH MANAGEMENT
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "9. Manajemen Makan Siang (Lunch Management)", level=1)
    
    doc.add_paragraph(
        "Modul Lunch Management mengelola seluruh kebutuhan katering makan siang karyawan di setiap site. "
        "Mulai dari data master pekerja, prediksi kuota (forecast), distribusi porsi untuk vendor, "
        "hingga absensi kehadiran melalui QR Code. Admin memiliki akses penuh ke seluruh sub-modul lunch."
    )
    
    add_heading_styled(doc, "9.1 Dashboard Lunch", level=2)
    
    add_step(doc, 1, "Dari sidebar, buka menu \"Lunch Management\" lalu pilih \"Dashboard Lunch\".", has_screenshot=True, screenshot_caption="Menu Dashboard Lunch")
    
    add_step(doc, 2, "Dashboard menampilkan alur 4 langkah:", has_screenshot=True, screenshot_caption="Alur Lunch Management")
    
    table = doc.add_table(rows=5, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    headers = ["Langkah", "Keterangan"]
    for i, header in enumerate(headers):
        cell = table.cell(0, i)
        cell.text = header
        for paragraph in cell.paragraphs:
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in paragraph.runs:
                run.bold = True
        set_cell_shading(cell, "E8EAF6")
    
    lunch_steps = [
        ("1. Master Pekerja", "Kelola data pegawai, alias nama, dan preferensi makan"),
        ("2. Forecast Besok", "Generate prediksi porsi makan siang H+1"),
        ("3. Distribusi Hari Ini", "Kunci angka pasti untuk vendor katering"),
        ("4. Kehadiran & Export", "Monitoring absensi QR dan export laporan vendor"),
    ]
    
    for row_idx, (langkah, keterangan) in enumerate(lunch_steps, 1):
        table.cell(row_idx, 0).text = langkah
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_heading_styled(doc, "9.2 Mengelola Master Pekerja (Employee Defaults)", level=2)
    
    add_step(doc, 1, "Pilih menu \"Employee Default\" di sub-menu Lunch Management.", has_screenshot=True, screenshot_caption="Menu Employee Default")
    
    add_step(doc, 2, "Halaman menampilkan daftar seluruh pekerja yang terdaftar sebagai penerima makan siang.", has_screenshot=True, screenshot_caption="Daftar Employee Default")
    
    add_step(doc, 3, "Anda dapat menambah pekerja baru, mengedit data (nama, jabatan, divisi, site default), atau menghapus pekerja.", has_screenshot=True, screenshot_caption="Form Edit Employee Default")
    
    add_step(doc, 4, "Fitur Export/Import Excel tersedia untuk memudahkan pengelolaan data massal.")
    
    add_lampiran_box(doc, "Route: /lunch/employee-defaults/* | Controller: EmployeeDefaultController | View: resources/views/lunch/employee-defaults/index.blade.php")
    
    add_heading_styled(doc, "9.3 Generate Forecast H+1", level=2)
    
    add_step(doc, 1, "Pilih menu \"Forecast\" di sub-menu Lunch Management.", has_screenshot=True, screenshot_caption="Menu Forecast")
    
    add_step(doc, 2, "Klik tombol \"Generate Forecast\" untuk membuat prediksi porsi makan siang untuk hari esok.", has_screenshot=True, screenshot_caption="Tombol Generate Forecast")
    
    add_step(doc, 3, "Sistem akan menghitung prediksi berdasarkan 4 prioritas:")
    
    p = doc.add_paragraph()
    run = p.add_run("   1) Manual Override: ")
    run.bold = True
    run = p.add_run("Admin dapat mengubah langsung prediksi per pekerja.")
    
    p = doc.add_paragraph()
    run = p.add_run("   2) Tomorrow Confirmation: ")
    run.bold = True
    run = p.add_run("Jawaban karyawan saat scan QR hari ini (\"Besok makan dimana?\").")
    
    p = doc.add_paragraph()
    run = p.add_run("   3) Historical Pattern: ")
    run.bold = True
    run = p.add_run("Pola kebiasaan makan berdasarkan riwayat transaksi.")
    
    p = doc.add_paragraph()
    run = p.add_run("   4) Default Site: ")
    run.bold = True
    run = p.add_run("Lokasi site tempat pekerja terdaftar.")
    
    add_info_box(doc, "Forecast juga berjalan otomatis setiap pukul 15:00 melalui sistem penjadwalan.")
    
    add_lampiran_box(doc, "Route: /lunch/forecasts/* | Controller: LunchForecastController | View: resources/views/lunch/forecasts/index.blade.php")
    
    add_heading_styled(doc, "9.4 Distribusi & Lock untuk Vendor", level=2)
    
    add_step(doc, 1, "Pilih menu \"Distribution\" di sub-menu Lunch Management.", has_screenshot=True, screenshot_caption="Menu Distribution")
    
    add_step(doc, 2, "Halaman menampilkan data distribusi porsi per site.", has_screenshot=True, screenshot_caption="Halaman Distribusi")
    
    add_step(doc, 3, "Klik \"Generate Distribution\" untuk membuat snapshot distribusi dari data forecast.", has_screenshot=True, screenshot_caption="Generate Distribution")
    
    add_step(doc, 4, "Setelah yakin dengan angka distribusi, klik \"Lock\" untuk mengunci dan mengirimkan data final ke vendor.")
    
    add_warning_box(doc, "Setelah distribusi di-lock, data tidak dapat diubah. Pastikan angka sudah benar sebelum mengunci. Vendor akan menggunakan data ini untuk menyiapkan porsi makan siang.")
    
    add_lampiran_box(doc, "Route: /lunch/distributions/* | Controller: LunchDistributionController | View: resources/views/lunch/distributions/index.blade.php")
    
    add_heading_styled(doc, "9.5 Export Laporan untuk Vendor", level=2)
    
    add_step(doc, 1, "Pilih menu \"Export Vendor\" di sub-menu Lunch Management.", has_screenshot=True, screenshot_caption="Menu Export Vendor")
    
    add_step(doc, 2, "Pilih tanggal dan site, lalu klik \"Export\" untuk mengunduh laporan Excel.", has_screenshot=True, screenshot_caption="Form Export Vendor")
    
    add_step(doc, 3, "File Excel berisi 3 sheet: Ringkasan per Site, Detail Karyawan, dan Daftar Tamu.")
    
    add_lampiran_box(doc, "Route: /lunch/export | Controller: LunchDistributionController | View: resources/views/lunch/distributions/export-form.blade.php")
    
    add_heading_styled(doc, "9.6 Operasional Harian Lunch", level=2)
    
    doc.add_paragraph(
        "Berikut adalah ringkasan operasional harian yang perlu dilakukan Admin terkait modul Lunch:"
    )
    
    doc.add_paragraph("Generate Forecast H+1 (otomatis pukul 15:00 atau manual via tombol Generate).", style='List Bullet')
    doc.add_paragraph("Tinjau hasil forecast dan lakukan Manual Override jika diperlukan.", style='List Bullet')
    doc.add_paragraph("Lock distribusi untuk memberikan angka pasti ke vendor.", style='List Bullet')
    doc.add_paragraph("Pantau transaksi QR attendance untuk memastikan absensi berjalan lancar.", style='List Bullet')
    doc.add_paragraph("Cek reservasi tamu (Guest Reservation) yang memerlukan konfirmasi H-2.", style='List Bullet')
    doc.add_paragraph("Export laporan untuk vendor jika diminta.", style='List Bullet')
    
    add_info_box(doc, "Karyawan melakukan absensi dengan scan QR Code di lokasi kantin. Data kehadiran ini menjadi ground truth untuk evaluasi akurasi forecast.")
    
    add_lampiran_box(doc, "Dashboard: /lunch | View: resources/views/lunch/dashboard.blade.php | Controller: LunchDashboardController")
    add_lampiran_box(doc, "Seluruh route lunch: Route::middleware(['auth', 'role:admin,superadmin'])->prefix('lunch')")
    
    # ============================================
    # HALAMAN 22: AI ASSISTANT GAGA
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "10. Asisten AI — Gaga (General Affairs Geodipa Assistant)", level=1)
    
    doc.add_paragraph(
        "Gaga adalah asisten virtual cerdas berbasis AI yang terintegrasi di dalam sistem SIAGA. "
        "Admin dapat menggunakan Gaga untuk bertanya tentang data operasional secara real-time "
        "menggunakan bahasa alami. Gaga didukung oleh model Llama 3.3 70B melalui infrastruktur Groq."
    )
    
    add_heading_styled(doc, "10.1 Mengakses Gaga", level=2)
    
    add_step(doc, 1, "Dari sidebar, pilih menu \"Gaga\" di grup Laporan & Analisis.", has_screenshot=True, screenshot_caption="Menu Gaga AI Assistant")
    
    add_step(doc, 2, "Halaman chat akan terbuka dengan sapaan personal.", has_screenshot=True, screenshot_caption="Halaman Chat Gaga")
    
    add_step(doc, 3, "Terdapat tombol \"Sesi Baru\" untuk memulai percakapan baru, dan panel riwayat sesi di sebelah kiri.")
    
    add_lampiran_box(doc, "Route: GET /ai-assistant | Name: ai-assistant.index | View: resources/views/superadmin/ai-assistant/index.blade.php")
    
    add_heading_styled(doc, "10.2 Bertanya pada Gaga", level=2)
    
    add_step(doc, 1, "Ketik pertanyaan Anda pada kolom chat di bagian bawah halaman.", has_screenshot=True, screenshot_caption="Kolom Chat Gaga")
    
    add_step(doc, 2, "Gaga dapat menjawab pertanyaan seputar data berikut:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - BBM: ")
    run.bold = True
    run = p.add_run("Jumlah permohonan, status approval, nominal, dan statistik BBM.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - SPD: ")
    run.bold = True
    run = p.add_run("Surat perjalanan dinas, driver, tujuan, anggaran, dan status.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - LPJ: ")
    run.bold = True
    run = p.add_run("Laporan pertanggungjawaban, total pengeluaran, status keterlambatan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Lunch: ")
    run.bold = True
    run = p.add_run("Porsi makan siang, jumlah karyawan hadir, data forecast.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kendaraan: ")
    run.bold = True
    run = p.add_run("Daftar kendaraan, suku cadang, jadwal pengecekan.")
    
    add_step(doc, 3, "Tekan Enter atau klik tombol kirim untuk mengirim pertanyaan.", has_screenshot=True, screenshot_caption="Mengirim Pertanyaan")
    
    add_step(doc, 4, "Gaga akan merespons dalam format Markdown yang rapi (heading, tabel, bullet point).", has_screenshot=True, screenshot_caption="Respons Gaga")
    
    add_info_box(doc, "Anda juga dapat mengklik chip saran yang tersedia di bawah kolom chat untuk mencoba contoh pertanyaan umum.")
    
    add_lampiran_box(doc, "Service: app/Services/GeminiAssistantService.php (Groq) | Model: Llama 3.3 70B | Data Mart: 9 modul (BBM, SPD, LPJ, Lunch, Vehicle, dll)")
    
    add_heading_styled(doc, "10.3 Contoh Pertanyaan", level=2)
    
    doc.add_paragraph("Berikut adalah contoh pertanyaan yang dapat Anda tanyakan ke Gaga:")
    
    doc.add_paragraph("\"Berapa total permohonan BBM bulan ini?\"", style='List Bullet')
    doc.add_paragraph("\"Tampilkan 5 kendaraan dengan konsumsi BBM tertinggi.\"", style='List Bullet')
    doc.add_paragraph("\"Berapa surat SPD yang masih pending?\"", style='List Bullet')
    doc.add_paragraph("\"Siapa driver yang paling sering melakukan perjalanan dinas?\"", style='List Bullet')
    doc.add_paragraph("\"Ada LPJ yang telat bulan ini?\"", style='List Bullet')
    doc.add_paragraph("\"Berapa porsi makan siang untuk besok?\"", style='List Bullet')
    doc.add_paragraph("\"Kendaraan apa yang jadwal servisnya overdue?\"", style='List Bullet')
    
    # ============================================
    # HALAMAN PENUTUP
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "11. Penutup", level=1)
    
    doc.add_paragraph(
        "Buku panduan ini disusun sebagai referensi bagi pengguna role Admin (General Affairs) dalam menggunakan "
        "Sistem Administrasi General Affairs (Siaga) di PT Geo Dipa Energi (Persero). "
        "Diharapkan dengan adanya panduan ini, admin dapat memahami hak akses dan batasannya, "
        "serta menjalankan seluruh prosedur operasional — mulai dari approval BBM, "
        "persetujuan suku cadang, penjadwalan servis, manajemen makan siang, "
        "hingga penggunaan asisten AI Gaga — dengan baik dan benar."
    )
    
    doc.add_paragraph("")
    
    doc.add_paragraph("Apabila terdapat pertanyaan, saran, atau kendala terkait penggunaan sistem, "
                      "silakan hubungi Tim IT Developer melalui:")
    
    doc.add_paragraph("Email: it.developer@geodipa.co.id", style='List Bullet')
    doc.add_paragraph("Telepon: Ext. XXXX", style='List Bullet')
    doc.add_paragraph("Meeting: Tim IT Developer Unit Patuha", style='List Bullet')
    
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
    output_path = "/Users/farisiftikharalfarisi/Desktop/Laravel_Project/Geodipa/BBM_Project/Buku_Panduan_Admin_Siaga_v1.0.0.docx"
    doc.save(output_path)
    print(f"Dokumen berhasil disimpan di: {output_path}")
    return output_path

if __name__ == "__main__":
    create_document()
