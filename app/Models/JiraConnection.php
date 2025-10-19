<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class JiraConnection extends Model
{
    protected $fillable = [
        'user_id',
        'cloud_id',
        'site_url',
        'site_name',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the connection
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the decrypted access token
     */
    public function getDecryptedAccessTokenAttribute(): string
    {
        return Crypt::decryptString($this->access_token);
    }

    /**
     * Get the decrypted refresh token
     */
    public function getDecryptedRefreshTokenAttribute(): string
    {
        return Crypt::decryptString($this->refresh_token);
    }

    /**
     * Set the access token (encrypted)
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    /**
     * Set the refresh token (encrypted)
     */
    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = Crypt::encryptString($value);
    }

    /**
     * Check if the access token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the access token needs refresh (expires in less than 5 minutes)
     */
    public function needsRefresh(): bool
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }
}
