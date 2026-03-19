<?php

namespace App\Exceptions;

use App\Models\ClientError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Laravel の $internalDontReport が reportable() より先にこれらをフィルタリングするため、
            // 通常このガードは実行されない。将来の変更に備えた防衛的チェックとして残す。
            $skip = [
                ValidationException::class,
                AuthenticationException::class,
                AuthorizationException::class,
                TokenMismatchException::class,
                HttpException::class,
            ];

            foreach ($skip as $class) {
                if ($e instanceof $class) {
                    return;
                }
            }

            $isHttp = !app()->runningInConsole();

            ClientError::log([
                'error_type' => 'server_error',
                'message'    => $e->getMessage() ?: get_class($e),
                'stack'      => substr($e->getTraceAsString(), 0, 3000),
                'url'        => $isHttp ? request()->url() : null,
                'user_agent' => $isHttp ? request()->userAgent() : null,
                'manager_id' => $isHttp ? request()->user()?->id : null,
            ]);
        });
    }

    public function render($request, Throwable $exception)
    {
        // Tokenエラーの時、ログイン画面にリダイレクトする。
        if ($exception instanceof TokenMismatchException) {
            return redirect(route('login'));
        }

        return parent::render($request, $exception);
    }
}
