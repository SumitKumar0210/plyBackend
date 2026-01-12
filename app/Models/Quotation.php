<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory,SoftDeletes;
    
    public function customer(){
        return $this->hasOne(Customer::class,'id', 'customer_id');
    }
    public function publicLink(){
        return $this->hasOne(PublicLink::class,'quotation_id', 'id');
    }
    
    public function product(){
        return $this->hasMany(QuotationProducts::class,'quotation_id', 'id');
    }
}
