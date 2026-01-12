<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InwardPaymentLog extends Model
{
    use HasFactory,SoftDeletes;
    
    public function inward()
    {
        return $this->hasOne(PurchaseInwardLog::class, 'id', 'inward_id');
    }
}