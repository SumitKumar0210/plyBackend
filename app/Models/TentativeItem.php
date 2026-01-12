<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TentativeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'material_id',
        'qty',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
