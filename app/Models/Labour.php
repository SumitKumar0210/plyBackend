<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labour extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'labours';

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
     public function shift()
    {
        return $this->belongsTo(WorkShift::class, 'shift_id', 'id');
    }
}
