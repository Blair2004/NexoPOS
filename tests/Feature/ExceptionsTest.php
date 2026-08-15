<?php

namespace Tests\Feature;

use App\Exceptions\CoreException;
use App\Exceptions\CoreVersionMismatchException;
use App\Exceptions\MethodNotAllowedHttpException;
use App\Exceptions\MissingDependencyException;
use App\Exceptions\ModuleVersionMismatchException;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Exceptions\QueryException;
use App\Exceptions\ValidationException;
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
            CoreException::class,
            CoreVersionMismatchException::class,
            MethodNotAllowedHttpException::class,
            MissingDependencyException::class,
            ModuleVersionMismatchException::class,
            NotAllowedException::class,
            NotFoundException::class,
            QueryException::class,
            ValidationException::class,
        ] )->each( function ( $class ) {
            $instance = new $class;
            $response = $this->get( 'exceptions?class=' . $class );
            $response->assertSee( $instance->getMessage() );
        } );
    }
}
