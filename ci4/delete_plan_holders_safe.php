<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tableExists = function (PDO $db, string $table): bool {
        $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'kaagapay_db' AND table_name = ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    };

    $planHolderIds = $db->query('SELECT plan_holder_id FROM plan_holders')->fetchAll(PDO::FETCH_COLUMN);
    $userIds = $db->query('SELECT user_id FROM users WHERE is_plan_holder = 1')->fetchAll(PDO::FETCH_COLUMN);

    if (empty($planHolderIds) && empty($userIds)) {
        echo "No plan holder records or plan holder users found. Nothing to delete." . PHP_EOL;
        exit(0);
    }

    $planHolderIdsList = $planHolderIds ? implode(',', array_map('intval', $planHolderIds)) : '0';
    $userIdsList = $userIds ? implode(',', array_map('intval', $userIds)) : '0';

    echo "Deleting client data for plan_holder_ids: " . ($planHolderIds ? $planHolderIdsList : 'none') . PHP_EOL;
    echo "Deleting client users for user_ids: " . ($userIds ? $userIdsList : 'none') . PHP_EOL;

    $db->beginTransaction();

    if ($tableExists($db, 'service_application_documents')) {
        $db->exec("DELETE FROM service_application_documents WHERE application_id IN (SELECT application_id FROM service_applications WHERE plan_holder_id IN ($planHolderIdsList))");
    }

    if ($tableExists($db, 'service_applications')) {
        $db->exec("DELETE FROM service_applications WHERE plan_holder_id IN ($planHolderIdsList)");
    }

    if ($tableExists($db, 'service_balance_payments')) {
        $db->exec("DELETE FROM service_balance_payments WHERE service_balance_id IN (SELECT service_balance_id FROM service_balances WHERE plan_holder_id IN ($planHolderIdsList))");
    }

    if ($tableExists($db, 'service_balances')) {
        $db->exec("DELETE FROM service_balances WHERE plan_holder_id IN ($planHolderIdsList)");
    }

    if ($tableExists($db, 'payments')) {
        $db->exec("DELETE FROM payments WHERE plan_id IN (SELECT plan_id FROM plans WHERE plan_holder_id IN ($planHolderIdsList))");
    }

    if ($tableExists($db, 'plans')) {
        $db->exec("DELETE FROM plans WHERE plan_holder_id IN ($planHolderIdsList)");
    }

    if ($tableExists($db, 'beneficiaries')) {
        $db->exec("DELETE FROM beneficiaries WHERE plan_holder_id IN ($planHolderIdsList)");
    }

    if ($tableExists($db, 'plan_holders')) {
        $db->exec("DELETE FROM plan_holders WHERE plan_holder_id IN ($planHolderIdsList)");
    }

    if ($tableExists($db, 'users')) {
        $db->exec("DELETE FROM users WHERE is_plan_holder = 1");
    }

    $db->commit();
    echo "Client accounts and related records have been deleted successfully." . PHP_EOL;

    $remainingHolders = $db->query('SELECT COUNT(*) FROM plan_holders')->fetchColumn();
    $remainingPlanUsers = $db->query('SELECT COUNT(*) FROM users WHERE is_plan_holder = 1')->fetchColumn();
    echo "Remaining plan_holders: $remainingHolders" . PHP_EOL;
    echo "Remaining plan holder users: $remainingPlanUsers" . PHP_EOL;
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
