<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredUser extends Model
{
    protected $fillable = [
        'jira_connection_id',
        'jira_account_id',
        'display_name',
        'avatar_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the Jira connection this monitored user belongs to
     */
    public function jiraConnection(): BelongsTo
    {
        return $this->belongsTo(JiraConnection::class);
    }
}
