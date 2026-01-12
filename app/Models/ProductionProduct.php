<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionProduct extends Model
{
    // ,SoftDeletes
    use HasFactory;
    
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'pp_id', 'id');
    }
    
    public function messages()
    {
        return $this->hasMany(PpMessage::class, 'pp_id', 'id');
    }
    
    public function materialRequest()
    {
        return $this->hasMany(MaterialRequest::class, 'pp_id', 'id');
    }
    public function tentativeItems()
    {
        return $this->hasMany(TentativeItem::class, 'product_id', 'product_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    
    public function rrp()
    {
        return $this->hasOne(Rrp::class, 'pp_id', 'id');
    }
}
