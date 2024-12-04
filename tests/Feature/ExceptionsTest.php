<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExceptionsTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_exceptions_output()
    {
        collect( [
            \App\Exceptions\CoreException::class,
            \App\Exceptions\CoreVersionMismatchException::class,
            \App\Exceptions\MethodNotAllowedHttpException::class,
            \App\Exceptions\MissingDependencyException::class,
            \App\Exceptions\ModuleVersionMismatchException::class,
            \App\Exceptions\NotAllowedException::class,
            \App\Exceptions\NotFoundException::class,
            \App\Exceptions\QueryException::class,
            \App\Exceptions\ValidationException::class,
        ] )->each( function ( $class ) {
            $instance = new $class;
            $response = $this->get( 'exceptions?class=' . $class );
            $response->assertSee( $instance->getMessage() );
        } );
    }
}
