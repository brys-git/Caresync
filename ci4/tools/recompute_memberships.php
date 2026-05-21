<?php
// recompute_memberships.php
// Usage: php recompute_memberships.php

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'kaagapay_db';
$port = 3306;

date_default_timezone_set('Asia/Manila');
$mysqli = new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_error) {
    echo "DB connect error: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

echo "Connected to DB {$dbname}\n";

// Check optional columns
function columnExists($mysqli, $db, $table, $column) {
    $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $db, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return ((int)$res['cnt']) > 0;
}

$paymentsHasMonths = columnExists($mysqli, $dbname, 'payments', 'months_covered');
$plansHasCoverage = columnExists($mysqli, $dbname, 'plans', 'payment_coverage_until');
$plansHasNextDue = columnExists($mysqli, $dbname, 'plans', 'next_due_date');
$plansHasOverdue = columnExists($mysqli, $dbname, 'plans', 'overdue_months');
$plansHasMembershipState = columnExists($mysqli, $dbname, 'plans', 'membership_state');

echo "Schema: payments.months_covered=" . ($paymentsHasMonths ? 'YES' : 'NO') . ", plans.payment_coverage_until=" . ($plansHasCoverage ? 'YES' : 'NO') . ", plans.next_due_date=" . ($plansHasNextDue ? 'YES' : 'NO') . "\n";

// Default monthly fee
// Try to read monthly fee from membership_programs if the table exists
$monthlyFeeDefault = 240.0;
$tblRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($dbname) . "' AND TABLE_NAME = 'membership_programs'");
if ($tblRes && ($tblRow = $tblRes->fetch_assoc()) && (int)$tblRow['cnt'] > 0) {
    $res = $mysqli->query("SELECT monthly_fee FROM membership_programs WHERE is_active = 1 LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $monthlyFeeDefault = (float)$row['monthly_fee'];
    }
}

echo "Using monthly fee = {$monthlyFeeDefault}\n";

// Fetch all plans (avoid selecting optional columns if they don't exist)
$selectCols = 'plan_id, plan_holder_id, start_date, monthly_fee, months_paid';
if ($plansHasCoverage) {
    $selectCols .= ', payment_coverage_until';
}
$plansRes = $mysqli->query("SELECT " . $selectCols . " FROM plans");
if (! $plansRes) {
    echo "Failed to fetch plans: " . $mysqli->error . PHP_EOL;
    exit(1);
}

$updated = 0;
while ($plan = $plansRes->fetch_assoc()) {
    $planId = (int)$plan['plan_id'];
    $startDate = $plan['start_date'] ?: date('Y-m-d');
    $monthlyFee = (float) ($plan['monthly_fee'] ?: $monthlyFeeDefault);

    // compute months paid from payments
    if ($paymentsHasMonths) {
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(months_covered),0) AS sum_months FROM payments WHERE plan_id = ? AND (status IN ('paid','approved') OR verified_at IS NOT NULL)");
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $sum = $stmt->get_result()->fetch_assoc();
        $monthsPaid = (int)($sum['sum_months'] ?? 0);
    } else {
        // infer from amounts
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(amount),0) AS total_amount FROM payments WHERE plan_id = ? AND (status IN ('paid','approved') OR verified_at IS NOT NULL)");
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $sum = $stmt->get_result()->fetch_assoc();
        $totalAmount = (float)($sum['total_amount'] ?? 0.0);
        if ($monthlyFee > 0) {
            $monthsPaid = (int) floor($totalAmount / $monthlyFee);
        } else {
            $monthsPaid = 0;
        }
    }

    $monthsPaid = max(0, $monthsPaid);

    // compute logical coverageUntil = start_date + monthsPaid months (used for overdue calculation)
    $coverageUntil = null;
    if ($monthsPaid > 0) {
        $dt = new DateTime($startDate);
        $dt->modify("+{$monthsPaid} months");
        $coverageUntil = $dt->format('Y-m-d');
    }

    // compute overdue months and state
    $today = new DateTime();
    $overdueMonths = 0;
    $newState = 'active';
    if ($coverageUntil) {
        $cov = new DateTime($coverageUntil);
        if ($today > $cov) {
            $interval = $cov->diff($today);
            $overdueMonths = ($interval->y * 12) + $interval->m;
            if ($overdueMonths >= 6) {
                $newState = 'suspended';
            } elseif ($overdueMonths >= 3) {
                $newState = 'delinquent';
            } else {
                $newState = 'active';
            }
        } else {
            $overdueMonths = 0;
            $newState = 'active';
        }
    }

    // prepare update (only include columns that exist)
    $updates = [];
    if (columnExists($mysqli, $dbname, 'plans', 'months_paid')) {
        $updates[] = "months_paid = " . $mysqli->real_escape_string((string)$monthsPaid);
    }
    if ($plansHasOverdue) {
        $updates[] = "overdue_months = " . $mysqli->real_escape_string((string)$overdueMonths);
    }
    if ($plansHasMembershipState) {
        $updates[] = "membership_state = '" . $mysqli->real_escape_string($newState) . "'";
    }
        if ($plansHasCoverage && $coverageUntil) {
            $updates[] = "payment_coverage_until = '" . $mysqli->real_escape_string($coverageUntil) . "'";
        }
        if ($plansHasNextDue && $coverageUntil) {
        $dt = new DateTime($coverageUntil);
        $dt->modify('+1 day');
        $nextDue = $dt->format('Y-m-d');
        $updates[] = "next_due_date = '" . $mysqli->real_escape_string($nextDue) . "'";
    }

    $sql = "UPDATE plans SET " . implode(', ', $updates) . " WHERE plan_id = " . $planId;
    if ($mysqli->query($sql)) {
        $updated++;
        echo "Updated plan {$planId}: months_paid={$monthsPaid}, coverage_until=" . ($coverageUntil?:'NULL') . ", state={$newState}, overdue={$overdueMonths}\n";
    } else {
        echo "Failed to update plan {$planId}: " . $mysqli->error . "\n";
    }
}

echo "\nDone. Plans updated: {$updated}\n";
$mysqli->close();

return 0;
