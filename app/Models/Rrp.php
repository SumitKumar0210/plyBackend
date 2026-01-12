<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Rrp extends Model
{
    protected $table="rrp";
    use HasFactory;
    
    protected $fillable=['id','pp_id','material_cost','labour_cost','gross_profit','miscellaneous','unit_cost','gross_profit_amount'];
    
   
}
