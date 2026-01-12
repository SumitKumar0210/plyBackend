<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class WorkShift extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'id',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
    ];
}
