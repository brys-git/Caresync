<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Please sign in first.');
        }

        $path = trim($request->getUri()->getPath(), '/');
        $mustChangePassword = (int) session('must_change_password') === 1;
        $allowedPaths = ['change-password', 'logout'];

        if ($mustChangePassword && ! in_array($path, $allowedPaths, true)) {
            return redirect()->to('/change-password')->with('error', 'You must change your temporary password first.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
