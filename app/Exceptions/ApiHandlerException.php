<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiHandlerException
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return static::render($e);
        });
    }

    public static function render(Throwable $e): JsonResponse
    {
        [$status, $message, $errors] = static::resolve($e);

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * @return array{0: int, 1: string, 2: array<string, mixed>}
     */
    protected static function resolve(Throwable $e): array
    {
        return match (true) {
            $e instanceof ApiException => [$e->status, $e->getMessage(), $e->errors],
            $e instanceof ValidationException => [422, __('messages.validation'), $e->errors()],
            $e instanceof AuthenticationException => [401, __('messages.unauthorized'), []],
            $e instanceof AuthorizationException => [403, __('messages.forbidden'), []],
            $e instanceof NotFoundHttpException => [404, __('messages.not_found'), []],
            $e instanceof ModelNotFoundException => [404, __('messages.not_found'), []],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage(), []],
            default => [500, __('messages.internal_error'), []],
        };
    }
}
