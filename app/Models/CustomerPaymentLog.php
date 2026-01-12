<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPaymentLog extends Model
{
    use HasFactory,SoftDeletes;
    
    public function bill()
    {
        return $this->belongsTo(Billing::class, 'bill_id');
    }

}
