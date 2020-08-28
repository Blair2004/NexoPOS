<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table    =   'nexopos_' . 'expenses';

    public function category()
    {
        return $this->belongsTo( Expense::class, 'category_id' );
    }
}