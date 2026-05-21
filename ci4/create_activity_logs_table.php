<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        log_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        user_id INT UNSIGNED NULL COMMENT 'User who performed the action',
        action VARCHAR(100) NOT NULL COMMENT 'Action performed',
        module VARCHAR(50) NOT NULL COMMENT 'Module affected',
        target_id INT UNSIGNED NULL COMMENT 'ID of the record affected',
        description TEXT NULL COMMENT 'Detailed description',
        old_values JSON NULL COMMENT 'Previous values',
        new_values JSON NULL COMMENT 'New values',
        ip_address VARCHAR(45) NULL COMMENT 'IP address',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_id (user_id),
        KEY idx_module (module),
        KEY idx_target_id (target_id),
        KEY idx_created_at (created_at)
    )";
    
    $db->exec($sql);
    echo "SUCCESS: activity_logs table created!" . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
