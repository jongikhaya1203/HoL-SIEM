-- Update Remote Support module to active status
UPDATE modules
SET status = 'active',
    implementation_level = 'full',
    url = 'modules/remote_support.php',
    description = 'Comprehensive remote IT support with desktop sharing, file transfer, and system management'
WHERE module_code = 'DRE';

-- If the update didn't affect any rows, insert the module
INSERT INTO modules (module_code, module_name, category, description, icon, status, implementation_level, url, display_order, enabled)
SELECT 'DRE', 'Remote Support', 'service_management', 'Comprehensive remote IT support with desktop sharing, file transfer, and system management', '🖥️', 'active', 'full', 'modules/remote_support.php', 16, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE module_code = 'DRE');
