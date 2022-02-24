<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
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
