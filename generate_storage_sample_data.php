<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/StorageScanner.php';

echo "=== Storage Optimization Sample Data Generator ===\n\n";

$scanner = new StorageScanner();
$scanId = 'SCAN-' . date('Ymd-His');

echo "Scan ID: $scanId\n";
echo "Generating sample file data...\n\n";

// Sample files with various types and duplicates
$sampleFiles = [
    // Financial documents - Confidential
    ['path' => '/shares/Finance/Q4_Results_2024.xlsx', 'name' => 'Q4_Results_2024.xlsx', 'extension' => '.xlsx', 'size' => 52428800, 'hash' => 'a1b2c3d4e5f60001', 'type' => 'Excel', 'location' => 'NAS-01', 'department' => 'Finance'],
    ['path' => '/shares/Finance/Budget_2024_Final.xlsx', 'name' => 'Budget_2024_Final.xlsx', 'extension' => '.xlsx', 'size' => 31457280, 'hash' => 'a1b2c3d4e5f60002', 'type' => 'Excel', 'location' => 'NAS-01', 'department' => 'Finance'],
    ['path' => '/shares/Finance/Payroll_Data_Dec.xlsx', 'name' => 'Payroll_Data_Dec.xlsx', 'extension' => '.xlsx', 'size' => 41943040, 'hash' => 'a1b2c3d4e5f60003', 'type' => 'Excel', 'location' => 'NAS-01', 'department' => 'Finance'],

    // HR documents - Restricted
    ['path' => '/shares/HR/Employee_Records.xlsx', 'name' => 'Employee_Records.xlsx', 'extension' => '.xlsx', 'size' => 104857600, 'hash' => 'a1b2c3d4e5f60004', 'type' => 'Excel', 'location' => 'NAS-01', 'department' => 'HR'],
    ['path' => '/shares/HR/Performance_Reviews_2024.docx', 'name' => 'Performance_Reviews_2024.docx', 'extension' => '.docx', 'size' => 20971520, 'hash' => 'a1b2c3d4e5f60005', 'type' => 'Word', 'location' => 'NAS-01', 'department' => 'HR'],

    // Customer data - Confidential
    ['path' => '/shares/Sales/Customer_Database.xlsx', 'name' => 'Customer_Database.xlsx', 'extension' => '.xlsx', 'size' => 209715200, 'hash' => 'a1b2c3d4e5f60006', 'type' => 'Excel', 'location' => 'NAS-02', 'department' => 'Sales'],
    ['path' => '/shares/Sales/Q4_Sales_Report.pdf', 'name' => 'Q4_Sales_Report.pdf', 'extension' => '.pdf', 'size' => 15728640, 'hash' => 'a1b2c3d4e5f60007', 'type' => 'PDF', 'location' => 'NAS-02', 'department' => 'Sales'],

    // Source code - Internal
    ['path' => '/shares/Development/src/Application.java', 'name' => 'Application.java', 'extension' => '.java', 'size' => 524288, 'hash' => 'a1b2c3d4e5f60008', 'type' => 'Java', 'location' => 'NAS-03', 'department' => 'Development'],
    ['path' => '/shares/Development/src/Database.py', 'name' => 'Database.py', 'extension' => '.py', 'size' => 262144, 'hash' => 'a1b2c3d4e5f60009', 'type' => 'Python', 'location' => 'NAS-03', 'department' => 'Development'],
    ['path' => '/shares/Development/src/API.js', 'name' => 'API.js', 'extension' => '.js', 'size' => 131072, 'hash' => 'a1b2c3d4e5f60010', 'type' => 'JavaScript', 'location' => 'NAS-03', 'department' => 'Development'],

    // Database backups - Confidential (DUPLICATES)
    ['path' => '/backups/production/db_backup_20241201.bak', 'name' => 'db_backup_20241201.bak', 'extension' => '.bak', 'size' => 10737418240, 'hash' => 'DUP001BACKUP', 'type' => 'Backup', 'location' => 'SAN-01', 'department' => 'IT'],
    ['path' => '/backups/archive/db_backup_20241201.bak', 'name' => 'db_backup_20241201.bak', 'extension' => '.bak', 'size' => 10737418240, 'hash' => 'DUP001BACKUP', 'type' => 'Backup', 'location' => 'SAN-02', 'department' => 'IT'],
    ['path' => '/backups/offsite/db_backup_20241201.bak', 'name' => 'db_backup_20241201.bak', 'extension' => '.bak', 'size' => 10737418240, 'hash' => 'DUP001BACKUP', 'type' => 'Backup', 'location' => 'Cloud-01', 'department' => 'IT'],

    // Marketing media - Internal (DUPLICATES)
    ['path' => '/shares/Marketing/Campaign_Video_2024.mp4', 'name' => 'Campaign_Video_2024.mp4', 'extension' => '.mp4', 'size' => 2147483648, 'hash' => 'DUP002VIDEO', 'type' => 'Video', 'location' => 'NAS-02', 'department' => 'Marketing'],
    ['path' => '/shares/Marketing/Archive/Campaign_Video_2024.mp4', 'name' => 'Campaign_Video_2024.mp4', 'extension' => '.mp4', 'size' => 2147483648, 'hash' => 'DUP002VIDEO', 'type' => 'Video', 'location' => 'NAS-02', 'department' => 'Marketing'],
    ['path' => '/shares/Marketing/Backup/Campaign_Video_2024.mp4', 'name' => 'Campaign_Video_2024.mp4', 'extension' => '.mp4', 'size' => 2147483648, 'hash' => 'DUP002VIDEO', 'type' => 'Video', 'location' => 'NAS-03', 'department' => 'Marketing'],
    ['path' => '/shares/Web/media/Campaign_Video_2024.mp4', 'name' => 'Campaign_Video_2024.mp4', 'extension' => '.mp4', 'size' => 2147483648, 'hash' => 'DUP002VIDEO', 'type' => 'Video', 'location' => 'NAS-02', 'department' => 'Marketing'],

    // Office documents (SOME DUPLICATES)
    ['path' => '/shares/Projects/Project_Plan_2024.docx', 'name' => 'Project_Plan_2024.docx', 'extension' => '.docx', 'size' => 5242880, 'hash' => 'DUP003DOC', 'type' => 'Word', 'location' => 'NAS-01', 'department' => 'Projects'],
    ['path' => '/shares/Projects/Archive/Project_Plan_2024.docx', 'name' => 'Project_Plan_2024.docx', 'extension' => '.docx', 'size' => 5242880, 'hash' => 'DUP003DOC', 'type' => 'Word', 'location' => 'NAS-01', 'department' => 'Projects'],

    // Images (DUPLICATES)
    ['path' => '/shares/Marketing/logo.png', 'name' => 'logo.png', 'extension' => '.png', 'size' => 524288, 'hash' => 'DUP004IMAGE', 'type' => 'Image', 'location' => 'NAS-02', 'department' => 'Marketing'],
    ['path' => '/shares/Web/images/logo.png', 'name' => 'logo.png', 'extension' => '.png', 'size' => 524288, 'hash' => 'DUP004IMAGE', 'type' => 'Image', 'location' => 'NAS-02', 'department' => 'Marketing'],
    ['path' => '/shares/Design/assets/logo.png', 'name' => 'logo.png', 'extension' => '.png', 'size' => 524288, 'hash' => 'DUP004IMAGE', 'type' => 'Image', 'location' => 'NAS-02', 'department' => 'Marketing'],
    ['path' => '/shares/Public/branding/logo.png', 'name' => 'logo.png', 'extension' => '.png', 'size' => 524288, 'hash' => 'DUP004IMAGE', 'type' => 'Image', 'location' => 'NAS-01', 'department' => 'Marketing'],
    ['path' => '/shares/Downloads/logo.png', 'name' => 'logo.png', 'extension' => '.png', 'size' => 524288, 'hash' => 'DUP004IMAGE', 'type' => 'Image', 'location' => 'NAS-01', 'department' => 'Marketing'],

    // Archive files
    ['path' => '/backups/file_archive_2023.zip', 'name' => 'file_archive_2023.zip', 'extension' => '.zip', 'size' => 1073741824, 'hash' => 'a1b2c3d4e5f60020', 'type' => 'Archive', 'location' => 'SAN-01', 'department' => 'IT'],
    ['path' => '/backups/system_backup_Nov.tar.gz', 'name' => 'system_backup_Nov.tar.gz', 'extension' => '.tar.gz', 'size' => 5368709120, 'hash' => 'a1b2c3d4e5f60021', 'type' => 'Archive', 'location' => 'SAN-02', 'department' => 'IT'],

    // Log files - should be archived
    ['path' => '/logs/application.log', 'name' => 'application.log', 'extension' => '.log', 'size' => 104857600, 'hash' => 'a1b2c3d4e5f60022', 'type' => 'Log', 'location' => 'SAN-01', 'department' => 'IT'],
    ['path' => '/logs/system.log', 'name' => 'system.log', 'extension' => '.log', 'size' => 209715200, 'hash' => 'a1b2c3d4e5f60023', 'type' => 'Log', 'location' => 'SAN-01', 'department' => 'IT'],
    ['path' => '/logs/access.log', 'name' => 'access.log', 'extension' => '.log', 'size' => 524288000, 'hash' => 'a1b2c3d4e5f60024', 'type' => 'Log', 'location' => 'SAN-01', 'department' => 'IT'],

    // Temporary files - should be deleted
    ['path' => '/temp/cache_data.tmp', 'name' => 'cache_data.tmp', 'extension' => '.tmp', 'size' => 52428800, 'hash' => 'a1b2c3d4e5f60025', 'type' => 'Temporary', 'location' => 'NAS-01', 'department' => 'IT'],
    ['path' => '/temp/download_temp.cache', 'name' => 'download_temp.cache', 'extension' => '.cache', 'size' => 104857600, 'hash' => 'a1b2c3d4e5f60026', 'type' => 'Temporary', 'location' => 'NAS-01', 'department' => 'IT'],

    // PDF documents
    ['path' => '/shares/Legal/Contract_Template.pdf', 'name' => 'Contract_Template.pdf', 'extension' => '.pdf', 'size' => 2097152, 'hash' => 'a1b2c3d4e5f60027', 'type' => 'PDF', 'location' => 'NAS-01', 'department' => 'Legal'],
    ['path' => '/shares/Legal/NDA_Standard.pdf', 'name' => 'NDA_Standard.pdf', 'extension' => '.pdf', 'size' => 1048576, 'hash' => 'a1b2c3d4e5f60028', 'type' => 'PDF', 'location' => 'NAS-01', 'department' => 'Legal'],

    // Confidential marked files
    ['path' => '/shares/Executive/CONFIDENTIAL_Board_Minutes.docx', 'name' => 'CONFIDENTIAL_Board_Minutes.docx', 'extension' => '.docx', 'size' => 3145728, 'hash' => 'a1b2c3d4e5f60029', 'type' => 'Word', 'location' => 'NAS-01', 'department' => 'Executive'],
    ['path' => '/shares/Finance/CONFIDENTIAL_Merger_Analysis.xlsx', 'name' => 'CONFIDENTIAL_Merger_Analysis.xlsx', 'extension' => '.xlsx', 'size' => 10485760, 'hash' => 'a1b2c3d4e5f60030', 'type' => 'Excel', 'location' => 'NAS-01', 'department' => 'Finance'],
];

echo "Scanning " . count($sampleFiles) . " files...\n\n";

$result = $scanner->scanFiles($scanId, $sampleFiles);

echo "=== Scan Results ===\n";
echo "Total Files Scanned: {$result['total_scanned']}\n";
echo "Duplicate Groups Found: {$result['duplicates_found']}\n\n";

// Get statistics
$stats = $scanner->getScanStatistics($scanId);

echo "=== Storage Statistics ===\n";
echo "Total Storage Used: " . round($stats['total_storage_bytes'] / 1073741824, 2) . " GB\n";
echo "Wasted by Duplicates: " . round($stats['wasted_space_bytes'] / 1073741824, 2) . " GB\n";
echo "Duplicate Groups: {$stats['duplicate_groups']}\n\n";

echo "=== Classification Breakdown ===\n";
foreach ($stats['by_classification'] as $class) {
    $sizeGB = round($class['total_size'] / 1073741824, 2);
    echo "  {$class['data_classification']}: {$class['count']} files ({$sizeGB} GB)\n";
}

echo "\n=== Sensitivity Breakdown ===\n";
foreach ($stats['by_sensitivity'] as $sens) {
    $sizeGB = round($sens['total_size'] / 1073741824, 2);
    echo "  {$sens['sensitivity_level']}: {$sens['count']} files ({$sizeGB} GB)\n";
}

echo "\n=== Optimization Recommendations ===\n";
echo "Pending Recommendations: {$stats['pending_recommendations']}\n";
echo "Potential Savings: " . round($stats['potential_savings_bytes'] / 1073741824, 2) . " GB\n\n";

// Display duplicate groups
$duplicateGroups = $scanner->getDuplicateGroups(10);
if (!empty($duplicateGroups)) {
    echo "=== Top Duplicate Groups ===\n";
    foreach ($duplicateGroups as $i => $group) {
        $num = $i + 1;
        $wastedGB = round($group['total_wasted_space'] / 1073741824, 2);
        $sizeGB = round($group['file_size_bytes'] / 1073741824, 2);
        echo "$num. {$group['group_id']}: {$group['duplicate_count']} copies of {$sizeGB}GB file, wasting {$wastedGB}GB [{$group['priority']}]\n";
    }
}

echo "\n✓ Sample data generation complete!\n";
echo "\nAccess the enhanced SRM module at:\n";
echo "http://localhost/networkscan/modules/srm.php\n";
?>
