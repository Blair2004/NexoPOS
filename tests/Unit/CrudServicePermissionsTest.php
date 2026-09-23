<?php

namespace Tests\Unit;

use App\Exceptions\NotAllowedException;
use App\Services\CrudService;
use Tests\TestCase;

class CrudServicePermissionsTest extends TestCase
{
    public function test_allowed_to_accepts_true_permissions(): void
    {
        $crud = new class extends CrudService
        {
            protected $permissions = [
                'create' => true,
            ];
        };

        $crud->allowedTo( 'create' );

        $this->assertTrue( true );
    }

    public function test_allowed_to_rejects_false_permissions(): void
    {
        $crud = new class extends CrudService
        {
            protected $permissions = [
                'delete' => false,
            ];
        };

        $this->expectException( NotAllowedException::class );

        $crud->allowedTo( 'delete' );
    }
}
