<?php

namespace Database\Seeders;

use App\Models\CashFlow;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpensesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $author             =   User::get()->map( fn( $user ) => $user->id )
            ->shuffle()
            ->first();

        $group          =   new ExpenseCategory;
        $group->name    =   'Exploitation Expenses';
        $group->account     =   '000010';
        $group->operation   =   CashFlow::OPERATION_DEBIT;
        $group->author  =   $author;
        $group->save();

        $group          =   new ExpenseCategory;
        $group->name    =   'Employee Salaries';
        $group->account     =   '000011';
        $group->operation   =   CashFlow::OPERATION_DEBIT;
        $group->author  =   $author;
        $group->save();

        $group          =   new ExpenseCategory;
        $group->name    =   'Random Expenses';
        $group->account     =   '000012';
        $group->operation   =   CashFlow::OPERATION_DEBIT;
        $group->author  =   $author;
        $group->save();
    }
}
