<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\UUIDObserver;

class ExpenseCategory extends Model
{
    protected $table    =   'nexopos_' . 'expenses_categories';

    public function expenses()
    {
        return $this->hasMany( Expense::class, 'category_id' );
    }
}