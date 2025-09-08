<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory,SoftDeletes;

    public function category()
   {
    return $this->hasOne(Category::class, 'id', 'category_id');
   }
   public function group()
   {
    return $this->hasOne(Group::class, 'id', 'group_id');
   }
   public function unitOfMeasurement()
   {
    return $this->hasOne(UnitOfMeasurement::class, 'id', 'unit_of_measurement_id');
   }
}
