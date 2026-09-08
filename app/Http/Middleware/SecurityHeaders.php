<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Block installer when app is already installed (A05)
        if (file_exists(storage_path('installed')) && ($request->is('install') || $request->is('install/*'))) {
            abort(404);
        }

        // Force HTTPS in production (A02)
        if (App::environment('production') && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Strict Transport Security (HSTS)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy - Comprehensive for streaming platform
        // $csp = $this->buildContentSecurityPolicy();
        // $response->headers->set('Content-Security-Policy', $csp);

        // Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (Feature Policy)
        $response->headers->set('Permissions-Policy',
            'camera=(), microphone=(), geolocation=(self), gyroscope=(), ' .
            'magnetometer=(), payment=(self), usb=(), autoplay=(), ' .
            'fullscreen=(self), picture-in-picture=(self)'
        );

        // Cross-Origin Policies
        // $response->headers->set('Cross-Origin-Embedder-Policy', 'credentialless');
        // $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        // $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Remove information disclosure headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        $response->headers->remove('panel');
        $response->headers->remove('platform');
        $response->headers->remove('x-turbo-charged-by');
        $response->headers->remove('server-timing');

        return $response;
    }

    /**
     * Build comprehensive Content Security Policy for streaming platform
     */
    private function buildContentSecurityPolicy(): string
    {
        $policies = [];

        // Default source - self and trusted domains
        $policies[] = "default-src 'self'";

        // Scripts - allow self, CDNs, and analytics
        $policies[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
                     "https://cdn.jsdelivr.net " .
                     "https://code.jquery.com " .
                     "https://unpkg.com " .
                     "https://www.google-analytics.com " .
                     "https://www.googletagmanager.com " .
                     "https://js.stripe.com " .
                     "https://connect.facebook.net";

        // Styles - allow self, CDNs, and inline styles
        $policies[] = "style-src 'self' 'unsafe-inline' " .
                     "https://cdn.jsdelivr.net " .
                     "https://fonts.googleapis.com " .
                     "https://unpkg.com";

        // Images - allow self and common image sources
        $policies[] = "img-src 'self' data: https: blob:";

        // Fonts - allow self and Google Fonts
        $policies[] = "font-src 'self' https://fonts.gstatic.com";

        // Connect - allow self and common APIs
        $policies[] = "connect-src 'self' " .
                     "https://api.stripe.com " .
                     "https://www.google-analytics.com " .
                     "https://www.facebook.com " .
                     "wss:// " . // WebSocket connections
                     "*.pusherapp.com"; // Real-time connections

        // Media - allow self and video streaming sources
        $policies[] = "media-src 'self' https: blob:";

        // Objects and embeds - restrict to self
        $policies[] = "object-src 'none'";
        $policies[] = "frame-src 'self' https://js.stripe.com https://www.facebook.com";

        // Frames ancestors - allow embedding in same origin and specific domains
        $policies[] = "frame-ancestors 'self'";

        // Forms - allow self
        $policies[] = "form-action 'self' https://checkout.stripe.com";

        // Base URI - restrict to self
        $policies[] = "base-uri 'self'";

        // Upgrade insecure requests in production
        if (App::environment('production')) {
            $policies[] = "upgrade-insecure-requests";
        }

        return implode('; ', $policies);
    }
}
