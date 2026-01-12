<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLog extends Model
{
    use HasFactory, SoftDeletes;

    // Allow mass assignment
    protected $fillable = [
        'po_id',
        'production_product_id',
        'status',
        'remark',
        'from_stage',
        'to_stage',
        'action_by',
    ];

    public function fromStage()
    {
        return $this->belongsTo(Department::class, 'from_stage', 'id');
    }

    public function toStage()
    {
        return $this->belongsTo(Department::class, 'to_stage', 'id');
    }
    
    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'po_id');
    }

    public function productionProduct()
    {
        return $this->belongsTo(ProductionProduct::class, 'production_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
