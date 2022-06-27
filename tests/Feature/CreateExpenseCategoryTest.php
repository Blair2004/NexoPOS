<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithExpenseTest;

class CreateExpenseCategoryTest extends TestCase
{
    use WithAuthentication, WithExpenseTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateExpenses()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateExpensesCategories();
    }
}
