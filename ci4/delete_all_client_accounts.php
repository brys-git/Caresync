<?php
$db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$planHolderIds = $db->query('SELECT plan_holder_id FROM plan_holders')->fetchAll(PDO::FETCH_COLUMN);
if (empty($planHolderIds)) {
    echo "No plan holders found. Nothing to delete.\n";
    exit(0);
}

$planHolderIdsList = implode(',', array_map('intval', $planHolderIds));
$userIds = $db->query('SELECT DISTINCT user_id FROM plan_holders')->fetchAll(PDO::FETCH_COLUMN);
$usersList = implode(',', array_map('intval', $userIds));

try {
    $db->beginTransaction();

    $planIds = $db->query('SELECT plan_id FROM plans WHERE plan_holder_id IN (' . $planHolderIdsList . ')')->fetchAll(PDO::FETCH_COLUMN);
    $planIdsList = empty($planIds) ? 'NULL' : implode(',', array_map('intval', $planIds));

    $deleted = [];
    $deleted['payments'] = $db->exec('DELETE FROM payments WHERE plan_id IN (' . $planIdsList . ')');
    $deleted['plans'] = $db->exec('DELETE FROM plans WHERE plan_holder_id IN (' . $planHolderIdsList . ')');
    $deleted['beneficiaries'] = $db->exec('DELETE FROM beneficiaries WHERE plan_holder_id IN (' . $planHolderIdsList . ')');
    $deleted['service_applications'] = $db->exec('DELETE FROM service_applications WHERE plan_holder_id IN (' . $planHolderIdsList . ')');
    $deleted['services'] = $db->exec('DELETE FROM services WHERE plan_holder_id IN (' . $planHolderIdsList . ')');
    $deleted['plan_holders'] = $db->exec('DELETE FROM plan_holders WHERE plan_holder_id IN (' . $planHolderIdsList . ')');
    $deleted['users'] = 0;
    if (!empty($usersList)) {
        $deleted['users'] = $db->exec('DELETE FROM users WHERE user_id IN (' . $usersList . ') AND is_plan_holder = 1');
    }

    $db->commit();
    echo "Deleted records:\n";
    foreach ($deleted as $table => $count) {
        echo sprintf(" - %s: %d\n", $table, $count);
    }
} catch (Exception $e) {
    $db->rollBack();
    echo "Deletion failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>