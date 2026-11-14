<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ActivityLogService;

/**
 * One-time credentials files written by `php spark import:aclan-clients`.
 * They hold plain temporary passwords, so: System Admin only, download once,
 * then delete. Files live in writable/imports (not web-accessible).
 */
class ImportCredentialsController extends BaseController
{
    private const PATTERN = '/^faustino_aclan_credentials_[0-9_-]+\.csv$/';

    public function index(): string
    {
        $this->ensureAdminAccess();

        $files = [];
        foreach (glob(WRITEPATH . 'imports' . DIRECTORY_SEPARATOR . 'faustino_aclan_credentials_*.csv') ?: [] as $path) {
            if (preg_match(self::PATTERN, basename($path))) {
                $files[] = ['name' => basename($path), 'modified' => filemtime($path), 'size' => filesize($path), 'rows' => max(0, count(file($path)) - 1)];
            }
        }
        usort($files, static fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return view('admin/imports/credentials', [
            'role_layout' => 'layouts/admin',
            'page_title'  => 'Import credentials',
            'page_sub'    => 'One-time login details for imported clients.',
            'files'       => $files,
            'rows'        => null,
            'file'        => null,
        ]);
    }

    /** Printable list: one slip-style row per login. */
    public function show(string $name): string
    {
        $this->ensureAdminAccess();
        $path = $this->pathFor($name);
        $rows = [];
        $h = fopen($path, 'rb');
        fgetcsv($h);
        while (($r = fgetcsv($h)) !== false) {
            $rows[] = $r;
        }
        fclose($h);

        return view('admin/imports/credentials', [
            'role_layout' => 'layouts/admin',
            'page_title'  => 'Import credentials',
            'page_sub'    => 'Print, hand out, then delete this file.',
            'files'       => [],
            'rows'        => $rows,
            'file'        => $name,
        ]);
    }

    public function download(string $name)
    {
        $this->ensureAdminAccess();
        $path = $this->pathFor($name);
        (new ActivityLogService())->log((int) session('user_id'), 'downloaded', 'bulk_import', 1, 'Downloaded import credentials file ' . $name);

        return $this->response->download($path, null)->setFileName($name);
    }

    public function delete(string $name)
    {
        $this->ensureAdminAccess();
        $path = $this->pathFor($name);
        @unlink($path);
        (new ActivityLogService())->log((int) session('user_id'), 'deleted', 'bulk_import', 1, 'Deleted import credentials file ' . $name);

        return redirect()->to('/admin/imports/credentials')->with('success', 'The credentials file was deleted.');
    }

    private function pathFor(string $name): string
    {
        $path = WRITEPATH . 'imports' . DIRECTORY_SEPARATOR . $name;
        if (! preg_match(self::PATTERN, $name) || ! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $path;
    }

    private function ensureAdminAccess(): void
    {
        if ((int) session()->get('role_id') !== 1) {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
