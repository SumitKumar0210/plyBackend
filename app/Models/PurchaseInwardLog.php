<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInwardLog extends Model
{
    use HasFactory,SoftDeletes;

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'purchase_order_id');
    }

     public function vendor()
    {
        return $this->hasOne(Vendor::class, 'id', 'vendor_id');
    }

}
