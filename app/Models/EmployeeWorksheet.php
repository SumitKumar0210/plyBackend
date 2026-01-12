<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeWorksheet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pp_id',
        'labour_id',
        'sign_in',
        'sign_out',
        'date',
        'total_minutes',
        'overtime',
    ];

    // protected $casts = [
    //     'date'     => 'date',
    //     'sign_in'  => 'datetime:H:i',
    //     'sign_out' => 'datetime:H:i',
    // ];

    public function labour()
    {
        return $this->belongsTo(Labour::class, 'labour_id', 'id');
    }
    // public function shift()
    // {
    //     return $this->belongsTo(WorkShift::class, 'shift_id', 'id');
    // }

    public function productionProduct()
    {
        return $this->belongsTo(ProductionProduct::class, 'pp_id', 'id');
    }
}
