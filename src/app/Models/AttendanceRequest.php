<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'approved_by',
        'status',
        'requested_start_time', 'requested_end_time',
        'requested_break_start_time', 'requested_break_end_time',
        'requested_reason', 'edit_data', 'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    //  リレーション：申請者（User）
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //  リレーション：対象の勤怠データ
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    //  リレーション：承認者（User）
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}