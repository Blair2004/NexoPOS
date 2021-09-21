<?php

namespace App\Exceptions;

use App\Exceptions\QueryException as ExceptionsQueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException as MainValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'password_confirm'
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * We want to use our defined route
     * instead of what is provided by laravel.
     * @return \Illuminate\Routing\Redirector
     * 
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ( $request->expectsJson() ) {
            return response()->json([ 'status' => 'failed', 'message' => __( 'You\'re not authenticated.' ) ], 401);
        }

        return redirect()->guest( ns()->route( 'ns.login' ) );
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ( $exception instanceof MainValidationException ) {
            return ( new ValidationException( $exception->validator, $exception->response, $exception->errorBag ) )
                ->render( $request );
        }

        if ( $exception instanceof QueryException ) {
            if ( $request->expectsJson() ) {
                return response()->json([
                    'message' => $exception->getMessage()
                ], 404);    
            } 

            return ( new ExceptionsQueryException( $exception->getMessage() ) )
                ->render( $request );
        }

        return parent::render($request, $exception);
    }
}
