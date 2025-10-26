<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'event_type',
        'webhook_id',
        'issue_key',
        'headers',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
    ];
}
