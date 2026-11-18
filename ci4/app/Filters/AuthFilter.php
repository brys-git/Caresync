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

        // One small primary-key lookup per request keeps the session honest:
        // a deactivated or deleted account is signed out on its very next click,
        // and a changed role or branch takes effect immediately (this filter runs
        // before the role filter).
        $current = db_connect()->table('users')
            ->select('status, role_id, branch_id, deleted_at')
            ->where('user_id', (int) session('user_id'))
            ->get()
            ->getRowArray();

        if (! $current || ! empty($current['deleted_at']) || ($current['status'] ?? 'active') !== 'active') {
            // Wipe everything the user was signed in with and move to a new session id;
            // the flash message below then lives in that fresh session.
            $session = session();
            $session->remove(array_keys($session->get()));
            $session->regenerate(true);

            return redirect()->to('/login')->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        $freshRole   = (int) $current['role_id'];
        $freshBranch = $current['branch_id'] === null ? null : (int) $current['branch_id'];
        if ($freshRole !== (int) session('role_id') || $freshBranch !== (session('branch_id') === null ? null : (int) session('branch_id'))) {
            $branchName = '';
            if ($freshBranch !== null) {
                $row = db_connect()->table('branches')->select('branch_name')->where('branch_id', $freshBranch)->get()->getRowArray();
                $branchName = (string) ($row['branch_name'] ?? '');
            }
            $roleRow = db_connect()->table('roles')->select('role_name')->where('role_id', $freshRole)->get()->getRowArray();
            session()->set([
                'role_id'     => $freshRole,
                'branch_id'   => $freshBranch,
                'branch_name' => $branchName,
                'role_name'   => (string) ($roleRow['role_name'] ?? ''),
            ]);
        }

        // $request->getUri()->getPath() returns the path CI4 computed relative
        // to app.baseURL - when the app is served from anywhere that doesn't
        // exactly match baseURL (a different host/port during development,
        // or if baseURL is ever off in a real deployment), it comes back as
        // e.g. "caresync/ci4/public/index.php/change-password" instead of
        // "change-password", so the check below never matches and a user
        // with a temporary password gets redirect-looped on this exact page
        // and can never log in. IncomingRequest::getPath() is the path CI4
        // itself already used to route this request, so it's always right.
        $path = trim(
            $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request->getPath() : $request->getUri()->getPath(),
            '/'
        );
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
