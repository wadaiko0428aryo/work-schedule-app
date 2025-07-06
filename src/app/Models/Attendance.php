<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceRequest;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'reason',
        'is_approval',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

        public function latestRequest()
    {
        return $this->hasOne(AttendanceRequest::class)->latestOfMany();
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }
}
