<div class="card border-warning">
    <div class="card-header bg-warning text-white">
        <h6 class="mb-0">
            <i class="bi bi-hourglass-split"></i> Slot {{ $slotNum }} -
            @if($request->status === 'pending_vehicle_approval')
                Menunggu Approval Kendaraan
            @else
                Pending
            @endif
        </h6>
    </div>
    <div class="card-body">
        <p class="mb-1"><strong>Nomor:</strong> <code>{{ $request->request_number }}</code></p>

        {{-- Kendaraan/Alat Info --}}
        <p class="mb-1"><strong>Kendaraan/Alat:</strong><br>
            <small>{{ $request->vehicle->consumerial_code ?? 'N/A' }} - {{ $request->vehicle->consumerial_name ?? 'N/A' }}</small>
        </p>

        {{-- Tipe dengan indicator approval status --}}
        <p class="mb-1">
            <strong>Tipe:</strong>
            <span class="badge bg-secondary">{{ $request->vehicle->consumerial_type ?? 'N/A' }}</span>

            @if($request->vehicle && !$request->vehicle->is_approved)
                <span class="badge bg-danger" title="Kendaraan belum disetujui Superadmin">
                    <i class="bi bi-exclamation-circle"></i> Belum Approved
                </span>
            @endif
        </p>

        <p class="mb-1"><strong>BBM:</strong> {{ $request->gasoline_type }}</p>
        <p class="mb-1"><strong>Nominal:</strong> Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</p>

        {{-- Catatan jika ada --}}
        @if($request->requester_notes)
        <p class="mb-1 small text-muted">
            <strong>Catatan:</strong> {{ Str::limit($request->requester_notes, 50) }}
        </p>
        @endif

        <p class="mb-1 text-muted small">
            <i class="bi bi-clock"></i> {{ $request->request_date->diffForHumans() }}
        </p>

        <hr>

        {{-- Status Badge dengan penjelasan --}}
        @if($request->status === 'pending_vehicle_approval')
            <div class="alert alert-warning mb-2 py-2 px-2 small">
                <i class="bi bi-info-circle"></i>
                <strong>Kendaraan rental sedang menunggu persetujuan Superadmin.</strong>
                <br>Setelah kendaraan disetujui, permohonan akan diproses oleh Admin.
            </div>
            <span class="badge bg-warning w-100 mb-2">
                <i class="bi bi-truck"></i> Menunggu Approval Kendaraan
            </span>
        @else
            <span class="badge bg-warning w-100 mb-2">
                <i class="bi bi-clock-history"></i> Menunggu Approval Admin
            </span>
        @endif

        {{-- ✅ Tombol Cancel --}}
        @can('permission', 'bbm.request')
        <button type="button"
                class="btn btn-danger btn-sm w-100 mt-2"
                data-bs-toggle="modal"
                data-bs-target="#cancelModal{{ $slotNum }}">
            <i class="bi bi-x-circle"></i> Batalkan Permohonan
        </button>
        @endcan
    </div>
</div>

{{-- ✅ Modal Cancel Confirmation --}}
<div class="modal fade" id="cancelModal{{ $slotNum }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Batalkan Permohonan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('requester.cancel-request', $request->id) }}"
                  method="POST"
                  id="cancelForm{{ $slotNum }}">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        <strong>Perhatian!</strong> Permohonan yang dibatalkan tidak dapat dikembalikan.
                    </div>

                    <div class="mb-3">
                        <p class="mb-2"><strong>Detail Permohonan:</strong></p>
                        <ul class="small text-muted mb-0">
                            <li>Nomor: <code>{{ $request->request_number }}</code></li>
                            <li>Kendaraan: {{ $request->vehicle->consumerial_code ?? 'N/A' }}</li>
                            <li>Nominal: Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="cancellation_reason{{ $slotNum }}" class="form-label">
                            Alasan Pembatalan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('cancellation_reason') is-invalid @enderror"
                                  id="cancellation_reason{{ $slotNum }}"
                                  name="cancellation_reason"
                                  rows="4"
                                  required
                                  placeholder="Contoh: Salah input nominal, Salah pilih kendaraan, dll">{{ old('cancellation_reason') }}</textarea>
                        <small class="text-muted">Minimal 10 karakter</small>
                        @error('cancellation_reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger" id="confirmCancelBtn{{ $slotNum }}">
                        <i class="bi bi-check-circle"></i> Ya, Batalkan Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelForm{{ $slotNum }} = document.getElementById('cancelForm{{ $slotNum }}');
    const cancelBtn{{ $slotNum }} = document.getElementById('confirmCancelBtn{{ $slotNum }}');
    const reasonTextarea{{ $slotNum }} = document.getElementById('cancellation_reason{{ $slotNum }}');

    // ✅ Validasi minimal 10 karakter
    if (cancelForm{{ $slotNum }}) {
        cancelForm{{ $slotNum }}.addEventListener('submit', function(e) {
            const reason = reasonTextarea{{ $slotNum }}.value.trim();

            if (reason.length < 10) {
                e.preventDefault();
                alert('Alasan pembatalan minimal 10 karakter');
                reasonTextarea{{ $slotNum }}.focus();
                return false;
            }

            // Konfirmasi final
            if (!confirm('Apakah Anda yakin ingin membatalkan permohonan ini?')) {
                e.preventDefault();
                return false;
            }

            // Disable button untuk prevent double submit
            cancelBtn{{ $slotNum }}.disabled = true;
            cancelBtn{{ $slotNum }}.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Membatalkan...';
        });
    }

    // ✅ Real-time character counter
    if (reasonTextarea{{ $slotNum }}) {
        reasonTextarea{{ $slotNum }}.addEventListener('input', function() {
            const length = this.value.trim().length;
            const minLength = 10;

            if (length < minLength) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>
