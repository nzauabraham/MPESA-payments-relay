<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StkRequest extends Model
{
    use HasFactory;
    protected $table = 'stk_requests';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
