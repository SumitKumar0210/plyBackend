<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequest extends Model
{
    use HasFactory,SoftDeletes;
    
     protected $fillable = [
        'pp_id',
        'material_id',
        'size',
        'qty',
        'status',
        'department_id',
        'uuid',
    ];
    
    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }
    
    public function productionProduct()
    {
        return $this->belongsTo(ProductionProduct::class, 'pp_id');
    }

}