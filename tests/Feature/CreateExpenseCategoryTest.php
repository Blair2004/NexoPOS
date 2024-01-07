<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithTransactionTest;

class CreateExpenseCategoryTest extends TestCase
{
    use WithAuthentication, WithTransactionTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateExpenses()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTransactionAccount();
    }
}
