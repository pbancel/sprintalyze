<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JiraInstance extends Model
{
    protected $fillable = [
        'user_id',
        'jira_connection_id',
        'cloud_id',
        'site_name',
        'site_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this Jira instance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Jira connection this instance belongs to
     */
    public function jiraConnection(): BelongsTo
    {
        return $this->belongsTo(JiraConnection::class);
    }
}
