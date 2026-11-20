<?php

namespace App\Controllers;

use App\Services\GcashReviewService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The GCash review screen: the queue, one page per payment (screenshot on the left, the facts and the checklist on the
 * right), the decision, and the screenshot itself. Reachable by a Branch Admin (any GCash payment of their branch) and by a
 * collector (Collector role or Staff with "can collect") for the payments made to THEIR GCash account. Every call checks
 * that again in GcashReviewService::canReview - the page, the image and the decision.
 */
class GcashReviewController extends BaseController
{
    private GcashReviewService $svc;

    public function __construct()
    {
        $this->svc = new GcashReviewService();
    }

    private function me(): array
    {
        $uid = (int) session('user_id');
        $role = (int) session('role_id');
        $branch = (int) session('branch_id');
        if ($branch <= 0) {
            $branch = (int) (db_connect()->table('users')->select('branch_id')->where('user_id', $uid)->get(1)->getRowArray()['branch_id'] ?? 0);
        }

        return [$uid, $role, $branch];
    }

    private function layout(): string
    {
        return match ((int) session('role_id')) {
            2 => 'layouts/branch_admin',
            3 => 'layouts/staff',
            default => 'layouts/collector',
        };
    }

    private function allowedRole(): bool
    {
        [$uid, $role] = $this->me();

        return $role === 2 || ($role >= 3 && can_collect($uid));
    }

    public function index(): ResponseInterface|string
    {
        if (! $this->allowedRole()) {
            return redirect()->to('/unauthorized');
        }
        [$uid, $role, $branch] = $this->me();

        return view('gcash_review/queue', [
            'role_layout' => $this->layout(),
            'page_title' => 'GCash review',
            'page_sub' => 'Client GCash payments waiting for you to verify, oldest first.',
            'rows' => $this->svc->queue($uid, $role, $branch),
        ]);
    }

    public function show(int $id): ResponseInterface|string
    {
        if (! $this->allowedRole()) {
            return redirect()->to('/unauthorized');
        }
        [$uid, $role, $branch] = $this->me();
        $p = $this->svc->detail($id, $uid, $role, $branch);
        if ($p === null) {
            return redirect()->to('/gcash-review')->with('error', 'That payment is not waiting for your review.');
        }

        return view('gcash_review/review', [
            'role_layout' => $this->layout(),
            'page_title' => 'Review GCash payment',
            'page_sub' => 'Check the screenshot against your own GCash app.',
            'p' => $p,
            'checklist' => GcashReviewService::CHECKLIST,
            'reasons' => GcashReviewService::REJECT_REASONS,
        ]);
    }

    public function decide(int $id): ResponseInterface
    {
        if (! $this->allowedRole()) {
            return redirect()->to('/unauthorized');
        }
        [$uid, $role, $branch] = $this->me();
        $r = $this->svc->decide($id, $uid, $role, $branch, $this->request->getPost());
        if (! $r['ok']) {
            return redirect()->to('/gcash-review/' . $id)->withInput()->with('error', $r['message']);
        }

        return redirect()->to('/gcash-review')->with('success', $r['message']);
    }

    public function image(int $id)
    {
        if (! $this->allowedRole()) {
            return redirect()->to('/unauthorized');
        }
        [$uid, $role, $branch] = $this->me();
        $p = $this->svc->detail($id, $uid, $role, $branch);
        if ($p === null || ! $p['has_proof']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $full = WRITEPATH . 'uploads/payment-proofs/' . basename((string) $p['proof_image']);
        $mime = (string) mime_content_type($full);
        if (! str_starts_with($mime, 'image/')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->setHeader('Content-Type', $mime)->setHeader('X-Content-Type-Options', 'nosniff')->setHeader('Cache-Control', 'private, no-store')->setBody((string) file_get_contents($full));
    }
}
