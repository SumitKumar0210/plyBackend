<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TentativeItem extends Model
{
    use HasFactory,SoftDeletes;

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'po_id');
    }
}
