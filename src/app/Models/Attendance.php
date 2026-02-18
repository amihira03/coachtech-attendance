<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_OFF_DUTY = 0; // 勤務外
    public const STATUS_WORKING = 1;  // 出勤中
    public const STATUS_ON_BREAK = 2; // 休憩中
    public const STATUS_FINISHED = 3; // 退勤済


    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function statusText(): string
    {
        return match ($this->status) {
            self::STATUS_OFF_DUTY => '勤務外',
            self::STATUS_WORKING => '出勤中',
            self::STATUS_ON_BREAK => '休憩中',
            self::STATUS_FINISHED => '退勤済',
            default => '不明',
        };
    }
}
