<?php

namespace App\Http\Controllers;

use App\Models\LpjPenggantian;
use App\Models\SuratSpdBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SpdPencairanController — Lampiran Pemberian Dana Perjalanan Dinas.
 *
 * Mengelola baris pencairan dana perjalanan dinas (tabel `surat_spd_budgets`)
 * yang hanya boleh diisi/diubah oleh Divisi General Affairs (permission
 * `lpj.pencairan`).
 *
 * Setiap baris menyimpan: tanggal, nominal, bentuk pencairan (Tunai/Transfer),
 * nomor referensi perbankan, rekening tujuan, dan catatan.
 *
 * Efek samping yang dijaga: setiap perubahan menyinkronkan `jumlah_diberikan`
 * pada LPJ (= total pencairan) lalu menghitung ulang pengembalian/kekurangan,
 * sehingga Lembar Lampiran Pemberian Dana, Lembar Ringkasan Penggunaan, dan
 * Lembar Persetujuan selalu konsisten satu sama lain.
 */
class SpdPencairanController extends Controller
{
    /**
     * Aturan validasi baris pencairan.
     */
    private function rules(): array
    {
        return [
            'tanggal'          => 'required|date',
            'nominal'          => 'required|numeric|min:0',
            'bentuk_pencairan' => 'required|in:Tunai,Transfer',
            'bank_reference'   => 'nullable|string|max:255|required_if:bentuk_pencairan,Transfer',
            'rek_tujuan'       => 'nullable|string|max:255|required_if:bentuk_pencairan,Transfer',
            'catatan'          => 'nullable|string|max:255',
        ];
    }

    private function messages(): array
    {
        return [
            'tanggal.required'             => 'Tanggal pencairan wajib diisi.',
            'nominal.required'             => 'Nominal pencairan wajib diisi.',
            'bentuk_pencairan.required'    => 'Bentuk pencairan (Tunai/Transfer) wajib dipilih.',
            'bank_reference.required_if'   => 'Nomor referensi perbankan wajib diisi untuk pencairan transfer.',
            'rek_tujuan.required_if'       => 'Rekening tujuan wajib diisi untuk pencairan transfer.',
        ];
    }

    /**
     * Pastikan baris pencairan memang milik surat SPD dari LPJ ini.
     */
    private function assertOwned(LpjPenggantian $laporan, SuratSpdBudget $pencairan): void
    {
        if ((int) $pencairan->surat_spd_id !== (int) $laporan->surat_spd_id) {
            abort(404, 'Baris pencairan tidak ditemukan pada LPJ ini.');
        }
    }

    /**
     * Sinkronkan total dana diberikan pada LPJ dengan akumulasi pencairan.
     */
    private function syncJumlahDiberikan(LpjPenggantian $laporan): void
    {
        $total = (float) SuratSpdBudget::where('surat_spd_id', $laporan->surat_spd_id)->sum('nominal');

        $laporan->update(['jumlah_diberikan' => $total]);
        $laporan->calculateKeuangan();
    }

    /**
     * Tambah satu baris pencairan.
     */
    public function store(Request $request, LpjPenggantian $laporan)
    {
        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($data, $laporan) {
            $urutan = (int) SuratSpdBudget::where('surat_spd_id', $laporan->surat_spd_id)->max('urutan');

            SuratSpdBudget::create([
                'surat_spd_id'       => $laporan->surat_spd_id,
                'tanggal'            => $data['tanggal'],
                'nominal'            => $data['nominal'],
                'bentuk_pencairan'   => $data['bentuk_pencairan'],
                'bank_reference'     => $data['bentuk_pencairan'] === 'Transfer' ? ($data['bank_reference'] ?? null) : null,
                'rek_tujuan'         => $data['bentuk_pencairan'] === 'Transfer' ? ($data['rek_tujuan'] ?? null) : null,
                'catatan'            => $data['catatan'] ?? null,
                'urutan'             => $urutan + 1,
            ]);

            $this->syncJumlahDiberikan($laporan);
        });

        return redirect()->back()->with('success', 'Baris pencairan dana berhasil ditambahkan.');
    }

    /**
     * Ubah satu baris pencairan.
     */
    public function update(Request $request, LpjPenggantian $laporan, SuratSpdBudget $pencairan)
    {
        $this->assertOwned($laporan, $pencairan);

        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($data, $laporan, $pencairan) {
            $pencairan->update([
                'tanggal'          => $data['tanggal'],
                'nominal'          => $data['nominal'],
                'bentuk_pencairan' => $data['bentuk_pencairan'],
                'bank_reference'   => $data['bentuk_pencairan'] === 'Transfer' ? ($data['bank_reference'] ?? null) : null,
                'rek_tujuan'       => $data['bentuk_pencairan'] === 'Transfer' ? ($data['rek_tujuan'] ?? null) : null,
                'catatan'          => $data['catatan'] ?? null,
            ]);

            $this->syncJumlahDiberikan($laporan);
        });

        return redirect()->back()->with('success', 'Baris pencairan dana berhasil diperbarui.');
    }

    /**
     * Hapus satu baris pencairan.
     */
    public function destroy(LpjPenggantian $laporan, SuratSpdBudget $pencairan)
    {
        $this->assertOwned($laporan, $pencairan);

        DB::transaction(function () use ($laporan, $pencairan) {
            $pencairan->delete();
            $this->syncJumlahDiberikan($laporan);
        });

        return redirect()->back()->with('success', 'Baris pencairan dana berhasil dihapus.');
    }

    /**
     * Halaman kelola (CRUD) pencairan untuk sebuah LPJ — khusus Divisi GA.
     */
    public function index(LpjPenggantian $laporan)
    {
        $laporan->load(['suratSpd.vehicle', 'suratSpd.pencairans']);

        $pencairans = $laporan->suratSpd?->pencairans ?? collect();

        return view('superadmin.spd.lpj.pencairan', [
            'laporan'    => $laporan,
            'surat'      => $laporan->suratSpd,
            'pencairans' => $pencairans,
            'total'      => (float) $pencairans->sum('nominal'),
        ]);
    }
}
