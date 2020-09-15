<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof AuthenticationException) {
            return response()->format(401, 'Unauthenticated');
        } elseif ($e instanceof ModelNotFoundException) {
            return response()->format(404, 'Object Not Found');
        } elseif ($e instanceof NotFoundHttpException) {
            return response()->format(404, 'Url Not Found');
        } else {
            // ref: https://stackoverflow.com/a/35319899
            //return self::response_error($e->getMessage(), 500);
            $request->headers->set('Accept', 'application/json');
            return parent::render($request, $e);
        }
    }
}
