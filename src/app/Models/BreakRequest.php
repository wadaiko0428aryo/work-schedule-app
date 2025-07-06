<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'attendance_request_id',
        'approved_by',
        'status',
        'requested_break_start_time',
        'requested_break_end_time',
        'edit_data',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
