<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak sinkronisasi SSO.
 *
 * Menyimpan RAW CLAIMS dari IdP. Ini disengaja: atribut yang dikirim SSO tidak
 * dapat direproduksi setelah kejadian, sehingga tanpa menyimpannya mentah-mentah
 * pertanyaan seperti "waktu itu SSO bilang jabatannya apa?" tidak akan pernah
 * bisa dijawab. Sangat berguna saat user memprotes hak aksesnya.
 */
class SsoSyncLog extends Model
{
    protected $table = 'sso_sync_logs';

    /** Keputusan yang mungkin dicatat. */
    public const DECISION_MAPPED = 'mapped';       // dapat role dari mapping
    public const DECISION_UNMAPPED = 'unmapped';   // belum ada mapping -> nol permission
    public const DECISION_REJECTED = 'rejected';   // ditolak (nonaktif / tanpa unit)
    public const DECISION_CONFLICT = 'conflict';   // lokal nonaktif tapi SSO aktif
    public const DECISION_SYNCED = 'synced';       // sekedar sinkron atribut

    /**
     * Perubahan role hasil mapping TERLALU MENAIKKAN hak akses, sehingga
     * ditahan menunggu persetujuan Superadmin Pusat. Role lama tetap berlaku.
     */
    public const DECISION_PENDING_REVIEW = 'pending_review';

    protected $fillable = [
        'provider',
        'subject',
        'user_id',
        'raw_claims',
        'decision',
        'mapped_role_id',
        'note',
    ];

    protected $casts = [
        'raw_claims' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat satu kejadian sinkronisasi.
     *
     * Sengaja dibuat tahan gagal: kegagalan menulis audit TIDAK boleh
     * menggagalkan proses login user.
     */
    public static function record(
        string $decision,
        ?string $subject = null,
        ?int $userId = null,
        ?array $rawClaims = null,
        ?int $mappedRoleId = null,
        ?string $note = null,
        string $provider = 'geodipa'
    ): ?self {
        try {
            return static::create([
                'provider' => $provider,
                'subject' => $subject,
                'user_id' => $userId,
                'raw_claims' => $rawClaims,
                'decision' => $decision,
                'mapped_role_id' => $mappedRoleId,
                'note' => $note,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
