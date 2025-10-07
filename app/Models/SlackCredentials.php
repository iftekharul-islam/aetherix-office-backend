<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlackCredentials extends Model
{
    protected $fillable = [
        'model_id',
        'model_type',
        'slack_webhook_url',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];
}
