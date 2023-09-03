<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithTransactionTest;

class CreateExpenseTest extends TestCase
{
    use WithAuthentication, WithTransactionTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateAllExpenses()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTransaction();
    }
}
