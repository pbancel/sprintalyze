<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredIssue extends Model
{
    protected $fillable = [
        'jira_connection_id',
        'issue_key',
        'issue_id',
        'summary',
        'issue_type',
        'status',
        'assignee_id',
        'assignee_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the Jira connection this monitored issue belongs to
     */
    public function jiraConnection(): BelongsTo
    {
        return $this->belongsTo(JiraConnection::class);
    }
}
