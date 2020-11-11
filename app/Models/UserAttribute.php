<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAttribute extends Model
{
    use HasFactory;

    public $timestamps      =   false;
    protected $table        =   'nexopos_users_attributes';
}
