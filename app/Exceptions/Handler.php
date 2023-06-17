<?php

namespace App\Exceptions;

use Throwable;
use BadMethodCallException;
use InvalidArgumentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response as JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\Response as Http;


class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });
    }


    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {

        if ($e instanceof NotFoundHttpException || $e instanceof RouteNotFoundException) {
            return Response::json([
                'success'   => false,
                'status'    => JsonResponse::HTTP_NOT_FOUND,
                'message'   =>  "Not Found.",
            ], Http::HTTP_NOT_FOUND);
        }

        if ($e instanceof ModelNotFoundException) {
            return Response::json([
                'success'   => false,
                'status'    => Http::HTTP_NOT_FOUND,
                'message'   =>  "Model Not Found.",
                'err'   => $e->getMessage(),
            ], Http::HTTP_NOT_FOUND);
        }


        if ($e instanceof QueryException) {
            return Response::json([
                'success'   => false,
                'status'    => Http::HTTP_UNAUTHORIZED,
                'message'   => "Unauthenticated Access.",
                'err'   => $e->getMessage(),
            ], Http::HTTP_OK);
        }


        if ($e instanceof AuthenticationException) {
            return Response::json([
                'success'   => false,
                'status'    => Http::HTTP_UNAUTHORIZED,
                'message'   => "Unauthenticated Access.",
                'err'   => $e->getMessage(),
            ], Http::HTTP_UNAUTHORIZED); // HTTP_UNAUTHORIZED
        }


        if ($e instanceof HttpException || $e instanceof InvalidSignatureException) {
            return Response::json([
                'success'   => false,
                'status'    => $e->getStatusCode(),
                'message'   => __("Unauthenticated."),
                // 'message'   => $e->getMessage(),
                'err'   => $e->getMessage(),
            ], Http::HTTP_UNAUTHORIZED);
        }


        if ($e instanceof MethodNotAllowedHttpException || $e instanceof BadMethodCallException) {
            return Response::json([
                'success'   => false,
                // 'status'    => Http::HTTP_METHOD_NOT_ALLOWED,
                'status'    => $e->getStatusCode(),
                'message'   =>  "Method Not Allowed.",
                // 'message'   => $e->getMessage(),
                'err'   => $e->getMessage(),
            ], Http::HTTP_METHOD_NOT_ALLOWED);
        }


        if ($e instanceof ThrottleRequestsException) {
            return Response::json([
                'success'   => false,
                'status'    => $e->getStatusCode(),
                'message'   =>  "Unauthenticated Access.",
                // "message"   => $e->getMessage(),
                'err'   => $e->getMessage(),
            ], Http::HTTP_UNAUTHORIZED); // HTTP_METHOD_NOT_ALLOWED
        }


        if ($e instanceof Throwable) {
            if ($e->getCode() == 0) {
                return Response::json([
                    'success'   => false,
                    'status'    => Http::HTTP_METHOD_NOT_ALLOWED,
                    'message'   => "Unauthenticated Access.",
                    'err'   => $e->getMessage(),
                ], Http::HTTP_UNAUTHORIZED); // HTTP_METHOD_NOT_ALLOWED
            } else {
                return Response::json([
                    'success'   => false,
                    'status'    => $e->getCode(),
                    'message'   => $e->getMessage(),
                    'err'   => $e->getMessage(),
                ], $e->getCode());
            }
        }


        if ($e instanceof BindingResolutionException) {
            return Response::json([
                'success'   => false,
                'status'    => Http::HTTP_METHOD_NOT_ALLOWED,
                'message'   => "Unauthenticated Access.",
                'err'   => $e->getMessage(),
            ], Http::HTTP_UNAUTHORIZED); // HTTP_METHOD_NOT_ALLOWED
        }


        if ($e instanceof InvalidArgumentException) {
            return Response::json([
                'success'   => false,
                'status'    => $e->getCode(),
                'message'   => "Unauthenticated Access.",
                'err'   => $e->getMessage(),
            ], Http::HTTP_UNAUTHORIZED); // HTTP_METHOD_NOT_ALLOWED
        }

        return Response::json([
            'success'   => false,
            'status'    => JsonResponse::HTTP_NOT_FOUND,
            'message'   =>  "Not Found.",
        ], Http::HTTP_NOT_FOUND);

        return parent::render($request, $e);
    }
}
