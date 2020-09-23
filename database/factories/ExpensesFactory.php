<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpensesFactory extends Factory 
{
    protected $model    =   Expense::class;

    public function definition()
    {
        return [
            //
        ];
    }
}
