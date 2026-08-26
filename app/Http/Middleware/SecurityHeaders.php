<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware keamanan header HTTP dasar.
 *
 * - X-Frame-Options: cegah clickjacking (DENY).
 * - X-Content-Type-Options: cegah MIME sniffing.
 * - Referrer-Policy: batasi kebocoran referer.
 * - Hapus X-Powered-By (disclosure versi PHP).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
