<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InstagramAccount extends Model
{
    protected $fillable = [
        'name',
        'username',
        'instagram_user_id',
        'page_id',
        'access_token',
        'profile_picture_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_instagram_accounts');
    }
}
