<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

    // public function group()
    // {
    //     return $this->hasOne(Group::class, 'id', 'group_id');
    // }
    
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id'); // 'group_id' is the foreign key in products table
    }
}
