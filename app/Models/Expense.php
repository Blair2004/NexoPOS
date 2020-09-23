<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'expenses';

    public function category()
    {
        return $this->belongsTo( Expense::class, 'category_id' );
    }
}