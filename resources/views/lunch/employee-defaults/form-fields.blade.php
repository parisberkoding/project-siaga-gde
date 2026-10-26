<div class="mb-3">
    <label class="form-label">Kode Karyawan</label>
    <input type="text" name="employee_code" class="form-control" maxlength="50"
           placeholder="Opsional, mis. GDE-0001">
</div>
<div class="mb-3">
    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
    <input type="text" name="employee_name" class="form-control" maxlength="150" required>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Divisi</label>
        <input type="text" name="division" class="form-control" maxlength="100">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Jabatan</label>
        <input type="text" name="position" class="form-control" maxlength="100">
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Unit <span class="text-danger">*</span></label>
        <select name="unit_id" class="form-select unit-select">
            <option value="">— Pilih unit —</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Menentukan site apa saja yang boleh dipilih karyawan.</div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Default Site</label>
        <select name="default_site_id" class="form-select site-select">
            <option value="">— Tidak ada —</option>
            @foreach($sites as $site)
                <option value="{{ $site->id }}" data-unit="{{ $site->unit_id }}">
                    {{ $site->name }} ({{ $site->code }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Fallback forecast bila belum ada pola historis / konfirmasi.</div>
    </div>
</div>
<div class="mb-1">
    <label class="form-label">Alias (dipisah koma)</label>
    <input type="text" name="aliases" class="form-control" placeholder="mis. dedi, pak dedi, dedi ga">
    <div class="form-text">Dipakai Identity Resolution saat karyawan mengetik nama di QR Attendance.</div>
</div>
