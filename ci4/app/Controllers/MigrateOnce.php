<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * One-time database update for hosts without a command line (InfinityFree).
 *
 * It does nothing, and looks like a missing page, unless the server's .env has
 *     app.migrateKey = 'a-long-random-secret'
 * and the address contains that same secret:
 *     https://yourdomain/migrate/a-long-random-secret
 * Run it once after uploading, then DELETE the app.migrateKey line from .env.
 */
class MigrateOnce extends BaseController
{
    public function run(string $key = '')
    {
        $expected = (string) env('app.migrateKey', '');

        if (strlen($expected) < 16 || ! hash_equals($expected, $key)) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $migrations = \Config\Services::migrations();
            $migrations->setNamespace(null);
            $migrations->latest();
            $message = "Database is up to date.\n\nNow remove the app.migrateKey line from the server's .env file.";
            $status  = 200;
        } catch (\Throwable $e) {
            log_message('error', 'Migration run failed: ' . $e->getMessage());
            $message = 'The database update failed. Details were written to writable/logs. Do not continue until this is fixed.';
            $status  = 500;
        }

        return $this->response->setStatusCode($status)->setContentType('text/plain')->setBody($message);
    }
}
