<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use HasFactory,SoftDeletes;

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'po_id');
    }
}
