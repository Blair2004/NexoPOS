<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCategoryTest;

class CreateCategoryTest extends TestCase
{
    use WithAuthentication, WithCategoryTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateCategory()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCategory();
    }
}
