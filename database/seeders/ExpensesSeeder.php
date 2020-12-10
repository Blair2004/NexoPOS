<?php

namespace Database\Seeders;

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
        $group->author  =   $author;
        $group->save();

        $group          =   new ExpenseCategory;
        $group->name    =   'Employee Salaries';
        $group->author  =   $author;
        $group->save();

        $group          =   new ExpenseCategory;
        $group->name    =   'Random Expenses';
        $group->author  =   $author;
        $group->save();
    }
}
