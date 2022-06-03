<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortCodeConfig extends Model
{
    use HasFactory;
    protected $table = 'shortcode_configs';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
