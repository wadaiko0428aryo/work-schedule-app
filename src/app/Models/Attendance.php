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

    // AttendanceがUserを参照する（子->親）
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Attendanceは複数のAttendanceRequestを持つ（親->複数の子）
        public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    // attendanceに紐づくAttendanceRequestのうち一番新しい１件を取得
        public function latestRequest()
    {
        return $this->hasOne(AttendanceRequest::class)->latestOfMany();
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }
}
