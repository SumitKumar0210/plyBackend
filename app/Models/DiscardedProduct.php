<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscardedProduct extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'id',
        'product_id',
        'qty',
        'date',
        'action_by',
        'revised',
        'remark',
    ];
    
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'action_by');
    }
}
