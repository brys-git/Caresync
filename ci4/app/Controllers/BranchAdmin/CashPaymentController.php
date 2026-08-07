<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CashPaymentController
 * 
 * Handles cash payment recording by branch admin before client submits in the system
 * Client pays cash at branch → admin records receipt number → client later verifies it
 */
class CashPaymentController extends BaseController
{
    /**
     * Show cash payment recording form
     */
    public function recordPaymentForm(): ResponseInterface|string
    {
        $branch_id = (int) session('branch_id');
        if ($branch_id <= 0) {
            return redirect()->to('/admin/dashboard')->with('error', 'Invalid branch.');
        }

        $db = db_connect();

        // Get branch clients for smart search
        $clients = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.status AS plan_holder_status, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branch_id)
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Get approval queue (recent cash payment records)
        $approvalQueue = $db->table('cash_payment_records cpr')
            ->select('cpr.*, cpr.client_name, cpr.receipt_number, cpr.verified, cpr.created_at')
            ->where('cpr.branch_id', $branch_id)
            ->orderBy('cpr.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return view('branch_admin/cash_payment_record', [
            'branch_id' => $branch_id,
            'role_layout' => 'layouts/branch_admin',
            'page_title' => null,
            'clients' => $clients,
            'approval_queue' => $approvalQueue,
            'monthly_fee' => 240.0,
        ]);
    }

    /**
     * Save cash payment record
     */
    public function savePaymentRecord()
    {
        $branch_id = (int) session('branch_id');
        if ($branch_id <= 0) {
            return redirect()->to('/admin/dashboard')->with('error', 'Invalid branch.');
        }

        $clientName = trim((string) $this->request->getPost('client_name'));
        $monthsCovered = max(1, (int) $this->request->getPost('months_covered'));
        $receiptNumber = trim((string) $this->request->getPost('receipt_number'));
        $paymentType = strtolower(trim((string) $this->request->getPost('payment_type')));
        $monthlyFee = 240.0; // Hardcoded for now, could be dynamic

        if (empty($clientName) || empty($receiptNumber)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Client name and receipt number are required.');
        }

        // Check if receipt already exists
        $existing = db_connect()->table('cash_payment_records')
            ->where('receipt_number', $receiptNumber)
            ->get()
            ->getRowArray();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This receipt number already exists. Receipt: ' . esc($receiptNumber));
        }

        // Record the cash payment
        $db = db_connect();
        $amount = $monthlyFee * $monthsCovered;
        $paymentData = [
            'branch_id' => $branch_id,
            'client_name' => $clientName,
            'months_covered' => $monthsCovered,
            'amount' => $amount,
            'receipt_number' => $receiptNumber,
            'recorded_by' => (int) session('user_id'),
            'recorded_date' => date('Y-m-d'),
            'verified' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        log_message('debug', '[CashPaymentSave] Inserting: branch=' . $branch_id . ' receipt=' . $receiptNumber);

        $inserted = $db->table('cash_payment_records')->insert($paymentData);

        if (!$inserted) {
            $error = $db->error();
            log_message('error', '[CashPaymentSave] Insert failed: ' . json_encode($error));
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record payment. Error: ' . (isset($error['message']) ? $error['message'] : 'Unknown error'));
        }

        log_message('debug', '[CashPaymentSave] Success: receipt=' . $receiptNumber . ' for ' . $clientName);

        $label = $paymentType === 'initial' ? 'Initial payment' : 'Payment';

        return redirect()->to('/branch-admin/cash-payment-record')
            ->with('success', ucfirst($label) . ' recorded. Receipt: ' . esc($receiptNumber) . ' for ' . esc($clientName));
    }

    /**
     * View recorded cash payments
     */
    public function viewPayments(): ResponseInterface|string
    {
        $branch_id = (int) session('branch_id');
        if ($branch_id <= 0) {
            return redirect()->to('/admin/dashboard')->with('error', 'Invalid branch.');
        }

        $payments = db_connect()->table('cash_payment_records')
            ->where('branch_id', $branch_id)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('branch_admin/cash_payments_list', [
            'payments' => $payments,
        ]);
    }
}
