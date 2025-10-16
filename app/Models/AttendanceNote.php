<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'note',
    ];

   protected $casts = [
    'date' => 'datetime:Y-m-d', 
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}