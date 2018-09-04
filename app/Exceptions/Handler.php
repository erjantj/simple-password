<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        ValidationException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $message = '';
        $status = '';

        if ($e instanceof ModelNotFoundException) {
            $message = 'Record not found';
            $status = 404;

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }
        }
        else if ($e instanceof JWTException) {
            $message = 'Token does not exists';
            $status = 401;

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }
        }
        else if ($e instanceof MethodNotAllowedHttpException) {
            $message = 'Method not allowed';
            $status = $e->getStatusCode();

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }
        }
        else if ($e instanceof NotFoundHttpException) {
            $message = 'Route not found';
            $status = $e->getStatusCode();

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }
        }
        else if ($e instanceof HttpException) {
            $message = 'Http exception';
            $status = $e->getStatusCode();

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }
        }

        if ($message && $status) {
            return response()->json(['message' => $message], $status);
        }

        return parent::render($request, $e);
    }
}
