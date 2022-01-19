<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithUnitTest;

class CreateUnitGroupTest extends TestCase
{
    use WithAuthentication, WithUnitTest;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateUnitGroup()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateUnitGroup();
    }
}
