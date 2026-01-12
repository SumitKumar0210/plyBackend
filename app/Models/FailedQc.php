<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedQc extends Model
{
    use HasFactory;

    protected $table = 'failed_qc';

    protected $fillable = [
        'pp_id',
        'reason',
        'doc',
        'action_by',
    ];

    // protected $casts = [
    //     'pp_id' => 'integer',
    //     'action_by' => 'integer',
    // ];
}
