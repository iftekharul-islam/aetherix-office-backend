<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'machine_id',
        'employee_id',
        'name',
        'email',
        'department_id',
        'supervisor_id',
        'role',
        'password',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            if (!$user->employee_id) {
                $user->employee_id = 'EMP' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT);
                $user->save();
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's department.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }



    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }


    public function attendanceNotes()
    {
        return $this->hasMany(AttendanceNote::class);
    }


    /**
     * Scope a query to only include non-admin users.
     */
    public function scopeNonAdmin($query)
    {
        return $query->where('role', '!=', 'admin')
            ->orWhereNull('role');
    }
}
