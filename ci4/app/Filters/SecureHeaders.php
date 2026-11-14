<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Adds the standard browser-protection headers to every response.
 *
 * Registered in Config\Filters as a "required" after-filter (so it also runs on
 * the 404 page, where ordinary global filters do not) and in the global list.
 *
 * Content-Security-Policy is deliberately NOT sent yet: the pages use inline
 * <script> blocks and inline styles, which a strict policy would break. The
 * next step is documented in cspHeader() below.
 */
class SecureHeaders implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Camera is allowed for this site only: the Government ID verification
        // widget takes a photo with it. Microphone and location stay off.
        $response->setHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        if ($this->isHttps($request)) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000');
        }

        // $csp = $this->cspHeader();
        // if ($csp !== '') { $response->setHeader('Content-Security-Policy', $csp); }

        return $response;
    }

    /**
     * NEXT STEP (not enabled): a Content-Security-Policy.
     *
     * To turn it on safely, first move the inline <script>/<style> blocks in
     * app/Views into files under public/assets (or give them a per-request
     * nonce), then test every page and enable the two commented lines in
     * after() above. A starting point:
     *
     *   default-src 'self';
     *   script-src 'self' https://cdn.jsdelivr.net;
     *   style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com;
     *   font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:;
     *   img-src 'self' data: blob:;
     *   connect-src 'self' https://psgc.cloud;
     *   frame-ancestors 'self'
     *
     * Adjust the hosts to whatever the layouts actually load.
     */
    private function cspHeader(): string
    {
        return '';
    }

    /** True for HTTPS, including behind a proxy that forwards the original scheme. */
    private function isHttps(RequestInterface $request): bool
    {
        if (method_exists($request, 'isSecure') && $request->isSecure()) {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
