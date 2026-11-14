<?php

namespace App\Controllers;

use App\Services\ReceiptPdfService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Receipt PDF for one paid payment. One URL for every role that may see a
 * receipt; who may download what is decided in ReceiptPdfService::forViewer()
 * (plan holder: own payments; Branch Admin / Staff: own branch; System Admin: all).
 */
class ReceiptController extends BaseController
{
    public function download(int $paymentId)
    {
        $service = new ReceiptPdfService();
        $roleId  = (int) session('role_id');

        $row = $service->forViewer($paymentId, [
            'role_id'   => $roleId,
            'user_id'   => (int) session('user_id'),
            'branch_id' => (int) session('branch_id'),
        ]);

        if ($row === null) {
            if ($roleId === 4) {
                return redirect()->back()->with('error', 'Receipt not found, or this payment has not been verified yet.');
            }

            throw PageNotFoundException::forPageNotFound();
        }

        return $service->respond($this->response, $row, false);
    }
}
