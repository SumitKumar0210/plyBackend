<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnPurchase extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'grn_purchases';

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'purchase_order_id');
    }
}
