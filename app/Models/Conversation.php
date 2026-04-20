<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'instagram_account_id',
        'ig_conversation_id',
        'participant_ig_id',
        'participant_username',
        'participant_name',
        'participant_profile_pic',
        'last_message_at',
        'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany('sent_at');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->participant_name ?? $this->participant_username ?? $this->participant_ig_id;
    }
}
