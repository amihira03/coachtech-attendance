<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;

    protected $fillable = [
        'attendance_id',
        'requested_by',
        'status',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_note',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function breaks()
    {
        return $this->hasMany(CorrectionRequestBreak::class);
    }
}
