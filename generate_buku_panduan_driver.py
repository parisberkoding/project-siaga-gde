#!/usr/bin/env python3
"""
Script to generate Buku Panduan Driver Role - DOCX format
"""

from docx import Document
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.section import WD_ORIENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
import os

def set_cell_shading(cell, color):
    """Set cell background color"""
    shading_elm = OxmlElement('w:shd')
    shading_elm.set(qn('w:fill'), color)
    shading_elm.set(qn('w:val'), 'clear')
    cell._tc.get_or_add_tcPr().append(shading_elm)

def add_page_break(doc):
    """Add a page break"""
    doc.add_page_break()

def add_heading_styled(doc, text, level=1):
    """Add styled heading"""
    heading = doc.add_heading(text, level=level)
    for run in heading.runs:
        run.font.color.rgb = RGBColor(1, 41, 112)  # Dark blue
    return heading

def add_lampiran_box(doc, text):
    """Add a lampiran (attachment/reference) box"""
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    cell = table.cell(0, 0)
    cell.text = ""
    
    # Set cell shading
    set_cell_shading(cell, "F0F4FF")
    
    # Add border
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
    
    # Add content
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
    """Add a screenshot placeholder box"""
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
    """Add a step with optional screenshot"""
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
    """Add an info box"""
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

def create_document():
    doc = Document()
    
    # Set default font
    style = doc.styles['Normal']
    font = style.font
    font.name = 'Calibri'
    font.size = Pt(11)
    
    # ============================================
    # HALAMAN 1: COVER
    # ============================================
    for _ in range(6):
        doc.add_paragraph("")
    
    # Title
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
    
    # Subtitle
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Role: Driver")
    run.font.size = Pt(18)
    run.font.color.rgb = RGBColor(65, 84, 241)
    run.italic = True
    
    doc.add_paragraph("")
    doc.add_paragraph("")
    
    # Company
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("PT Geo Dipa Energi Persero")
    run.bold = True
    run.font.size = Pt(16)
    run.font.color.rgb = RGBColor(100, 100, 100)
    
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Unit Patuha")
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(100, 100, 100)
    
    doc.add_paragraph("")
    doc.add_paragraph("")
    doc.add_paragraph("")
    
    # Version
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Versi 1.0.0")
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(100, 100, 100)
    
    doc.add_paragraph("")
    doc.add_paragraph("")
    doc.add_paragraph("")
    
    # Author
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Penyusun:")
    run.font.size = Pt(12)
    run.font.color.rgb = RGBColor(100, 100, 100)
    
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("Tim IT Developer Unit Patuha")
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
    
    # Table for approval
    table = doc.add_table(rows=6, cols=3)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    # Header
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
    
    # Data rows
    approvals = [
        ("Disusun Oleh", "Tim IT Developer", ""),
        ("Diperiksa Oleh", "General Affair Manager", ""),
        ("Disetujui Oleh", "Unit Head Patuha", ""),
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
    
    # Set column widths
    for row in table.rows:
        row.cells[0].width = Cm(5)
        row.cells[1].width = Cm(5)
        row.cells[2].width = Cm(6)
    
    doc.add_paragraph("")
    doc.add_paragraph("")
    
    # Catatan
    p = doc.add_paragraph()
    run = p.add_run("Catatan:")
    run.bold = True
    run.font.size = Pt(11)
    
    doc.add_paragraph("Dokumen ini merupakan panduan resmi penggunaan Sistem Administrasi General Affairs untuk role Driver di PT Geo Dipa Energi Persero Unit Patuha.", style='List Bullet')
    doc.add_paragraph("Setiap pengguna diwajibkan memahami dan mengikuti prosedur yang tercantum dalam dokumen ini.", style='List Bullet')
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
        ("1.", "Pendahuluan", "4"),
        ("", "1.1 Latar Belakang", "4"),
        ("", "1.2 Tujuan", "4"),
        ("", "1.3 Ruang Lingkup", "4"),
        ("2.", "Prosedur Login & Logout", "5"),
        ("", "2.1 Akses Sistem", "5"),
        ("", "2.2 Proses Login", "5"),
        ("", "2.3 Proses Logout", "6"),
        ("3.", "Prosedur Permohonan dan Pelaporan BBM", "7"),
        ("", "3.1 Membuat Permohonan BBM Baru", "7"),
        ("", "3.2 Melihat Status Permohonan", "9"),
        ("", "3.3 Mencetak Voucher BBM", "10"),
        ("", "3.4 Melaporkan Pembelian BBM", "11"),
        ("", "3.5 Melihat Riwayat Permohonan", "13"),
        ("4.", "Prosedur SPD & Pelaporan SPD", "14"),
        ("", "4.1 Membuat Surat Tugas (SPD)", "14"),
        ("", "4.2 Melihat Daftar Surat Tugas", "16"),
        ("", "4.3 Membuat Laporan Pertanggungjawaban (LPJ)", "17"),
        ("", "4.4 Pengajuan Perpanjangan SPD", "19"),
        ("5.", "Prosedur Acknowledge Dispatcher", "20"),
        ("", "5.1 Melihat Hasil Pengecekan", "20"),
        ("", "5.2 Melakukan Acknowledge", "21"),
        ("", "5.3 Melihat Jadwal Service", "22"),
        ("6.", "Penutup", "23"),
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
        
        # Add tab and page number
        run = p.add_run(f"\t{page}")
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
        "Surat Tugas Perjalanan Dinas (SPD), dan pengelolaan kendaraan di PT Geo Dipa Energi Persero Unit Patuha. "
        "Sistem ini dirancang untuk memudahkan proses bisnis yang sebelumnya dilakukan secara manual menjadi "
        "lebih efisien dan terintegrasi."
    )
    
    add_heading_styled(doc, "1.2 Tujuan", level=2)
    doc.add_paragraph("Buku panduan ini disusun dengan tujuan:")
    doc.add_paragraph("Memberikan panduan lengkap bagi pengguna role Driver dalam menggunakan sistem Siaga.", style='List Bullet')
    doc.add_paragraph("Memastikan proses bisnis berjalan sesuai prosedur yang ditetapkan.", style='List Bullet')
    doc.add_paragraph("Mengurangi kesalahan penggunaan sistem.", style='List Bullet')
    doc.add_paragraph("Menjadi referensi bagi pengguna baru dalam memahami alur kerja sistem.", style='List Bullet')
    
    add_heading_styled(doc, "1.3 Ruang Lingkup", level=2)
    doc.add_paragraph("Buku panduan ini mencakup prosedur untuk role Driver, meliputi:")
    doc.add_paragraph("Login dan Logout dari sistem", style='List Bullet')
    doc.add_paragraph("Pengajuan Permohonan BBM", style='List Bullet')
    doc.add_paragraph("Input SPD (Surat Perintah Dinas)", style='List Bullet')
    doc.add_paragraph("Acknowledge hasil pengecekan kendaraan oleh Dispatcher", style='List Bullet')
    
    # ============================================
    # HALAMAN 5: PROCEDURE LOGIN & LOGOUT
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "2. Prosedur Login & Logout", level=1)
    
    add_heading_styled(doc, "2.1 Akses Sistem", level=2)
    
    add_step(doc, 1, "Buka web browser (Google Chrome, Mozilla Firefox, atau browser modern lainnya).")
    
    add_step(doc, 2, "Akses link sistem yang sudah dibagikan oleh administrator.", has_screenshot=True, screenshot_caption="Halaman Login Sistem Siaga")
    
    add_lampiran_box(doc, "Route: GET /login | View: resources/views/authentication/login.blade.php")
    
    add_heading_styled(doc, "2.2 Proses Login", level=2)
    
    add_step(doc, 1, "Pada halaman login, masukkan Username yang sudah diberikan oleh administrator pada kolom \"Masukkan Username\".", has_screenshot=True, screenshot_caption="Form Input Username")
    
    add_step(doc, 2, "Masukkan Password yang sudah diberikan pada kolom \"Masukkan Password\".", has_screenshot=True, screenshot_caption="Form Input Password")
    
    add_step(doc, 3, "Centang checkbox \"Tampilkan Password\" jika ingin melihat password yang dimasukkan (opsional).")
    
    add_step(doc, 4, "Klik tombol \"Login\" untuk masuk ke sistem.", has_screenshot=True, screenshot_caption="Tombol Login")
    
    add_info_box(doc, "Jika login berhasil, Anda akan diarahkan ke halaman dashboard sesuai role. Jika gagal, akan muncul pesan error \"Username atau password salah\".")
    
    add_lampiran_box(doc, "Route: POST /login-process | Controller: AuthentiactionController@authenticate | View: resources/views/authentication/login.blade.php")
    
    add_heading_styled(doc, "2.3 Proses Logout", level=2)
    
    add_step(doc, 1, "Klik icon profil atau nama Anda di pojok kanan atas halaman.", has_screenshot=True, screenshot_caption="Icon Profil Pengguna")
    
    add_step(doc, 2, "Pilih opsi \"Logout\" dari dropdown menu.", has_screenshot=True, screenshot_caption="Menu Logout")
    
    add_step(doc, 3, "Anda akan diarahkan kembali ke halaman login.", has_screenshot=True, screenshot_caption="Halaman Login Setelah Logout")
    
    add_info_box(doc, "Selalu lakukan logout setelah selesai menggunakan sistem untuk menjaga keamanan akun Anda.")
    
    add_lampiran_box(doc, "Route: GET/POST /logout | Controller: AuthentiactionController@logout")
    
    # ============================================
    # HALAMAN 6-N: PROCEDURE PERMOHONAN BBM
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "3. Prosedur Permohonan dan Pelaporan BBM", level=1)
    
    doc.add_paragraph(
        "Bagian ini menjelaskan langkah-langkah pengajuan permohonan BBM, melihat status permohonan, "
        "mencetak voucher, dan melaporkan pembelian BBM."
    )
    
    # 3.1 Membuat Permohonan BBM Baru
    add_heading_styled(doc, "3.1 Membuat Permohonan BBM Baru", level=2)
    
    add_step(doc, 1, "Setelah login, klik menu \"BBM / Permohonan\" di sidebar sebelah kiri.", has_screenshot=True, screenshot_caption="Menu BBM/Permohonan di Sidebar")
    
    add_step(doc, 2, "Klik submenu \"Permohonan Baru\".", has_screenshot=True, screenshot_caption="Submenu Permohonan Baru")
    
    add_step(doc, 3, "Anda akan diarahkan ke halaman Dashboard Permohonan BBM. Pastikan slot permohonan masih tersedia (maksimal 5 slot aktif).", has_screenshot=True, screenshot_caption="Dashboard Permohonan BBM")
    
    add_lampiran_box(doc, "Route: GET /requester/view-request-bbm | Name: requester-index | View: resources/views/requester/view_request_bbm.blade.php")
    
    add_step(doc, 4, "Pada form \"Form Permohonan BBM Baru\", isi data berikut:")
    
    # Sub-steps for form
    p = doc.add_paragraph()
    run = p.add_run("   a. Kendaraan/Alat: ")
    run.bold = True
    run = p.add_run("Ketik kode/plat/nama kendaraan pada kolom pencarian. Pilih kendaraan dari daftar yang muncul.")
    
    p = doc.add_paragraph()
    run = p.add_run("   b. Jenis BBM: ")
    run.bold = True
    run = p.add_run("Pilih jenis BBM yang diinginkan (Pertamax, Pertamina Dex, dll). Jenis BBM akan otomatis terisi berdasarkan master kendaraan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   c. Nominal (Rp): ")
    run.bold = True
    run = p.add_run("Pilih nominal pengisian BBM dari dropdown. Rentang nominal ditentukan berdasarkan kapasitas tangki kendaraan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   d. Catatan Permohonan (Opsional): ")
    run.bold = True
    run = p.add_run("Tambahkan catatan tambahan jika diperlukan.")
    
    add_step(doc, 5, "Klik tombol \"Ajukan Permohonan Baru\" untuk mengirim permohonan.", has_screenshot=True, screenshot_caption="Tombol Ajukan Permohonan")
    
    add_info_box(doc, "Setelah permohonan diajukan, status akan menjadi \"Pending\" dan menunggu persetujuan dari Admin General Affairs. Anda akan memberitahukan jika permohonan sudah disetujui.")
    
    add_lampiran_box(doc, "Route: POST /requester/create-request-bbm | Name: requester-create | Controller: RequestManagementController@requester_create")
    
    # 3.2 Melihat Status Permohonan
    add_heading_styled(doc, "3.2 Melihat Status Permohonan", level=2)
    
    add_step(doc, 1, "Pada halaman Dashboard Permohonan BBM, scroll ke bawah ke bagian \"Slot Permohonan Aktif\".", has_screenshot=True, screenshot_caption="Slot Permohonan Aktif")
    
    add_step(doc, 2, "Setiap slot menampilkan status permohonan:")
    
    # Status table
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
    
    statuses = [
        ("Pending", "Permohonan menunggu persetujuan Admin"),
        ("Approved", "Permohonan sudah disetujui, dapat cetak voucher"),
        ("Completed", "Laporan pembelian sudah dikirim"),
        ("Rejected", "Permohonan ditolak oleh Admin"),
    ]
    
    for row_idx, (status, keterangan) in enumerate(statuses, 1):
        table.cell(row_idx, 0).text = status
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_step(doc, 3, "Klik tombol \"Lihat Riwayat\" untuk melihat semua riwayat permohonan.", has_screenshot=True, screenshot_caption="Tombol Lihat Riwayat")
    
    add_lampiran_box(doc, "Route: GET /requester/bbm-history | Name: requester-history | View: resources/views/requester/history_request.blade.php")
    
    # 3.3 Mencetak Voucher BBM
    add_heading_styled(doc, "3.3 Mencetak Voucher BBM", level=2)
    
    add_step(doc, 1, "Pastikan permohonan Anda berstatus \"Approved\".", has_screenshot=True, screenshot_caption="Slot dengan Status Approved")
    
    add_step(doc, 2, "Pada slot yang sudah disetujui, akan muncul kode voucher BBM berwarna ungu.", has_screenshot=True, screenshot_caption="Kode Voucher BBM")
    
    add_step(doc, 3, "Klik tombol \"Cetak Voucher BBM\".", has_screenshot=True, screenshot_caption="Tombol Cetak Voucher")
    
    add_step(doc, 4, "Pada modal cetak, pilih printer dan klik \"Print\" untuk mencetak voucher.", has_screenshot=True, screenshot_caption="Modal Cetak Voucher")
    
    add_info_box(doc, "Voucher BBM digunakan sebagai bukti pengisian BBM di SPBU. Simpan voucher dengan baik.")
    
    add_lampiran_box(doc, "View: resources/views/requester/partials/voucher-print-modal.blade.php")
    
    # 3.4 Melaporkan Pembelian BBM
    add_heading_styled(doc, "3.4 Melaporkan Pembelian BBM", level=2)
    
    add_step(doc, 1, "Pada slot yang sudah disetujui, klik tombol \"Lapor Pembelian\".", has_screenshot=True, screenshot_caption="Tombol Lapor Pembelian")
    
    add_step(doc, 2, "Anda akan diarahkan ke halaman Pelaporan BBM. Isi data berikut:", has_screenshot=True, screenshot_caption="Halaman Pelaporan BBM")
    
    add_lampiran_box(doc, "Route: GET /pelaporan | Name: pelaporan.index | View: resources/views/pelaporan/index.blade.php")
    
    p = doc.add_paragraph()
    run = p.add_run("   a. Foto Struk BBM: ")
    run.bold = True
    run = p.add_run("Upload foto struk pembelian dari SPBU (format gambar, max 5MB).")
    
    p = doc.add_paragraph()
    run = p.add_run("   b. Foto Odometer: ")
    run.bold = True
    run = p.add_run("Upload foto odometer kendaraan setelah pengisian (khusus kendaraan).")
    
    p = doc.add_paragraph()
    run = p.add_run("   c. Volume BBM (Liter): ")
    run.bold = True
    run = p.add_run("Masukkan volume pengisian BBM sesuai struk.")
    
    p = doc.add_paragraph()
    run = p.add_run("   d. KM Akhir: ")
    run.bold = True
    run = p.add_run("Masukkan odometer terakhir kendaraan.")
    
    add_step(doc, 3, "Klik tombol \"Submit\" untuk mengirim laporan.", has_screenshot=True, screenshot_caption="Tombol Submit Laporan")
    
    add_info_box(doc, "Sistem akan menganalisis struk BBM menggunakan fitur OCR/AI untuk memvalidasi data. Pastikan foto struk jelas dan tidak buram.")
    
    add_lampiran_box(doc, "Route: POST /pelaporan/analyze-struk | Name: pelaporan.analyze-struk | Controller: ReportingController@analyzeStruk")
    
    # 3.5 Melihat Riwayat Permohonan
    add_heading_styled(doc, "3.5 Melihat Riwayat Permohonan", level=2)
    
    add_step(doc, 1, "Klik menu \"BBM / Permohonan\" di sidebar, lalu pilih \"Riwayat Permohonan\".", has_screenshot=True, screenshot_caption="Menu Riwayat Permohonan")
    
    add_step(doc, 2, "Anda akan melihat daftar semua permohonan BBM yang pernah diajukan.", has_screenshot=True, screenshot_caption="Daftar Riwayat Permohonan")
    
    add_step(doc, 3, "Klik tombol \"Detail\" pada permohonan yang ingin dilihat untuk melihat informasi lengkap.", has_screenshot=True, screenshot_caption="Detail Permohonan")
    
    add_lampiran_box(doc, "Route: GET /requester/request/{id} | Name: requester-show | View: resources/views/requester/detail_request.blade.php")
    
    # ============================================
    # HALAMAN N-NX: PROCEDURE SPD
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "4. Prosedur SPD & Pelaporan SPD", level=1)
    
    doc.add_paragraph(
        "Bagian ini menjelaskan langkah-langkah pengurusan Surat Tugas Perjalanan Dinas (SPD), "
        "termasuk pembuatan surat, pengajuan perpanjangan, dan pembuatan Laporan Pertanggungjawaban (LPJ)."
    )
    
    # 4.1 Membuat Surat Tugas (SPD)
    add_heading_styled(doc, "4.1 Membuat Surat Tugas (SPD)", level=2)
    
    add_step(doc, 1, "Klik menu \"SPD Management\" di sidebar sebelah kiri.", has_screenshot=True, screenshot_caption="Menu SPD Management")
    
    add_step(doc, 2, "Klik submenu \"Buat Surat Tugas\".", has_screenshot=True, screenshot_caption="Submenu Buat Surat Tugas")
    
    add_step(doc, 3, "Anda akan diarahkan ke form pembuatan Surat Tugas Perjalanan Dinas.", has_screenshot=True, screenshot_caption="Form Buat Surat Tugas")
    
    add_lampiran_box(doc, "Route: GET /requester/surat_requester/create | Name: requester.surat.create | View: resources/views/requester/spd/surat/create.blade.php")
    
    add_step(doc, 4, "Isi form dengan data berikut:")
    
    # Pemberi Tugas
    p = doc.add_paragraph()
    run = p.add_run("   A. Pemberi Tugas:")
    run.bold = True
    
    p = doc.add_paragraph()
    run = p.add_run("      - Level Pemberi Tugas: ")
    run.bold = True
    run = p.add_run("Pilih \"Manager\" atau \"Assistant Manager\".")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Ambil dari Master Pekerja (opsional): ")
    run.bold = True
    run = p.add_run("Pilih dari daftar pekerja jika ada, atau input manual.")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Nama Pemberi Tugas: ")
    run.bold = True
    run = p.add_run("Masukkan nama lengkap pemberi tugas.")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Jabatan: ")
    run.bold = True
    run = p.add_run("Masukkan jabatan pemberi tugas.")
    
    # Informasi Perjalanan
    p = doc.add_paragraph()
    run = p.add_run("   B. Informasi Perjalanan:")
    run.bold = True
    
    p = doc.add_paragraph()
    run = p.add_run("      - Tempat Tujuan: ")
    run.bold = True
    run = p.add_run("Masukkan alamat tujuan perjalanan dinas.")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Melayani Bidang: ")
    run.bold = True
    run = p.add_run("Masukkan bidang pekerjaan (contoh: Operasional, Teknik).")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Jumlah Pemakai: ")
    run.bold = True
    run = p.add_run("Masukkan jumlah orang yang menggunakan kendaraan (maksimal 7 orang).")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Kendaraan: ")
    run.bold = True
    run = p.add_run("Pilih kendaraan yang akan digunakan dari dropdown.")
    
    # Jadwal
    p = doc.add_paragraph()
    run = p.add_run("   C. Jadwal Perjalanan:")
    run.bold = True
    
    p = doc.add_paragraph()
    run = p.add_run("      - Tanggal Berangkat: ")
    run.bold = True
    run = p.add_run("Pilih tanggal keberangkatan.")
    
    p = doc.add_paragraph()
    run = p.add_run("      - Tanggal Kembali: ")
    run.bold = True
    run = p.add_run("Pilih tanggal kepulangan. Lama perjalanan akan dihitung otomatis.")
    
    # Daftar Nama
    p = doc.add_paragraph()
    run = p.add_run("   D. Daftar Nama Pemakai:")
    run.bold = True
    
    p = doc.add_paragraph()
    run = p.add_run("      - Masukkan nama lengkap pemakai kendaraan sesuai jumlah yang diisi.")
    
    add_step(doc, 5, "Klik tombol \"Ajukan Surat Tugas\" di sidebar kanan untuk mengirim surat.", has_screenshot=True, screenshot_caption="Tombol Ajukan Surat Tugas")
    
    add_info_box(doc, "Surat akan diproses oleh Superadmin. Budget per hari akan ditentukan saat persetujuan. Setelah disetujui, deadline LPJ adalah 3 hari setelah tanggal kembali.")
    
    add_lampiran_box(doc, "Route: POST /requester/surat_requester/store | Name: requester.surat.store | Controller: SpdController@storeSurat")
    
    # 4.2 Melihat Daftar Surat Tugas
    add_heading_styled(doc, "4.2 Melihat Daftar Surat Tugas", level=2)
    
    add_step(doc, 1, "Klik submenu \"Daftar Surat Tugas\" di bawah menu SPD Management.", has_screenshot=True, screenshot_caption="Menu Daftar Surat Tugas")
    
    add_step(doc, 2, "Anda akan melihat daftar semua surat tugas yang pernah dibuat.", has_screenshot=True, screenshot_caption="Daftar Surat Tugas")
    
    add_step(doc, 3, "Status surat tugas dapat berupa:")
    
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
    
    statuses = [
        ("Menunggu", "Surat sedang diproses oleh Superadmin"),
        ("Disetujui", "Surat sudah disetujui, dapat cetak"),
        ("Selesai", "LPJ sudah disetujui"),
        ("Ditolak", "Surat ditolak oleh Superadmin"),
    ]
    
    for row_idx, (status, keterangan) in enumerate(statuses, 1):
        table.cell(row_idx, 0).text = status
        table.cell(row_idx, 1).text = keterangan
        for col in range(2):
            for paragraph in table.cell(row_idx, col).paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(10)
    
    add_lampiran_box(doc, "Route: GET /requester/surat_requester/main-surat | Name: requester.surat.index | View: resources/views/requester/spd/surat/index.blade.php")
    
    # 4.3 Membuat LPJ
    add_heading_styled(doc, "4.3 Membuat Laporan Pertanggungjawaban (LPJ)", level=2)
    
    add_step(doc, 1, "Pada daftar surat tugas, pastikan surat berstatus \"Disetujui\".", has_screenshot=True, screenshot_caption="Surat Tugas Disetujui")
    
    add_step(doc, 2, "Klik tombol \"Buat LPJ\" pada surat yang ingin dibuatkan laporan.", has_screenshot=True, screenshot_caption="Tombol Buat LPJ")
    
    add_step(doc, 3, "Anda akan diarahkan ke form pembuatan LPJ.", has_screenshot=True, screenshot_caption="Form Buat LPJ")
    
    add_lampiran_box(doc, "Route: GET /requester/lpj/create/{surat} | Name: requester.lpj.create | View: resources/views/requester/spd/lpj/create.blade.php")
    
    add_step(doc, 4, "Isi form LPJ dengan data yang diperlukan:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kebutuhan BBM (Liter): ")
    run.bold = True
    run = p.add_run("Masukkan jumlah liter BBM yang dibutuhkan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Upload Struk: ")
    run.bold = True
    run = p.add_run("Upload foto struk pembelian BBM.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Items: ")
    run.bold = True
    run = p.add_run("Tambahkan item pengeluaran lainnya jika ada.")
    
    add_step(doc, 5, "Klik tombol \"Simpan\" untuk mengirim LPJ.", has_screenshot=True, screenshot_caption="Tombol Simpan LPJ")
    
    add_info_box(doc, "LPJ akan dikirim ke Superadmin untuk validasi. Pastikan semua data dan bukti pengeluaran sudah benar sebelum mengirim.")
    
    add_lampiran_box(doc, "Route: POST /requester/lpj/store | Name: requester.lpj.store | Controller: SpdController@storeLaporan")
    
    # 4.4 Pengajuan Perpanjangan SPD
    add_heading_styled(doc, "4.4 Pengajuan Perpanjangan SPD", level=2)
    
    add_step(doc, 1, "Pada daftar surat tugas, klik tombol \"Ajukan Perpanjangan\" pada surat yang sudah disetujui.", has_screenshot=True, screenshot_caption="Tombol Ajukan Perpanjangan")
    
    add_step(doc, 2, "Isi form perpanjangan dengan data baru.", has_screenshot=True, screenshot_caption="Form Perpanjangan SPD")
    
    add_lampiran_box(doc, "Route: GET /requester/perpanjangan/create | Name: requester.perpanjangan.create | View: resources/views/requester/spd/perpanjangan/create.blade.php")
    
    add_step(doc, 3, "Klik tombol \"Ajukan Perpanjangan\" untuk mengirim.", has_screenshot=True, screenshot_caption="Tombol Submit Perpanjangan")
    
    add_info_box(doc, "Perpanjangan akan diproses oleh Superadmin. Pastikan surat asli sudah disetujui sebelum mengajukan perpanjangan.")
    
    add_lampiran_box(doc, "Route: POST /requester/perpanjangan/store | Name: requester.perpanjangan.store | Controller: SpdController@storePerpanjangan")
    
    # ============================================
    # HALAMAN NX-NY: PROCEDURE ACKNOWLEDGE DISPATCHER
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "5. Prosedur Acknowledge Dispatcher", level=1)
    
    doc.add_paragraph(
        "Bagian ini menjelaskan langkah-langkah melihat hasil pengecekan kendaraan oleh Dispatcher "
        "dan melakukan acknowledge (konfirmasi) terhadap hasil pengecekan tersebut."
    )
    
    # 5.1 Melihat Hasil Pengecekan
    add_heading_styled(doc, "5.1 Melihat Hasil Pengecekan", level=2)
    
    add_step(doc, 1, "Klik menu \"Pengecekan & Service\" di sidebar sebelah kiri.", has_screenshot=True, screenshot_caption="Menu Pengecekan & Service")
    
    add_step(doc, 2, "Klik submenu \"Hasil Pengecekan\".", has_screenshot=True, screenshot_caption="Submenu Hasil Pengecekan")
    
    add_step(doc, 3, "Anda akan melihat daftar hasil pengecekan kendaraan dengan dua tab:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Belum Ack: ")
    run.bold = True
    run = p.add_run("Pengecekan yang belum di-acknowledge (ditandai dengan badge merah).")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Sudah Ack: ")
    run.bold = True
    run = p.add_run("Pengecekan yang sudah di-acknowledge.")
    
    add_step(doc, 4, "Gunakan filter untuk mencari pengecekan tertentu:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Kendaraan: ")
    run.bold = True
    run = p.add_run("Filter berdasarkan kendaraan.")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Jenis: ")
    run.bold = True
    run = p.add_run("Filter berdasarkan jenis pengecekan (Rutin/Body).")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Dari: ")
    run.bold = True
    run = p.add_run("Filter berdasarkan tanggal.")
    
    add_lampiran_box(doc, "Route: GET /requester/vehicle-check/acknowledgement | Name: requester.checking-acknowledgement.index | View: resources/views/requester/checking-acknowledgement/index.blade.php")
    
    # 5.2 Melakukan Acknowledge
    add_heading_styled(doc, "5.2 Melakukan Acknowledge", level=2)
    
    add_step(doc, 1, "Klik pada kartu pengecekan yang berstatus \"Belum Ack\" untuk melihat detail.", has_screenshot=True, screenshot_caption="Kartu Pengecekan Belum Ack")
    
    add_step(doc, 2, "Anda akan melihat detail pengecekan, meliputi:")
    
    p = doc.add_paragraph()
    run = p.add_run("   - Informasi pengecekan (kode, jenis, kendaraan, pemeriksa, tanggal)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Ringkasan hasil (OK/NG untuk rutin, atau Baik/Cukup/Kurang/Tidak untuk body check)")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Daftar item hasil pemeriksaan dengan status masing-masing")
    run.font.size = Pt(11)
    
    p = doc.add_paragraph()
    run = p.add_run("   - Foto kerusakan (jika ada)")
    run.font.size = Pt(11)
    
    add_step(doc, 3, "Setelah memahami hasil pengecekan, klik tombol \"Saya Menghadiri dan Mengetahui\".", has_screenshot=True, screenshot_caption="Tombol Acknowledge")
    
    add_step(doc, 4, "Konfirmasi acknowledge dengan mengklik tombol tersebut. Status akan berubah menjadi \"Sudah Di-acknowledge\".", has_screenshot=True, screenshot_caption="Status Sudah Di-acknowledge")
    
    add_info_box(doc, "Acknowledge merupakan konfirmasi bahwa Anda telah mengetahui hasil pengecekan kendaraan. Jika ada item yang perlu penggantian, Dispatcher akan memproses permohonan penggantian sparepart.")
    
    add_lampiran_box(doc, "Route: PUT /requester/vehicle-check/acknowledgement/{checkingDetailCode}/ack | Name: requester.checking-acknowledgement.acknowledge | Controller: VehicleRepairmentDriverController@acknowledge")
    
    add_lampiran_box(doc, "View Detail: resources/views/requester/checking-acknowledgement/show.blade.php")
    
    # 5.3 Melihat Jadwal Service
    add_heading_styled(doc, "5.3 Melihat Jadwal Service", level=2)
    
    add_step(doc, 1, "Klik submenu \"Jadwal Service\" di bawah menu \"Pengecekan & Service\".", has_screenshot=True, screenshot_caption="Menu Jadwal Service")
    
    add_step(doc, 2, "Anda akan melihat daftar jadwal service kendaraan yang ditugaskan kepada Anda.", has_screenshot=True, screenshot_caption="Daftar Jadwal Service")
    
    add_step(doc, 3, "Jika ada jadwal service yang perlu ditindaklanjuti, klik pada jadwal tersebut untuk melihat detail.", has_screenshot=True, screenshot_caption="Detail Jadwal Service")
    
    add_lampiran_box(doc, "Route: GET /requester/vehicle-check/replacement-schedule | Name: requester.replacement-schedule.index | View: resources/views/requester/replacement-schedule/index.blade.php")
    
    # ============================================
    # HALAMAN PENUTUP
    # ============================================
    add_page_break(doc)
    
    add_heading_styled(doc, "6. Penutup", level=1)
    
    doc.add_paragraph(
        "Buku panduan ini disusun sebagai referensi bagi pengguna role Driver dalam menggunakan "
        "Sistem Administrasi General Affairs (Siaga) di PT Geo Dipa Energi Persero Unit Patuha. "
        "Diharapkan dengan adanya panduan ini, pengguna dapat memahami dan menjalankan prosedur "
        "yang telah ditetapkan dengan baik."
    )
    
    doc.add_paragraph("")
    
    doc.add_paragraph("Apabila terdapat pertanyaan, saran, atau kendala terkait penggunaan sistem, "
                      "silakan hubungi Tim IT Developer Unit Patuha melalui:")
    
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
    run = p.add_run("Tim IT Developer Unit Patuha")
    run.font.size = Pt(12)
    run.font.color.rgb = RGBColor(100, 100, 100)
    
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run("PT Geo Dipa Energi Persero")
    run.font.size = Pt(12)
    run.font.color.rgb = RGBColor(100, 100, 100)
    
    # Save document
    output_path = "/Users/farisiftikharalfarisi/Desktop/Laravel_Project/Geodipa/BBM_Project/Buku_Panduan_Driver_Siaga_v1.0.0.docx"
    doc.save(output_path)
    print(f"Dokumen berhasil disimpan di: {output_path}")
    return output_path

if __name__ == "__main__":
    create_document()
