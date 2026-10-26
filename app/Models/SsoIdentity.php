<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identitas user pada penyedia SSO.
 *
 * Dipisah dari `users` supaya:
 *   - `subject` (ID unik dari IdP) tetap stabil walau atribut lain berubah,
 *   - satu user bisa punya lebih dari satu identitas provider,
 *   - `claims` mentah tersimpan untuk keperluan penelusuran.
 */
class SsoIdentity extends Model
{
    protected $table = 'sso_identities';

    protected $fillable = [
        'user_id',
        'provider',
        'subject',
        'claims',
        'last_synced_at',
    ];

    protected $casts = [
        'claims' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cari identitas berdasarkan provider + subject.
     * Inilah kunci pencocokan utama saat callback SSO — bukan nama atau email,
     * karena keduanya bisa berubah.
     */
    public static function findBySubject(string $subject, string $provider = 'geodipa'): ?self
    {
        return static::where('provider', $provider)
            ->where('subject', $subject)
            ->first();
    }
}
