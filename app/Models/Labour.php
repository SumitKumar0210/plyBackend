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
        return $this->hasOne(Department::class, 'id', 'department_id');
    }
}
