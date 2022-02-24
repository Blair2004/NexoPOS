<?php

namespace App\Exceptions;

use App\Exceptions\MethodNotAllowedHttpException as ExceptionsMethodNotAllowedHttpException;
use App\Exceptions\QueryException as ExceptionsQueryException;
use ErrorException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException as MainValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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
     * Register custom handler
     * 
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if ($this->shouldReport($e) && app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
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

        if ( env( 'NS_CUSTOM_ERROR_HANDLER', true ) ) {

            /**
             * Let's make a better verfication
             * to avoid repeating outself.
             */
            $exceptionResponse  =   collect([
                ModuleVersionMismatchException::class   =>  [
                    'use'           =>  ModuleVersionMismatchException::class,
                    'safeMessage'   =>  null,
                    'code'          =>  503
                ],
    
                QueryException::class   =>  [
                    'use'           =>  ExceptionsQueryException::class,
                    'safeMessage'   =>  __( 'A database error has occured' ),
                    'code'          =>  503
                ],
    
                MethodNotAllowedHttpException::class    =>  [
                    'use'           =>  ExceptionsMethodNotAllowedHttpException::class,
                    'safeMessage'   =>  __( 'Invalid method used for the current request.' ),
                    'code'          =>  405
                ],
    
                CoreVersionMismatchException::class     =>  [
                    'use'           =>  CoreVersionMismatchException::class,
                    'safeMessage'   =>  null,
                    'code'          =>  503
                ],
    
                ModuleVersionMismatchException::class     =>  [
                    'use'           =>  ModuleVersionMismatchException::class,
                    'safeMessage'   =>  null,
                    'code'          =>  503
                ],
    
                InvalidArgumentException::class         =>  [
                    'use'           =>  Exception::class,
                    'safeMessage'   =>  null,
                    'code'          =>  503
                ],
    
                ErrorException::class         =>  [
                    'use'           =>  Exception::class,
                    'safeMessage'   =>  __( 'An unexpected error occured while opening the app. See the log details or enable the debugging.' ),
                    'code'          =>  503
                ]
            ])->map( function( $exceptionConfig, $class ) use ( $exception, $request ) {
                if ( $exception instanceof $class ) {
                    if ( $request->expectsJson() ) {
                        Log::error( $exception->getMessage() );
        
                        /**
                         * We'll return a safe message if the debug mode is enabled
                         * otherwise, we'll return the full message which might have 
                         * sensitive informations.
                         */
                        if ( env( 'APP_DEBUG' ) ) {
                            return response()->json( 
                                $this->convertExceptionToArray( $exception ),
                                500
                            );
                        } else {
                            return response()->json([
                                'message' => ! empty( $exceptionConfig[ 'safeMessage' ] ) && ! env( 'APP_DEBUG' ) ? $exceptionConfig[ 'safeMessage' ] : $exception->getMessage()
                            ], $exceptionConfig[ 'code' ] ?? 500 );    
                        }
                    } 
        
                    return ( new $exceptionConfig[ 'use' ]( 
                        ! empty( $exceptionConfig[ 'safeMessage' ] ) && ! env( 'APP_DEBUG' ) ? $exceptionConfig[ 'safeMessage' ] : $exception->getMessage()
                    ) )
                        ->render( $request );
                }
    
                return false;
            })->filter( fn( $exception ) => $exception !== false );
            
            if ( ! $exceptionResponse->isEmpty() ) {
                return $exceptionResponse->first();
            }
        }        


        return parent::render($request, $exception);
    }
}
