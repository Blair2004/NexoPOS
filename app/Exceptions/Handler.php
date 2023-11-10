<?php

namespace App\Exceptions;

use App\Exceptions\MethodNotAllowedHttpException as ExceptionsMethodNotAllowedHttpException;
use App\Exceptions\PostTooLargeException as ExceptionsPostTooLargeException;
use App\Exceptions\QueryException as ExceptionsQueryException;
use ArgumentCountError;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use ErrorException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use TypeError;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'password_confirm',
    ];

    /**
     * Register custom handler
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // ...
        });
    }

    /**
     * We want to use our defined route
     * instead of what is provided by laravel.
     *
     * @return Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ( $request->expectsJson() ) {
            return response()->json([ 'status' => 'failed', 'message' => __( 'You\'re not authenticated.' ) ], 401);
        }

        return redirect()->guest( ns()->route( 'ns.login' ) );
    }

    public function render( $request, Throwable $exception )
    {
        /**
         * We're dealing here with exceptions that
         * provide a custom "handler" method.
         */
        if ( method_exists( $exception, 'render' ) ) {
            return $exception->render( $request, $exception );
        }

        $matches    =   [
            PostTooLargeException::class            =>  ExceptionsPostTooLargeException::class,
            QueryException::class                   =>  ExceptionsQueryException::class,
            MethodNotAllowedHttpException::class    =>  ExceptionsMethodNotAllowedHttpException::class,
            InvalidArgument::class                  =>  CoreException::class,
            ErrorException::class                   =>  CoreException::class,
            ArgumentCountError::class               =>  CoreException::class,
            TypeError::class                        =>  CoreException::class,
        ];

        /**
         * This will replace original unsupported exceptions with
         * understandable exceptions that return proper reponses.
         */
        foreach( $matches as $bind => $use ) {
            if ( $exception instanceof $bind ) {
                throw new $use( env( 'APP_DEBUG' ) ? $exception->getMessage() : __( 'Something went wrong.' ), 502, $exception );
            }
        }

        /**
         * We'll attempt our best to display or 
         * return a proper response for unsupported exceptions
         * mostly these are either package exceptions or laravel exceptions
         */
        return parent::render( $request, $exception );
    }
}
