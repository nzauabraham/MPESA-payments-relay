<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutgoingMessage extends Model
{
    use HasFactory;
    protected $table = 'outgoing_messages';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
