START TRANSACTION;

-- 1. Clear service dependents
DELETE FROM assignments WHERE service_id IN (SELECT service_id FROM services);
DELETE FROM service_logs WHERE service_id IN (SELECT service_id FROM services);

-- 2. Delete services
DELETE FROM services;

-- 3. Clear package dependents (order matters due to FKs)
-- First null out plans.version_id FK
UPDATE plans SET version_id = NULL WHERE version_id IS NOT NULL;

-- Delete plans (FK to packages)
DELETE FROM plans;

-- Delete service_applications (FK to packages)
DELETE FROM service_applications WHERE package_id IS NOT NULL;

-- Delete package_versions (FK to packages)
DELETE FROM package_versions;

-- Delete packages
DELETE FROM packages;

COMMIT;

SELECT 'services' tbl, COUNT(*) cnt FROM services
UNION ALL SELECT 'packages', COUNT(*) FROM packages
UNION ALL SELECT 'package_versions', COUNT(*) FROM package_versions
UNION ALL SELECT 'plans', COUNT(*) FROM plans
UNION ALL SELECT 'service_applications', COUNT(*) FROM service_applications
UNION ALL SELECT 'assignments', COUNT(*) FROM assignments
UNION ALL SELECT 'service_logs', COUNT(*) FROM service_logs;