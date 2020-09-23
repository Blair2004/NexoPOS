<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\UUIDObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'expenses_categories';

    public function expenses()
    {
        return $this->hasMany( Expense::class, 'category_id' );
    }
}