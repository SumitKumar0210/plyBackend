<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductUnitMaterial extends Model
{
    use HasFactory,SoftDeletes;

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }

}