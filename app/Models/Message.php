<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'meta_message_id',
        'from_ig_id',
        'to_ig_id',
        'message_text',
        'message_type',
        'media_url',
        'is_outgoing',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'is_outgoing' => 'boolean',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
