<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'billing_details';

    protected $fillable = [
        'bill_id',
        'product_id',
        'qty',
        'rate',
        'amount',
    ];

    public function billing()
    {
        return $this->belongsTo(Billing::class, 'bill_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
