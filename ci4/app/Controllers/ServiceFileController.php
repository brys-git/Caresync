<?php

namespace App\Controllers;

use App\Services\ServiceProcessService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Serves the files of the service process (a task's document / photo, the family's signed confirmation) from writable/.
 * They are never public: the Branch Admin of that service's branch can open them, and a staff member can open the
 * document of THEIR OWN task. Anyone else gets "not found".
 */
class ServiceFileController extends BaseController
{
    public function taskDocument(int $taskId)
    {
        $svc = new ServiceProcessService();
        $t = $svc->task($taskId);
        $service = $t ? $svc->service((int) $t['service_id']) : null;
        if ($t === null || $service === null || empty($t['document_path']) || ! $this->mayOpen($service, (int) $t['assigned_staff_id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->send((string) $t['document_path'], (string) ($t['document_name'] ?: 'document'));
    }

    public function confirmation(int $serviceId)
    {
        $svc = new ServiceProcessService();
        $service = $svc->service($serviceId);
        $plan = $svc->plan($serviceId);
        if ($service === null || empty($plan['confirmed_photo_path']) || ! $this->mayOpen($service, 0)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->send((string) $plan['confirmed_photo_path'], 'family-confirmation');
    }

    private function mayOpen(array $service, int $ownerStaffId): bool
    {
        $role = (int) session('role_id');
        $branch = (int) session('branch_id');
        if ($branch <= 0 || (int) $service['branch_id'] !== $branch) {
            return false;
        }

        return $role === 2 || ($role === 3 && $ownerStaffId > 0 && $ownerStaffId === (int) session('user_id'));
    }

    private function send(string $relativePath, string $name)
    {
        $full = WRITEPATH . ltrim(str_replace(['..', '\\'], ['', '/'], $relativePath), '/');
        if (! is_file($full) || ! str_starts_with(realpath($full) ?: '', realpath(WRITEPATH . 'uploads') ?: '#')) {
            throw PageNotFoundException::forPageNotFound();
        }
        $mime = (string) (function_exists('mime_content_type') ? mime_content_type($full) : 'application/octet-stream');

        return $this->response->setHeader('Content-Type', $mime)->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name) . '"')->setBody((string) file_get_contents($full));
    }
}
