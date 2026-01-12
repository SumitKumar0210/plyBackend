<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_id',
        'type',
        'qty',
        'previous_qty',
        'new_qty',
        'reference_type',
        'reference_id',
        'action_by',
    ];
}
