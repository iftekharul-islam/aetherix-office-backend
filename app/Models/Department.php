<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'division_id',
        'name',
        'code',
        'description',
        'head_id',
        'office_start_time',
        'expected_duty_hours',
        'on_time_threshold_minutes',
        'delay_threshold_minutes',
        'extreme_delay_threshold_minutes',
    ];

    protected $casts = [
        'expected_duty_hours' => 'decimal:2',
        'on_time_threshold_minutes' => 'integer',
        'delay_threshold_minutes' => 'integer',
        'extreme_delay_threshold_minutes' => 'integer',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}