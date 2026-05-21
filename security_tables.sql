-- Add security tracking to users table
ALTER TABLE users ADD COLUMN (
    failed_login_attempts INT DEFAULT 0,
    last_failed_login TIMESTAMP NULL,
    locked_until TIMESTAMP NULL,
    two_factor_enabled TINYINT(1) DEFAULT 0,
    two_factor_secret VARCHAR(255) NULL,
    ip_address_created VARCHAR(45),
    ip_address_last_login VARCHAR(45),
    INDEX idx_locked_until (locked_until),
    INDEX idx_two_factor (two_factor_enabled)
);

-- Create rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
    limit_id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(100) NOT NULL,
    attempt_count INT DEFAULT 1,
    first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_blocked TINYINT(1) DEFAULT 0,
    blocked_until TIMESTAMP NULL,
    UNIQUE KEY idx_ip_action (ip_address, action),
    INDEX idx_blocked_status (is_blocked),
    INDEX idx_blocked_until (blocked_until)
);

-- Create session management table
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, session_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);

-- Create API keys table
CREATE TABLE IF NOT EXISTS api_keys (
    key_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    ip_whitelist JSON,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
);
