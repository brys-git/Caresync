<?php

namespace App\Controllers;

/**
 * Branded "page not found" screen, wired as the router's 404 override
 * (see Config/Routes.php). Going through a normal controller means the
 * response also passes the global filters, so the security headers are
 * sent on 404 pages too (the framework's plain 404 handler skips them).
 * Never shows the framework's message, a file path or a stack trace.
 */
class ErrorPage extends BaseController
{
    public function notFound()
    {
        $this->response->setStatusCode(404);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['error' => 'Not found']);
        }

        return $this->response->setBody(view('errors/html/error_404', ['message' => '']));
    }
}
