<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class purchaseMaterial extends Model
{
    use HasFactory,SoftDeletes;

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'purchase_order_id');
    }

    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }
}
