<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory,SoftDeletes;
    
   public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }
    
    protected $fillable = [
        'group_id',
        'name',
        'status',
        'created_by',
    ];
}
