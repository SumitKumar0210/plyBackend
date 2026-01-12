<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "shipping_addresses";

    protected $fillable = [
        'customer_id',
        'state_id',
        'city',
        'zip_code',
        'address',
    ];

    // protected $casts = [
    //     'customer_id' => 'integer',
    //     'state_id'    => 'integer',
    // ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
