<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'recharge-callback/*',
        'recharge-callback',
    ];

    /**
     * Create a new XSRF-TOKEN cookie (unique per app so user+admin can run together locally).
     */
    protected function newCookie($request, $config)
    {
        return new Cookie(
            config('session.xsrf_cookie', 'XSRF-TOKEN'),
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            false,
            false,
            $config['same_site'] ?? null
        );
    }

    public static function serialized()
    {
        return EncryptCookies::serialized(config('session.xsrf_cookie', 'XSRF-TOKEN'));
    }
}
