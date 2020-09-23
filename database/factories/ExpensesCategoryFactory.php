<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\ExpenseCategory;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpensesCategoryFactory extends Factory
{
    protected $model    =   ExpenseCategory::class;

    public function definition()
    {
        return [
            //
        ];
    }
}
