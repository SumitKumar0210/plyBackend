<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceLog extends Model
{
    use HasFactory,SoftDeletes;

     public function machine()
    {
        return $this->hasOne(Machine::class, 'id', 'machine_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
