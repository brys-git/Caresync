-- Payment status change trigger
DELIMITER //
CREATE TRIGGER trg_payment_status_change
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO payment_transactions (payment_id, old_status, new_status, transitioned_at)
        VALUES (NEW.payment_id, OLD.status, NEW.status, NOW());
    END IF;
END //
DELIMITER ;

-- Service status change trigger
DELIMITER //
CREATE TRIGGER trg_service_status_change
AFTER UPDATE ON services
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO service_logs (service_id, old_status, new_status, logged_at)
        VALUES (NEW.service_id, OLD.status, NEW.status, NOW());
    END IF;
END //
DELIMITER ;
