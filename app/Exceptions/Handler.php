<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
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
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // When CSRF token mismatch (e.g. session was invalidated / demo_admin logged out remotely),
        // redirect to login instead of showing 419 error page.
        $this->renderable(function (TokenMismatchException $e, $request) {
            return $this->sessionExpiredResponse($request);
        });

        // Laravel converts TokenMismatchException to HttpException(419) before render;
        // handle 419 so AJAX saves get redirect instead of raw "CSRF token mismatch" JSON.
        $this->renderable(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                return $this->sessionExpiredResponse($request);
            }
        });

        $this->renderable(function (PostTooLargeException $e, $request) {
            if (! $request->routeIs('backend.short-drama.installer.install')) {
                return null;
            }

            $message = __('messages.short_drama_plugin_upload_php_limit', [
                'upload_max' => ini_get('upload_max_filesize') ?: 'unknown',
                'post_max' => ini_get('post_max_size') ?: 'unknown',
                'app_max' => '50 MB',
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 413);
            }

            return redirect()
                ->route('backend.short-drama.installer')
                ->with('error', $message);
        });
    }

    /**
     * Response for session expired / CSRF mismatch: redirect to login or JSON with redirect URL.
     */
    protected function sessionExpiredResponse($request)
    {
        $message = __('messages.session_expired_please_login');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('admin-login'),
            ], 419);
        }

        return redirect()->guest(route('admin-login'))->with('error', $message);
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        // Handle mail errors with clean messages instead of full stack traces
        if ($e instanceof \Symfony\Component\Mailer\Exception\UnexpectedResponseException) {
            $message = $e->getMessage();
            // Check for mail-related errors (Mailtrap rate limits, 550 errors, etc.)
            if (strpos($message, '550') !== false || 
                strpos($message, 'Mailtrap') !== false ||
                strpos($message, 'Too many emails') !== false ||
                strpos($message, '5.7.0') !== false) {
                // Log a clean, user-friendly message instead of full stack trace
                \Log::warning('Mail notification failed: Email sending rate limit reached. Subscription created successfully.', [
                    'error' => 'Mail service rate limit exceeded',
                    'message' => 'Email notification could not be sent due to rate limiting. This does not affect subscription creation.'
                ]);
                return; // Don't log the full exception
            }
        }

        // Call parent report method for other exceptions
        parent::report($e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api*')) {
            return response()->json(['error' => __('auth.unauthenticated')], 401);
        }

        return redirect()->guest(route('admin-login'));
    }
}
