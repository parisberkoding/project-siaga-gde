<?php

namespace App\Models;

use App\Support\AiChartEmbed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(AiChatSession::class, 'chat_session_id');
    }

    /**
     * Identifier: apakah pesan ini punya kode embedding grafik atau tidak.
     */
    public function hasChartEmbedding(): bool
    {
        return AiChartEmbed::has((string) $this->content);
    }

    /**
     * Spec grafik dari embedding (jika ada), atau fallback metadata lama.
     *
     * @return array<string, mixed>|null
     */
    public function chartSpec(): ?array
    {
        if ($this->hasChartEmbedding()) {
            return AiChartEmbed::extract((string) $this->content);
        }

        return $this->metadata['chart'] ?? null;
    }

    /**
     * Konten tampilan tanpa marker embedding grafik.
     */
    public function displayContent(): string
    {
        return AiChartEmbed::strip((string) $this->content);
    }
}
