<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithExpenseTest;

class CreateExpenseTest extends TestCase
{
    use WithAuthentication, WithExpenseTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateAllExpenses()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateExpenses();
    }
}
