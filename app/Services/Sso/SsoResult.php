<?php

namespace App\Services\Sso;

use App\Models\User;

/**
 * Hasil satu siklus autentikasi SSO.
 *
 * Memisahkan "boleh masuk" dari "punya hak akses":
 *   - STATUS_AUTHENTICATED  -> boleh masuk, role sudah ditetapkan
 *   - STATUS_NEEDS_REVIEW   -> boleh masuk, TETAPI nol permission dan masuk
 *                              antrian review Pusat (mapping belum ada)
 *   - STATUS_REJECTED       -> tidak boleh masuk
 *
 * Pemisahan ini yang membuat kebijakan JIT bisa "akun langsung aktif" tanpa
 * memberi akses tak sengaja.
 */
class SsoResult
{
    public const STATUS_AUTHENTICATED = 'authenticated';
    public const STATUS_NEEDS_REVIEW = 'needs_review';
    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        public readonly string $status,
        public readonly ?User $user = null,
        public readonly ?string $message = null,
        public readonly array $context = [],
    ) {
    }

    public static function authenticated(User $user, array $context = []): self
    {
        return new self(self::STATUS_AUTHENTICATED, $user, null, $context);
    }

    public static function needsReview(User $user, string $message, array $context = []): self
    {
        return new self(self::STATUS_NEEDS_REVIEW, $user, $message, $context);
    }

    public static function rejected(string $message, array $context = []): self
    {
        return new self(self::STATUS_REJECTED, null, $message, $context);
    }

    public function canLogin(): bool
    {
        return $this->status !== self::STATUS_REJECTED && $this->user !== null;
    }

    public function needsReviewFlag(): bool
    {
        return $this->status === self::STATUS_NEEDS_REVIEW;
    }
}
