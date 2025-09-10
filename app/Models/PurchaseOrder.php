<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model 
{
    use HasFactory,SoftDeletes;

    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'id', 'vendor_id');
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }
}
