<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabourAttendance extends Model
{
    protected $table = 'labour_attendance';

    protected $fillable = [
        'id',
        'labour_id',
        'sign_in',
        'sign_out',
        'date',
    ];

    // protected $casts = [
    //     'attendance_date' => 'date',
    // ];


    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
}
