<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'head_id',
    ];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }
}
