<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'stocks';

    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }
}
