<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Register extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'registers';
}