<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaSetting extends Model
{
    protected $fillable = [
        'priority',
        'response_hours',
        'resolution_hours',
    ];
}