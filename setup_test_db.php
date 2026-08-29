<?php
// Recreate minimal test database with correct payment statuses

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'test_kaagapay';

try {
    // Connect to MySQL
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "=== SETTING UP TEST DATABASE ===\n\n";
    
    // Drop existing test database if it exists
    $mysqli->query("DROP DATABASE IF EXISTS test_kaagapay");
    
    // Create test database
    $mysqli->query("CREATE DATABASE test_kaagapay CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $mysqli->select_db('test_kaagapay');
    
    echo "✓ Created test database\n";
    
    // Create tables with corrected payment status enum
    $mysqli->query("CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(100),
        is_plan_holder TINYINT,
        account_status VARCHAR(20),
        branch_id INT,
        role_id INT
    )");
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS plan_holders (
        plan_holder_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        branch_id INT,
        status VARCHAR(50),
        unique_identifier VARCHAR(100)
    )");
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS plans (
        plan_id INT PRIMARY KEY AUTO_INCREMENT,
        plan_holder_id INT,
        package_id INT,
        monthly_fee DECIMAL(10,2),
        months_paid INT,
        start_date DATE,
        status VARCHAR(50),
        next_due_date DATE,
        payment_coverage_until DATE,
        overdue_months INT DEFAULT 0,
        membership_state VARCHAR(50) DEFAULT 'active',
        legacy_remaining_balance DECIMAL(10,2),
        version_id INT
    )");
    
    // CORRECTED: Enum with new status values
    $mysqli->query("CREATE TABLE IF NOT EXISTS payments (
        payment_id INT PRIMARY KEY AUTO_INCREMENT,
        plan_id INT,
        amount DECIMAL(10,2),
        months_covered INT DEFAULT 1,
        payment_date DATE,
        payment_method VARCHAR(50),
        reference_number VARCHAR(100),
        official_receipt_number VARCHAR(100),
        status ENUM('awaiting_verification', 'verified', 'rejected') DEFAULT 'awaiting_verification',
        received_by INT,
        verified_by INT,
        verified_at DATETIME,
        remarks TEXT,
        branch_id INT
    )");
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS activity_logs (
        log_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(100),
        module VARCHAR(50),
        target_id INT,
        description TEXT,
        old_values LONGTEXT,
        new_values LONGTEXT,
        old_status VARCHAR(50),
        new_status VARCHAR(50),
        user_role VARCHAR(50),
        device VARCHAR(100),
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "✓ Created tables with corrected payment statuses\n";
    
    // Insert test data
    $mysqli->query("INSERT INTO users (user_id, first_name, last_name, email, is_plan_holder, account_status, branch_id, role_id) 
                   VALUES (18, 'Maria', 'Santos', 'maria@test.com', 1, 'verified', 1, 4)");
    
    $mysqli->query("INSERT INTO plan_holders (plan_holder_id, user_id, branch_id, status, unique_identifier) 
                   VALUES (10, 18, 1, 'active', 'PH-10001')");
    
    $mysqli->query("INSERT INTO plans (plan_id, plan_holder_id, package_id, monthly_fee, months_paid, start_date, status, next_due_date, payment_coverage_until, overdue_months, membership_state, legacy_remaining_balance, version_id) 
                   VALUES (5, 10, 1, 240.00, 1, '2026-05-05', 'active', '2026-06-12', '2026-06-11', 0, 'active', 0, 1)");
    
    // Insert payments with NEW STATUSES
    $mysqli->query("INSERT INTO payments (payment_id, plan_id, amount, months_covered, payment_date, payment_method, status, received_by, verified_by, verified_at, remarks, branch_id) 
                   VALUES (5, 5, 240.00, 1, '2026-05-05', 'cash', 'verified', 6, 6, '2026-05-05 10:00:00', 'Initial payment verified', 1)");
    
    $mysqli->query("INSERT INTO payments (payment_id, plan_id, amount, months_covered, payment_date, payment_method, status, received_by, remarks, branch_id) 
                   VALUES (6, 5, 240.00, 1, '2026-05-06', 'gcash', 'awaiting_verification', 3, 'GCash reference: GC123456', 1)");
    
    echo "✓ Inserted test data with corrected payment statuses\n";
    
    // Verify
    echo "\n=== VERIFICATION ===\n";
    $result = $mysqli->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['status'] . ": " . $row['count'] . "\n";
    }
    
    echo "\n✓ Test database ready for testing\n";
    echo "Database: test_kaagapay\n";
    echo "User: root\n";
    echo "Password: (empty)\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
