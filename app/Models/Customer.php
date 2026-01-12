<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory,SoftDeletes;
    
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
    
    public function payments()
    {
        return $this->hasMany(Billing::class,'customer_id','id');
    }

}
