<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory,SoftDeletes;
    
    public function category(){
        return $this->hasOne(Category::class, 'id', 'category_id');
    }
    
    public function inwards(){
        return $this->hasMany(PurchaseInwardLog::class, 'vendor_id','id');
    }
    public function state(){
        return $this->hasOne(State::class, 'id','state_id');
    }
}
