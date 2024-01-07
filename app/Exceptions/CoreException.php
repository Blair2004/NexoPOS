<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;
use Illuminate\Http\Request;

class CoreException extends Exception
{
    public function __construct(public $message = null, public $code = 0, public $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request, $exception)
    {
        $title = __('Oops, We\'re Sorry!!!');
        $back = Helper::getValidPreviousUrl($request);
        $message = $exception->getMessage() ?: sprintf(__('Class: %s'), get_class($exception));

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'failed',
                'message' => $message,
                'exception' => $exception::class,
                'previous' => $back,
                'trace' => $this->previous->getTrace(),
                'line' => $this->previous->getLine(),
                'file' => $this->previous->getFile(),
            ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 501);
        }

        return response()->view('pages.errors.exception', compact('message', 'title', 'back'), 503);
    }
}
