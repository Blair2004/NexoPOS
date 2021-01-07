<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Register extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'registers';

    const STATUS_OPENED         =   'opened';
    const STATUS_CLOSED         =   'closed';
    const STATUS_DISABLED       =   'disabled';
}