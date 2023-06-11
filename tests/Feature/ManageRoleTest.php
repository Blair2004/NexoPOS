<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithRoleTest;

class ManageRoleTest extends TestCase
{
    use WithAuthentication, WithRoleTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_reserved_role()
    {
        $this->attemptAuthenticate();

        $this->attemptCreateReservedRole();
    }

    public function test_edit_reserved_role()
    {
        $this->attemptAuthenticate();

        $this->attemptEditReservedRole();
    }

    public function test_delete_reserved_role()
    {
        $this->attemptAuthenticate();

        $this->attemptDeleteReservedRole();
    }
}
