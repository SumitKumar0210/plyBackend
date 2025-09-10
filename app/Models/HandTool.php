<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandTool extends Model
{
    use HasFactory,SoftDeletes;

    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }

    public function labour()
    {
        return $this->hasOne(Labour::class, 'id', 'labour_id');
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }
}
