<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Route smoke test.
 *
 * Boots the real app in-process and GETs every static (non-parameterized) route
 * once, with a real session user for the role each route protects. A route that
 * returns a 500 (or throws) is a regression the test fails on.
 *
 * Run:  php spark test  --filter RouteSmokeTest
 *  or:  vendor/bin/phpunit --filter RouteSmokeTest
 */
class RouteSmokeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Roles to look up a real active user for when seeding the session. */
    private const ROLES = [1, 2, 3, 4];

    public function testAllStaticGetRoutesRespond(): void
    {
        $routes = service('routes');
        $routes->loadRoutes();

        $paths = array_keys($routes->getRoutes('GET'));
        sort($paths);

        // Cache one active user per role so every protected route gets a valid session.
        // Roles 2/3 are branch-scoped (many controllers 404 without a branch), so prefer
        // a user that has one; role 4 routes behave like the client portal when the
        // account is a plan holder.
        $usersByRole = [];
        foreach (self::ROLES as $role) {
            $builder = db_connect()->table('users')
                ->where('role_id', $role)
                ->where('status', 'active');

            if (in_array($role, [2, 3], true)) {
                $builder->where('branch_id IS NOT NULL');
                $builder->where('branch_id !=', 0);
            }
            if ($role === 4) {
                $builder->where('is_plan_holder', 1);
            }

            $user = $builder->limit(1)->get()->getRowArray();
            $usersByRole[$role] = $user ?: null;
        }

        $failures = [];
        $tested = 0;

        foreach ($paths as $path) {
            if ($this->skippablePath($path)) {
                continue;
            }

            $role = $this->roleForPath($path, $routes);

            if ($role === null) {
                $session = [];
            } elseif ($usersByRole[$role] === null) {
                $failures[] = "[SKIP] no active role-{$role} user to test route: {$path}";
                continue;
            } else {
                $u = $usersByRole[$role];
                $session = [
                    'user_id'   => (int) $u['user_id'],
                    'role_id'   => (int) $u['role_id'],
                    'branch_id' => $u['branch_id'] === null ? null : (int) $u['branch_id'],
                    'is_plan_holder' => (int) ($u['is_plan_holder'] ?? 0),
                    'must_change_password' => 0,
                    'user_name' => trim(((string) ($u['first_name'] ?? '')) . ' ' . ((string) ($u['last_name'] ?? ''))),
                    'email'     => (string) ($u['email'] ?? ''),
                ];
            }

            $tested++;

            try {
                $result = $this->withSession($session)->get($path);
                $status = (int) $result->status();

                if ($status >= 500) {
                    $failures[] = "[{$status}] {$path} (role {$role})";
                }
            } catch (\Throwable $e) {
                $failures[] = "[EXCEPTION] {$path} (role {$role}): " . $e->getMessage();
            }
        }

        fwrite(STDERR, PHP_EOL . "Route smoke test: {$tested} routes hit" . PHP_EOL);
        fwrite(STDERR, 'Failures: ' . count($failures) . PHP_EOL);
        if ($failures !== []) {
            fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
        }

        $this->assertSame([], $failures, 'GET routes returning 500/exception:' . PHP_EOL . implode(PHP_EOL, $failures));
    }

    /**
     * Routes that can't (or shouldn't) be smoke-tested as plain GETs:
     * parameterized handlers, the empty home path, and binary/streaming responses.
     */
    private function skippablePath(string $path): bool
    {
        $path = ltrim($path, '/');

        if ($path === '' || $path === '/') {
            return true;
        }

        // Parameterized route — CI4 exposes the compiled regex as the key, e.g.
        // "client-management/edit/([0-9]+)" or "api/address/cities/([a-zA-Z0-9]+)".
        if (preg_match('#[()\[\]{}\$]#', $path)) {
            return true;
        }

        // Streaming / binary handlers are validated separately (route exists, method exists).
        if (preg_match('#/(export|download|id-document|pdf|print)[/]?|(?:^|/)(?:export|download)[^/]*$#i', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Determine which role a route requires, from the route's filter option when
     * available, otherwise by URI prefix convention.
     */
    private function roleForPath(string $path, $routes): ?int
    {
        $options = $routes->getRoutesOptions(ltrim($path, '/'), 'GET');
        $filter = $options['filter'] ?? null;

        if (is_array($filter)) {
            $filter = implode(',', $filter);
        }

        if (is_string($filter) && preg_match('/role:(\d+)/', $filter, $m)) {
            return (int) $m[1];
        }

        // Fallback: URI prefix convention for routes guarded by the plain 'auth' filter.
        if ($path === '' || str_starts_with($path, 'admin') || str_starts_with($path, 'plan-holders') || str_starts_with($path, 'approvals') || str_starts_with($path, 'payment-monitoring')) {
            return 1;
        }
        if (str_starts_with($path, 'branch-admin')) {
            return 2;
        }
        if (str_starts_with($path, 'staff')) {
            return 3;
        }
        if (str_starts_with($path, 'client') || str_starts_with($path, 'payment') || str_starts_with($path, 'plan-registration') || str_starts_with($path, 'plan-holder')) {
            return 4;
        }

        return null; // public route — no session needed
    }
}
