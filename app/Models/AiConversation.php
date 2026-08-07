<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = [
        'session_id', 'persona', 'locale', 'message_count', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'message_count' => 'integer',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
    }

    public function personaLabel(): string
    {
        return match ($this->persona) {
            'tourist'     => 'นักท่องเที่ยว',
            'student'     => 'นักเรียน/นักศึกษา',
            'worker'      => 'แรงงาน',
            'investor'    => 'นักลงทุน',
            'hotel'       => 'โรงแรม/ที่พัก',
            'employer'    => 'นายจ้าง',
            'thai_citizen'=> 'คนไทย',
            'officer'     => 'เจ้าหน้าที่',
            'retiree'     => 'ผู้เกษียณ',
            'family'      => 'ครอบครัว',
            default       => 'ทั่วไป',
        };
    }
}
