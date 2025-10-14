<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineAttendance extends Model
{
    protected $fillable = [
        'uid',
        'attendance_id',
        'user_id',
        'type',
        'datetime',
        'note',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // send_slack($model);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
