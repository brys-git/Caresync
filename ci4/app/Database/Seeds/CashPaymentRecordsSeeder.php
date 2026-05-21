<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CashPaymentRecordsSeeder extends Seeder
{
    public function run()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS cash_payment_records (
            cash_record_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT(11) UNSIGNED NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            months_covered INT(3) DEFAULT 1,
            amount DECIMAL(10,2) NOT NULL,
            receipt_number VARCHAR(50) NOT NULL UNIQUE,
            recorded_by INT(11) UNSIGNED,
            recorded_date DATE NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            verified_date DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (cash_record_id),
            KEY idx_branch (branch_id),
            KEY idx_receipt (receipt_number),
            KEY idx_verified (verified)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $this->db->query($sql);
        echo "cash_payment_records table created successfully.\n";
    }
}
