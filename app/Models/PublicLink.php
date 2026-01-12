<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entity_id',
        'entity_name',
        'link',
        'expiry_time',
        'view_count',
    ];
}
