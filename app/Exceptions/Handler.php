<?php

namespace App\Exceptions;

use App\Exceptions\MethodNotAllowedHttpException as ExceptionsMethodNotAllowedHttpException;
use App\Exceptions\PostTooLargeException as ExceptionsPostTooLargeException;
use App\Exceptions\QueryException as ExceptionsQueryException;
use App\Services\Helper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
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
        if ($request->expectsJson()) {
            return response()->json([ 'status' => 'failed', 'message' => __('You\'re not authenticated.') ], 401);
        }

        return redirect()->guest(ns()->route('ns.login'));
    }

    public function render($request, Throwable $exception)
    {
        /**
         * When the exception doesn't provide a custom "handler" method
         * we'll try to render it ourself.
         */
        if ($request->expectsJson()) {
            return $this->renderJsonException($request, $exception);
        } else {
            return $this->renderViewException($request, $exception);
        }
    }

    /**
     * Render an exception into an HTTP response.
     */
    protected function renderViewException($request, $exception): Response
    {
        $title = __('Oops, We\'re Sorry!!!');
        $back = Helper::getValidPreviousUrl($request);
        $message = $exception->getMessage() ?: sprintf(__('Class: %s'), get_class($exception));
        $exploded = explode('(View', $message);
        $message = $exploded[0] ?? $message;

        if (env('APP_DEBUG', true)) {
            /**
             * We'll attempt our best to display or
             * return a proper response for unsupported exceptions
             * mostly these are either package exceptions or laravel exceptions
             */
            return parent::render($request, $exception);
        } else {
            return response()->view('pages.errors.exception', compact('message', 'title', 'back'), 500);
        }
    }

    /**
     * Render an exception into a JSON response.
     */
    protected function renderJsonException($request, $exception): Response
    {
        $exceptionsWithCode = [
            AuthenticationException::class => 401,
            ExceptionsMethodNotAllowedHttpException::class => 405,
            ExceptionsPostTooLargeException::class => 413,
            ExceptionsQueryException::class => 500,
            TypeError::class => 500,
        ];

        $code = $exceptionsWithCode[ get_class($exception) ] ?? 500;

        $back = Helper::getValidPreviousUrl($request);
        $message = $exception->getMessage() ?: sprintf(__('Class: %s'), get_class($exception));
        $exploded = explode('(View', $message);
        $message = $exploded[0] ?? $message;

        if (env('APP_DEBUG', true)) {
            return response()->json([
                'status' => 'failed',
                'message' => $message,
                'previous' => $back,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : $code);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => __('An error occured while performing your request.'),
                'previous' => $back,
            ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : $code);
        }
    }
}
