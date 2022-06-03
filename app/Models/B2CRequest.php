<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class B2CRequest extends Model
{
    use HasFactory;
    protected $table = 'b2c_requests';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
