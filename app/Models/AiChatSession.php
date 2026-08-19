<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatSession extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'session_key',
    ];

    /**
     * Token unik untuk URL sesi (mis. ?session=<session_key>) — ID numerik
     * tidak pernah tampil di URL. Dibangkitkan otomatis saat membuat sesi.
     */
    protected static function booted(): void
    {
        static::creating(function (AiChatSession $session) {
            if (empty($session->session_key)) {
                $session->session_key = \Illuminate\Support\Str::random(32);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(AiChatMessage::class, 'chat_session_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(AiChatMessage::class, 'chat_session_id')->latestOfMany();
    }
}
