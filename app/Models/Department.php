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
];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }
}
