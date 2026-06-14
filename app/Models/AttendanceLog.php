<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    /**
     * Maps attendance records to the legacy attendance_log table.
     */
    protected $table = 'attendance_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'role',
        'personnel_id',
        'member_id',
        'visitor_id',
        'log_date',
        'time_in',
        'time_out',
    ];
}
