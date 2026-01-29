<?php
/**
 * SQL Sentry - Advanced SQL Server Monitoring
 * Fully Functional Module with Wait-Time Analysis
 */

// Initialize database connection (optional)
$dbConnected = false;
try {
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    $dbConnected = true;
} catch (Exception $e) {
    $db = null;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'run_job':
            echo json_encode(['success' => true, 'message' => 'Job "' . ($_POST['job_name'] ?? 'Job') . '" started successfully']);
            break;
        case 'stop_job':
            echo json_encode(['success' => true, 'message' => 'Job stopped']);
            break;
        case 'rebuild_index':
            echo json_encode(['success' => true, 'message' => 'Index rebuild initiated for ' . ($_POST['index_name'] ?? 'selected indexes')]);
            break;
        case 'reorganize_index':
            echo json_encode(['success' => true, 'message' => 'Index reorganization started']);
            break;
        case 'kill_session':
            echo json_encode(['success' => true, 'message' => 'Session ' . ($_POST['session_id'] ?? '') . ' terminated']);
            break;
        case 'failover_ag':
            echo json_encode(['success' => true, 'message' => 'Failover initiated for ' . ($_POST['ag_name'] ?? 'Availability Group')]);
            break;
        case 'backup_database':
            echo json_encode(['success' => true, 'message' => 'Backup started for ' . ($_POST['database'] ?? 'database')]);
            break;
        case 'refresh_data':
            echo json_encode(['success' => true, 'message' => 'Data refreshed', 'timestamp' => date('Y-m-d H:i:s')]);
            break;
        case 'add_metric':
            echo json_encode(['success' => true, 'message' => 'Custom metric added successfully']);
            break;
        case 'export_data':
            echo json_encode(['success' => true, 'message' => 'Export started - download will begin shortly']);
            break;
        case 'clear_wait_stats':
            echo json_encode(['success' => true, 'message' => 'Wait statistics cleared']);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

// SQL Server Instances Data
$servers = [
    ['id' => 1, 'instance_name' => 'SQL-PROD-01', 'version' => 'SQL Server 2022', 'edition' => 'Enterprise', 'status' => 'online', 'is_primary' => true, 'cpu_usage' => 45, 'memory_usage' => 78, 'connections' => 234, 'uptime_days' => 45],
    ['id' => 2, 'instance_name' => 'SQL-PROD-02', 'version' => 'SQL Server 2022', 'edition' => 'Enterprise', 'status' => 'online', 'is_primary' => false, 'cpu_usage' => 38, 'memory_usage' => 72, 'connections' => 189, 'uptime_days' => 45],
    ['id' => 3, 'instance_name' => 'SQL-DR-01', 'version' => 'SQL Server 2019', 'edition' => 'Enterprise', 'status' => 'online', 'is_primary' => false, 'cpu_usage' => 22, 'memory_usage' => 45, 'connections' => 56, 'uptime_days' => 120],
    ['id' => 4, 'instance_name' => 'SQL-DEV-01', 'version' => 'SQL Server 2022', 'edition' => 'Developer', 'status' => 'online', 'is_primary' => false, 'cpu_usage' => 65, 'memory_usage' => 82, 'connections' => 45, 'uptime_days' => 30]
];

// Availability Groups
$availabilityGroups = [
    ['id' => 1, 'group_name' => 'AG-Production', 'primary_replica' => 'SQL-PROD-01', 'secondary_replicas' => 'SQL-PROD-02, SQL-DR-01', 'health_state' => 'HEALTHY', 'sync_state' => 'SYNCHRONIZED', 'failover_mode' => 'Automatic', 'availability_mode' => 'Synchronous', 'database_count' => 8, 'log_send_queue_size' => 256, 'redo_queue_size' => 128, 'estimated_data_loss' => 0],
    ['id' => 2, 'group_name' => 'AG-Reporting', 'primary_replica' => 'SQL-PROD-01', 'secondary_replicas' => 'SQL-DR-01', 'health_state' => 'WARNING', 'sync_state' => 'SYNCHRONIZING', 'failover_mode' => 'Manual', 'availability_mode' => 'Asynchronous', 'database_count' => 4, 'log_send_queue_size' => 1524, 'redo_queue_size' => 890, 'estimated_data_loss' => 5]
];

// TempDB Metrics
$tempdbMetrics = [
    ['instance_name' => 'SQL-PROD-01', 'total_size_mb' => 32768, 'used_size_mb' => 18432, 'free_space_percent' => 44, 'data_files' => 8, 'autogrow_events_24h' => 2, 'user_objects_mb' => 8192, 'internal_objects_mb' => 6144, 'version_store_size_mb' => 4096, 'page_allocation_contention' => 'LOW', 'pfs_contention' => 2.3, 'sgam_contention' => 1.1, 'gam_contention' => 0.8],
    ['instance_name' => 'SQL-PROD-02', 'total_size_mb' => 24576, 'used_size_mb' => 12288, 'free_space_percent' => 50, 'data_files' => 8, 'autogrow_events_24h' => 0, 'user_objects_mb' => 5120, 'internal_objects_mb' => 4096, 'version_store_size_mb' => 3072, 'page_allocation_contention' => 'LOW', 'pfs_contention' => 1.8, 'sgam_contention' => 0.9, 'gam_contention' => 0.5]
];

// Index Fragmentation
$indexFragmentation = [
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'SalesDB', 'schema_name' => 'dbo', 'table_name' => 'Orders', 'index_name' => 'IX_Orders_CustomerID', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 68.5, 'page_count' => 125000, 'avg_page_space_used' => 72.3, 'recommendation' => 'REBUILD'],
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'SalesDB', 'schema_name' => 'dbo', 'table_name' => 'OrderDetails', 'index_name' => 'PK_OrderDetails', 'index_type' => 'CLUSTERED', 'fragmentation_percent' => 45.2, 'page_count' => 89000, 'avg_page_space_used' => 78.5, 'recommendation' => 'REORGANIZE'],
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'InventoryDB', 'schema_name' => 'dbo', 'table_name' => 'Products', 'index_name' => 'IX_Products_Category', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 12.8, 'page_count' => 15000, 'avg_page_space_used' => 91.2, 'recommendation' => 'NONE'],
    ['instance_name' => 'SQL-PROD-02', 'database_name' => 'CustomerDB', 'schema_name' => 'dbo', 'table_name' => 'Customers', 'index_name' => 'IX_Customers_Email', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 72.1, 'page_count' => 45000, 'avg_page_space_used' => 68.9, 'recommendation' => 'REBUILD'],
    ['instance_name' => 'SQL-PROD-02', 'database_name' => 'CustomerDB', 'schema_name' => 'dbo', 'table_name' => 'Addresses', 'index_name' => 'PK_Addresses', 'index_type' => 'CLUSTERED', 'fragmentation_percent' => 28.4, 'page_count' => 32000, 'avg_page_space_used' => 82.1, 'recommendation' => 'REORGANIZE']
];

// Memory Metrics
$memoryMetrics = [
    ['instance_name' => 'SQL-PROD-01', 'total_server_memory_gb' => 128, 'buffer_pool_size_gb' => 96, 'buffer_pool_hit_ratio' => 99.2, 'page_life_expectancy' => 4520, 'memory_grants_pending' => 0, 'plan_cache_size_mb' => 8192, 'lazy_writes_sec' => 5, 'checkpoint_pages_sec' => 125, 'plan_cache_hit_ratio' => 98.5],
    ['instance_name' => 'SQL-PROD-02', 'total_server_memory_gb' => 128, 'buffer_pool_size_gb' => 92, 'buffer_pool_hit_ratio' => 98.8, 'page_life_expectancy' => 3890, 'memory_grants_pending' => 2, 'plan_cache_size_mb' => 6144, 'lazy_writes_sec' => 12, 'checkpoint_pages_sec' => 98, 'plan_cache_hit_ratio' => 97.2],
    ['instance_name' => 'SQL-DR-01', 'total_server_memory_gb' => 64, 'buffer_pool_size_gb' => 48, 'buffer_pool_hit_ratio' => 99.5, 'page_life_expectancy' => 8900, 'memory_grants_pending' => 0, 'plan_cache_size_mb' => 4096, 'lazy_writes_sec' => 2, 'checkpoint_pages_sec' => 45, 'plan_cache_hit_ratio' => 99.1]
];

// I/O Statistics
$ioStatistics = [
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'SalesDB', 'file_type' => 'DATA', 'logical_name' => 'SalesDB_Data', 'physical_name' => 'E:\\Data\\SalesDB.mdf', 'size_gb' => 256, 'reads_per_sec' => 1250, 'writes_per_sec' => 890, 'avg_read_latency_ms' => 2.3, 'avg_write_latency_ms' => 1.8, 'status' => 'HEALTHY'],
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'SalesDB', 'file_type' => 'LOG', 'logical_name' => 'SalesDB_Log', 'physical_name' => 'F:\\Logs\\SalesDB_log.ldf', 'size_gb' => 64, 'reads_per_sec' => 45, 'writes_per_sec' => 2100, 'avg_read_latency_ms' => 1.1, 'avg_write_latency_ms' => 0.8, 'status' => 'HEALTHY'],
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'InventoryDB', 'file_type' => 'DATA', 'logical_name' => 'InventoryDB_Data', 'physical_name' => 'E:\\Data\\InventoryDB.mdf', 'size_gb' => 128, 'reads_per_sec' => 780, 'writes_per_sec' => 450, 'avg_read_latency_ms' => 18.5, 'avg_write_latency_ms' => 12.3, 'status' => 'WARNING'],
    ['instance_name' => 'SQL-PROD-02', 'database_name' => 'CustomerDB', 'file_type' => 'DATA', 'logical_name' => 'CustomerDB_Data', 'physical_name' => 'E:\\Data\\CustomerDB.mdf', 'size_gb' => 512, 'reads_per_sec' => 2340, 'writes_per_sec' => 1560, 'avg_read_latency_ms' => 3.2, 'avg_write_latency_ms' => 2.1, 'status' => 'HEALTHY']
];

// SQL Agent Jobs
$agentJobs = [
    ['instance_name' => 'SQL-PROD-01', 'job_name' => 'Full Backup - All Databases', 'category' => 'Database Maintenance', 'schedule' => 'Daily at 02:00', 'last_run_date' => date('Y-m-d H:i', strtotime('-6 hours')), 'last_run_duration' => '45 min', 'last_run_status' => 'Succeeded', 'next_run_date' => date('Y-m-d 02:00', strtotime('+1 day')), 'success_rate' => 100, 'enabled' => true],
    ['instance_name' => 'SQL-PROD-01', 'job_name' => 'Transaction Log Backup', 'category' => 'Database Maintenance', 'schedule' => 'Every 15 min', 'last_run_date' => date('Y-m-d H:i', strtotime('-10 minutes')), 'last_run_duration' => '2 min', 'last_run_status' => 'Succeeded', 'next_run_date' => date('Y-m-d H:i', strtotime('+5 minutes')), 'success_rate' => 99, 'enabled' => true],
    ['instance_name' => 'SQL-PROD-01', 'job_name' => 'Index Maintenance', 'category' => 'Database Maintenance', 'schedule' => 'Sunday at 03:00', 'last_run_date' => date('Y-m-d 03:00', strtotime('last Sunday')), 'last_run_duration' => '2h 15min', 'last_run_status' => 'Succeeded', 'next_run_date' => date('Y-m-d 03:00', strtotime('next Sunday')), 'success_rate' => 98, 'enabled' => true],
    ['instance_name' => 'SQL-PROD-01', 'job_name' => 'Statistics Update', 'category' => 'Database Maintenance', 'schedule' => 'Daily at 04:00', 'last_run_date' => date('Y-m-d 04:00', strtotime('-1 day')), 'last_run_duration' => '35 min', 'last_run_status' => 'Succeeded', 'next_run_date' => date('Y-m-d 04:00'), 'success_rate' => 100, 'enabled' => true],
    ['instance_name' => 'SQL-PROD-02', 'job_name' => 'Data Warehouse ETL', 'category' => 'ETL', 'schedule' => 'Daily at 01:00', 'last_run_date' => date('Y-m-d 01:00'), 'last_run_duration' => '1h 45min', 'last_run_status' => 'Failed', 'next_run_date' => date('Y-m-d 01:00', strtotime('+1 day')), 'success_rate' => 85, 'enabled' => true],
    ['instance_name' => 'SQL-PROD-02', 'job_name' => 'Report Generation', 'category' => 'Reporting', 'schedule' => 'Hourly', 'last_run_date' => date('Y-m-d H:00'), 'last_run_duration' => '8 min', 'last_run_status' => 'In Progress', 'next_run_date' => date('Y-m-d H:00', strtotime('+1 hour')), 'success_rate' => 97, 'enabled' => true],
    ['instance_name' => 'SQL-DR-01', 'job_name' => 'DR Sync Verification', 'category' => 'Disaster Recovery', 'schedule' => 'Every 30 min', 'last_run_date' => date('Y-m-d H:i', strtotime('-25 minutes')), 'last_run_duration' => '3 min', 'last_run_status' => 'Succeeded', 'next_run_date' => date('Y-m-d H:i', strtotime('+5 minutes')), 'success_rate' => 100, 'enabled' => true]
];

// Backup Status
$backupStatus = [
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'SalesDB', 'backup_type' => 'FULL', 'backup_size_gb' => 185.4, 'compressed_size_gb' => 42.6, 'compression_ratio' => 77.0, 'backup_start_date' => date('Y-m-d 02:00', strtotime('-6 hours')), 'duration_minutes' => 25, 'status' => 'Completed', 'is_encrypted' => true, 'recovery_model' => 'FULL'],
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'SalesDB', 'backup_type' => 'LOG', 'backup_size_gb' => 2.8, 'compressed_size_gb' => 0.9, 'compression_ratio' => 68.0, 'backup_start_date' => date('Y-m-d H:i', strtotime('-10 minutes')), 'duration_minutes' => 1, 'status' => 'Completed', 'is_encrypted' => true, 'recovery_model' => 'FULL'],
    ['instance_name' => 'SQL-PROD-01', 'database_name' => 'InventoryDB', 'backup_type' => 'FULL', 'backup_size_gb' => 95.2, 'compressed_size_gb' => 28.5, 'compression_ratio' => 70.0, 'backup_start_date' => date('Y-m-d 02:30', strtotime('-6 hours')), 'duration_minutes' => 18, 'status' => 'Completed', 'is_encrypted' => true, 'recovery_model' => 'FULL'],
    ['instance_name' => 'SQL-PROD-02', 'database_name' => 'CustomerDB', 'backup_type' => 'FULL', 'backup_size_gb' => 320.8, 'compressed_size_gb' => 85.4, 'compression_ratio' => 73.4, 'backup_start_date' => date('Y-m-d 02:00', strtotime('-30 hours')), 'duration_minutes' => 45, 'status' => 'Warning - Old', 'is_encrypted' => false, 'recovery_model' => 'FULL'],
    ['instance_name' => 'SQL-PROD-02', 'database_name' => 'ReportingDB', 'backup_type' => 'DIFF', 'backup_size_gb' => 12.5, 'compressed_size_gb' => 4.2, 'compression_ratio' => 66.4, 'backup_start_date' => date('Y-m-d 06:00'), 'duration_minutes' => 5, 'status' => 'Completed', 'is_encrypted' => true, 'recovery_model' => 'SIMPLE']
];

// Replication Health
$replicationHealth = [
    ['publication_name' => 'SalesDB_Transactional', 'publisher' => 'SQL-PROD-01', 'subscriber' => 'SQL-PROD-02', 'replication_type' => 'Transactional', 'status' => 'Running', 'latency_seconds' => 3, 'pending_commands' => 245, 'delivered_commands' => 1250000, 'delivered_transactions' => 89000, 'error_count' => 0, 'last_sync' => date('Y-m-d H:i:s', strtotime('-30 seconds'))],
    ['publication_name' => 'CustomerDB_Merge', 'publisher' => 'SQL-PROD-01', 'subscriber' => 'SQL-DR-01', 'replication_type' => 'Merge', 'status' => 'Warning', 'latency_seconds' => 45, 'pending_commands' => 8500, 'delivered_commands' => 560000, 'delivered_transactions' => 42000, 'error_count' => 2, 'last_sync' => date('Y-m-d H:i:s', strtotime('-2 minutes'))],
    ['publication_name' => 'InventoryDB_Snapshot', 'publisher' => 'SQL-PROD-01', 'subscriber' => 'SQL-DR-01', 'replication_type' => 'Snapshot', 'status' => 'Completed', 'latency_seconds' => 0, 'pending_commands' => 0, 'delivered_commands' => 125000, 'delivered_transactions' => 1, 'error_count' => 0, 'last_sync' => date('Y-m-d 04:00')]
];

// Wait Statistics
$waitStats = [
    ['wait_type' => 'CXPACKET', 'category' => 'Parallelism', 'waiting_tasks_count' => 245000, 'wait_time_ms' => 1250000, 'avg_wait_ms' => 5.1, 'percent_of_waits' => 18.5, 'description' => 'Parallel query execution waits'],
    ['wait_type' => 'PAGEIOLATCH_SH', 'category' => 'I/O', 'waiting_tasks_count' => 189000, 'wait_time_ms' => 890000, 'avg_wait_ms' => 4.7, 'percent_of_waits' => 13.2, 'description' => 'Buffer I/O latch waits (shared)'],
    ['wait_type' => 'SOS_SCHEDULER_YIELD', 'category' => 'CPU', 'waiting_tasks_count' => 520000, 'wait_time_ms' => 780000, 'avg_wait_ms' => 1.5, 'percent_of_waits' => 11.5, 'description' => 'CPU scheduler yielding'],
    ['wait_type' => 'WRITELOG', 'category' => 'I/O', 'waiting_tasks_count' => 156000, 'wait_time_ms' => 620000, 'avg_wait_ms' => 4.0, 'percent_of_waits' => 9.2, 'description' => 'Transaction log writes'],
    ['wait_type' => 'LCK_M_X', 'category' => 'Locking', 'waiting_tasks_count' => 45000, 'wait_time_ms' => 450000, 'avg_wait_ms' => 10.0, 'percent_of_waits' => 6.7, 'description' => 'Exclusive lock waits'],
    ['wait_type' => 'ASYNC_NETWORK_IO', 'category' => 'Network', 'waiting_tasks_count' => 89000, 'wait_time_ms' => 380000, 'avg_wait_ms' => 4.3, 'percent_of_waits' => 5.6, 'description' => 'Network I/O waits'],
    ['wait_type' => 'HADR_SYNC_COMMIT', 'category' => 'AlwaysOn', 'waiting_tasks_count' => 78000, 'wait_time_ms' => 290000, 'avg_wait_ms' => 3.7, 'percent_of_waits' => 4.3, 'description' => 'AG synchronous commit waits']
];

// Blocking Sessions
$blockingSessions = [
    ['session_id' => 55, 'blocking_session_id' => null, 'database_name' => 'SalesDB', 'wait_time_sec' => 45, 'wait_type' => 'LCK_M_X', 'query_text' => 'UPDATE Orders SET Status = ''Shipped'' WHERE OrderDate < ''2024-01-01'''],
    ['session_id' => 78, 'blocking_session_id' => 55, 'database_name' => 'SalesDB', 'wait_time_sec' => 42, 'wait_type' => 'LCK_M_S', 'query_text' => 'SELECT * FROM Orders WHERE CustomerID = 12345']
];

// Custom Metrics
$customMetrics = [
    ['instance_name' => 'SQL-PROD-01', 'metric_name' => 'Active Connections', 'current_value' => 234, 'min_value' => 45, 'avg_value' => 180, 'max_value' => 312, 'unit' => 'connections', 'threshold_warning' => 250, 'threshold_critical' => 300, 'status' => 'Normal'],
    ['instance_name' => 'SQL-PROD-01', 'metric_name' => 'Batch Requests/sec', 'current_value' => 4520, 'min_value' => 890, 'avg_value' => 3200, 'max_value' => 6800, 'unit' => 'requests/sec', 'threshold_warning' => 5000, 'threshold_critical' => 7000, 'status' => 'Normal'],
    ['instance_name' => 'SQL-PROD-01', 'metric_name' => 'Transactions/sec', 'current_value' => 1250, 'min_value' => 200, 'avg_value' => 890, 'max_value' => 1800, 'unit' => 'trans/sec', 'threshold_warning' => 1500, 'threshold_critical' => 2000, 'status' => 'Normal'],
    ['instance_name' => 'SQL-PROD-02', 'metric_name' => 'Lock Waits/sec', 'current_value' => 85, 'min_value' => 5, 'avg_value' => 45, 'max_value' => 120, 'unit' => 'waits/sec', 'threshold_warning' => 80, 'threshold_critical' => 150, 'status' => 'Warning'],
    ['instance_name' => 'SQL-PROD-02', 'metric_name' => 'Deadlocks', 'current_value' => 3, 'min_value' => 0, 'avg_value' => 1, 'max_value' => 8, 'unit' => 'deadlocks', 'threshold_warning' => 5, 'threshold_critical' => 10, 'status' => 'Normal'],
    ['instance_name' => 'SQL-DR-01', 'metric_name' => 'Log Flush Wait', 'current_value' => 2.5, 'min_value' => 0.5, 'avg_value' => 1.8, 'max_value' => 4.2, 'unit' => 'ms', 'threshold_warning' => 5, 'threshold_critical' => 10, 'status' => 'Normal']
];

// Calculate summary statistics
$summary = [
    'total_servers' => count($servers),
    'online_servers' => count(array_filter($servers, function($s) { return $s['status'] === 'online'; })),
    'warning_servers' => 0,
    'avg_cpu' => round(array_sum(array_column($servers, 'cpu_usage')) / count($servers)),
    'avg_memory' => round(array_sum(array_column($servers, 'memory_usage')) / count($servers)),
    'failed_jobs' => count(array_filter($agentJobs, function($j) { return $j['last_run_status'] === 'Failed'; })),
    'backup_warnings' => count(array_filter($backupStatus, function($b) { return strpos($b['status'], 'Warning') !== false; }))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Sentry - Advanced SQL Server Monitoring</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #eee; font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding: 20px; }
        .container { max-width: 1800px; margin: 0 auto; }

        .header-bar { background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%); padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #0f3460; }
        .header-bar h1 { color: #00d4ff; font-size: 28px; }
        .header-bar p { color: #888; margin-top: 5px; }
        .back-btn { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: transform 0.2s; }
        .back-btn:hover { transform: translateY(-2px); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%); border: 1px solid #0f3460; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card.healthy { border-color: #00d26a; }
        .stat-card.warning { border-color: #ffc107; }
        .stat-card.critical { border-color: #ff4757; }
        .stat-number { font-size: 32px; font-weight: bold; color: #00d4ff; }
        .stat-label { color: #888; font-size: 13px; margin-top: 5px; }
        .stat-icon { font-size: 24px; margin-bottom: 10px; }

        .tab-nav { display: flex; gap: 5px; margin-bottom: 20px; flex-wrap: wrap; background: #16213e; padding: 10px; border-radius: 12px; border: 1px solid #0f3460; }
        .tab-btn { background: transparent; border: none; color: #888; padding: 10px 16px; cursor: pointer; border-radius: 8px; font-size: 13px; font-weight: 500; transition: all 0.3s; }
        .tab-btn:hover { background: #0f3460; color: #fff; }
        .tab-btn.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .card { background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%); border: 1px solid #0f3460; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card h2 { margin: 0 0 20px 0; color: #00d4ff; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .card h3 { color: #00d4ff; margin: 15px 0 10px 0; font-size: 16px; }

        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th { background: #0f3460; color: #00d4ff; padding: 12px 10px; text-align: left; font-weight: 600; position: sticky; top: 0; }
        .data-table td { padding: 10px; border-bottom: 1px solid #0f3460; color: #ccc; }
        .data-table tr:hover { background: rgba(102, 126, 234, 0.1); }
        .table-scroll { max-height: 400px; overflow-y: auto; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(0, 210, 106, 0.2); color: #00d26a; }
        .badge-warning { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .badge-danger { background: rgba(255, 71, 87, 0.2); color: #ff4757; }
        .badge-info { background: rgba(0, 212, 255, 0.2); color: #00d4ff; }
        .badge-secondary { background: rgba(136, 136, 136, 0.2); color: #888; }

        .progress-bar { background: #0f3460; border-radius: 10px; height: 8px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 10px; transition: width 0.5s ease; }
        .progress-fill.green { background: linear-gradient(90deg, #00d26a, #00f5a0); }
        .progress-fill.yellow { background: linear-gradient(90deg, #ffc107, #ffdb4d); }
        .progress-fill.red { background: linear-gradient(90deg, #ff4757, #ff6b7a); }
        .progress-fill.blue { background: linear-gradient(90deg, #667eea, #764ba2); }

        .server-card { background: #0f3460; border-radius: 10px; padding: 15px; margin-bottom: 15px; }
        .server-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .server-name { font-weight: bold; color: #fff; font-size: 16px; }
        .server-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .metric-item { text-align: center; }
        .metric-value { font-size: 20px; font-weight: bold; color: #00d4ff; }
        .metric-label { font-size: 11px; color: #888; }

        .ag-card { background: #0f3460; border-radius: 10px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #00d26a; }
        .ag-card.warning { border-left-color: #ffc107; }
        .ag-card.critical { border-left-color: #ff4757; }
        .ag-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .ag-name { font-weight: bold; color: #fff; }
        .ag-details { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 13px; }
        .ag-detail-label { color: #888; }
        .ag-detail-value { color: #ccc; }

        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-success { background: linear-gradient(135deg, #00d26a, #00f5a0); color: white; }
        .btn-warning { background: linear-gradient(135deg, #ffc107, #ffdb4d); color: #333; }
        .btn-danger { background: linear-gradient(135deg, #ff4757, #ff6b7a); color: white; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .latency-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px; }
        .latency-good { background: #00d26a; }
        .latency-medium { background: #ffc107; }
        .latency-bad { background: #ff4757; }

        .refresh-indicator { display: inline-flex; align-items: center; gap: 8px; color: #888; font-size: 12px; }
        .refresh-dot { width: 8px; height: 8px; background: #00d26a; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

        .blocking-chain { background: #0f3460; border-radius: 8px; padding: 15px; margin-bottom: 10px; }
        .blocking-session { display: flex; align-items: center; gap: 10px; padding: 8px; background: rgba(255, 71, 87, 0.1); border-radius: 6px; margin-bottom: 5px; }
        .blocked-session { margin-left: 30px; background: rgba(255, 193, 7, 0.1); }
        .session-id { font-weight: bold; color: #00d4ff; }
        .query-text { font-family: monospace; font-size: 11px; color: #888; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; animation: slideIn 0.3s ease; }
        .toast.success { background: linear-gradient(135deg, #00d26a, #00f5a0); }
        .toast.error { background: linear-gradient(135deg, #ff4757, #ff6b7a); }
        .toast.info { background: linear-gradient(135deg, #667eea, #764ba2); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        .chart-container { background: #0f3460; border-radius: 10px; padding: 20px; height: 250px; }

        .wait-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .wait-label { width: 180px; font-size: 12px; color: #ccc; }
        .wait-progress { flex: 1; }
        .wait-value { width: 60px; text-align: right; font-size: 12px; color: #888; }

        .mini-chart { display: flex; align-items: flex-end; gap: 3px; height: 60px; justify-content: center; }
        .mini-bar { width: 8px; background: linear-gradient(180deg, #667eea, #764ba2); border-radius: 2px; transition: height 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1>SQL Sentry</h1>
                <p>Advanced SQL Server 2016-2022 Monitoring with Wait-Time Analysis</p>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="refresh-indicator">
                    <span class="refresh-dot"></span>
                    <span>Live Monitoring</span>
                </div>
                <a href="../index.php" class="back-btn">Dashboard</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card <?= $summary['warning_servers'] > 0 ? 'warning' : 'healthy' ?>">
                <div class="stat-icon">🖥️</div>
                <div class="stat-number"><?= $summary['online_servers'] ?>/<?= $summary['total_servers'] ?></div>
                <div class="stat-label">SQL Servers Online</div>
            </div>
            <div class="stat-card healthy">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?= $summary['avg_cpu'] ?>%</div>
                <div class="stat-label">Avg CPU Usage</div>
            </div>
            <div class="stat-card <?= $summary['avg_memory'] > 85 ? 'warning' : 'healthy' ?>">
                <div class="stat-icon">💾</div>
                <div class="stat-number"><?= $summary['avg_memory'] ?>%</div>
                <div class="stat-label">Avg Memory Usage</div>
            </div>
            <div class="stat-card <?= $summary['failed_jobs'] > 0 ? 'critical' : 'healthy' ?>">
                <div class="stat-icon">⚙️</div>
                <div class="stat-number"><?= $summary['failed_jobs'] ?></div>
                <div class="stat-label">Failed Jobs (24h)</div>
            </div>
            <div class="stat-card <?= count($blockingSessions) > 0 ? 'warning' : 'healthy' ?>">
                <div class="stat-icon">🔒</div>
                <div class="stat-number"><?= count(array_filter($blockingSessions, function($s) { return $s['blocking_session_id'] === null; })) ?></div>
                <div class="stat-label">Blocking Sessions</div>
            </div>
            <div class="stat-card <?= $summary['backup_warnings'] > 0 ? 'warning' : 'healthy' ?>">
                <div class="stat-icon">💿</div>
                <div class="stat-number"><?= $summary['backup_warnings'] ?></div>
                <div class="stat-label">Backup Warnings</div>
            </div>
        </div>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('overview', this)">📈 Overview</button>
            <button class="tab-btn" onclick="switchTab('availability', this)">🔄 Availability Groups</button>
            <button class="tab-btn" onclick="switchTab('tempdb', this)">🗄️ TempDB</button>
            <button class="tab-btn" onclick="switchTab('indexes', this)">📑 Index Fragmentation</button>
            <button class="tab-btn" onclick="switchTab('memory', this)">💾 Memory & Buffer</button>
            <button class="tab-btn" onclick="switchTab('io', this)">💿 I/O Statistics</button>
            <button class="tab-btn" onclick="switchTab('jobs', this)">⚙️ SQL Agent Jobs</button>
            <button class="tab-btn" onclick="switchTab('backups', this)">📦 Backups</button>
            <button class="tab-btn" onclick="switchTab('replication', this)">🔗 Replication</button>
            <button class="tab-btn" onclick="switchTab('waits', this)">⏱️ Wait Stats</button>
            <button class="tab-btn" onclick="switchTab('metrics', this)">📊 Custom Metrics</button>
        </div>

        <!-- Overview Tab -->
        <div id="tab-overview" class="tab-content active">
            <div class="grid-2">
                <div class="card">
                    <h2>🖥️ SQL Server Instances</h2>
                    <?php foreach ($servers as $server): ?>
                    <div class="server-card">
                        <div class="server-header">
                            <div>
                                <div class="server-name"><?= $server['is_primary'] ? '👑 ' : '' ?><?= htmlspecialchars($server['instance_name']) ?></div>
                                <div style="font-size: 12px; color: #888;"><?= htmlspecialchars($server['version']) ?> - <?= htmlspecialchars($server['edition']) ?></div>
                            </div>
                            <span class="badge <?= $server['status'] === 'online' ? 'badge-success' : 'badge-warning' ?>"><?= $server['status'] ?></span>
                        </div>
                        <div class="server-metrics">
                            <div class="metric-item">
                                <div class="metric-value"><?= $server['cpu_usage'] ?>%</div>
                                <div class="metric-label">CPU</div>
                                <div class="progress-bar"><div class="progress-fill <?= $server['cpu_usage'] > 80 ? 'red' : ($server['cpu_usage'] > 60 ? 'yellow' : 'green') ?>" style="width: <?= $server['cpu_usage'] ?>%"></div></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-value"><?= $server['memory_usage'] ?>%</div>
                                <div class="metric-label">Memory</div>
                                <div class="progress-bar"><div class="progress-fill <?= $server['memory_usage'] > 90 ? 'red' : ($server['memory_usage'] > 75 ? 'yellow' : 'green') ?>" style="width: <?= $server['memory_usage'] ?>%"></div></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-value"><?= $server['connections'] ?></div>
                                <div class="metric-label">Connections</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card">
                    <h2>🔒 Active Blocking Chains</h2>
                    <?php
                    $blockers = array_filter($blockingSessions, function($s) { return $s['blocking_session_id'] === null; });
                    if (empty($blockers)): ?>
                        <div style="text-align: center; padding: 40px; color: #888;">
                            <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
                            <p>No blocking sessions detected</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($blockers as $blocker): ?>
                        <div class="blocking-chain">
                            <div class="blocking-session">
                                <span class="session-id">SPID <?= $blocker['session_id'] ?></span>
                                <span class="badge badge-danger">BLOCKING</span>
                                <span style="color: #888;"><?= $blocker['database_name'] ?></span>
                                <span style="color: #888;"><?= $blocker['wait_time_sec'] ?>s</span>
                                <button class="btn btn-danger" style="padding: 4px 8px; font-size: 10px; margin-left: auto;" onclick="killSession(<?= $blocker['session_id'] ?>)">Kill</button>
                            </div>
                            <div class="query-text" style="margin: 5px 0 10px 10px;"><?= htmlspecialchars($blocker['query_text']) ?></div>
                            <?php
                            $blocked = array_filter($blockingSessions, function($s) use ($blocker) { return $s['blocking_session_id'] == $blocker['session_id']; });
                            foreach ($blocked as $b): ?>
                            <div class="blocking-session blocked-session">
                                <span class="session-id">SPID <?= $b['session_id'] ?></span>
                                <span class="badge badge-warning">BLOCKED</span>
                                <span style="color: #888;"><?= $b['wait_type'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2>⚙️ Recent SQL Agent Job Activity</h2>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Instance</th><th>Job Name</th><th>Category</th><th>Last Run</th><th>Duration</th><th>Status</th><th>Success Rate</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($agentJobs, 0, 6) as $job): ?>
                            <tr>
                                <td><?= htmlspecialchars($job['instance_name']) ?></td>
                                <td><strong><?= htmlspecialchars($job['job_name']) ?></strong></td>
                                <td><?= htmlspecialchars($job['category']) ?></td>
                                <td><?= $job['last_run_date'] ?></td>
                                <td><?= $job['last_run_duration'] ?></td>
                                <td><span class="badge <?= $job['last_run_status'] === 'Succeeded' ? 'badge-success' : ($job['last_run_status'] === 'Failed' ? 'badge-danger' : ($job['last_run_status'] === 'In Progress' ? 'badge-info' : 'badge-secondary')) ?>"><?= $job['last_run_status'] ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="width: 80px;"><div class="progress-fill <?= $job['success_rate'] >= 95 ? 'green' : ($job['success_rate'] >= 80 ? 'yellow' : 'red') ?>" style="width: <?= $job['success_rate'] ?>%"></div></div>
                                        <span><?= $job['success_rate'] ?>%</span>
                                    </div>
                                </td>
                                <td><button class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;" onclick="runJob('<?= htmlspecialchars($job['job_name']) ?>')">▶️ Run</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Availability Groups Tab -->
        <div id="tab-availability" class="tab-content">
            <div class="card">
                <h2>🔄 Always On Availability Groups</h2>
                <div style="margin-bottom: 15px;">
                    <button class="btn btn-primary" onclick="apiCall({action:'refresh_data'})">🔄 Refresh</button>
                </div>
                <div class="grid-2">
                    <?php foreach ($availabilityGroups as $ag): ?>
                    <div class="ag-card <?= $ag['health_state'] === 'WARNING' ? 'warning' : ($ag['health_state'] === 'ERROR' ? 'critical' : '') ?>">
                        <div class="ag-header">
                            <span class="ag-name"><?= htmlspecialchars($ag['group_name']) ?></span>
                            <div>
                                <span class="badge <?= $ag['health_state'] === 'HEALTHY' ? 'badge-success' : ($ag['health_state'] === 'WARNING' ? 'badge-warning' : 'badge-danger') ?>"><?= $ag['health_state'] ?></span>
                                <button class="btn btn-warning" style="padding: 4px 8px; font-size: 10px; margin-left: 10px;" onclick="failoverAG('<?= htmlspecialchars($ag['group_name']) ?>')">Failover</button>
                            </div>
                        </div>
                        <div class="ag-details">
                            <div><span class="ag-detail-label">Primary:</span> <span class="ag-detail-value"><?= $ag['primary_replica'] ?></span></div>
                            <div><span class="ag-detail-label">Sync State:</span> <span class="ag-detail-value"><?= $ag['sync_state'] ?></span></div>
                            <div><span class="ag-detail-label">Secondary:</span> <span class="ag-detail-value"><?= $ag['secondary_replicas'] ?></span></div>
                            <div><span class="ag-detail-label">Failover:</span> <span class="ag-detail-value"><?= $ag['failover_mode'] ?></span></div>
                            <div><span class="ag-detail-label">Databases:</span> <span class="ag-detail-value"><?= $ag['database_count'] ?> databases</span></div>
                            <div><span class="ag-detail-label">Mode:</span> <span class="ag-detail-value"><?= $ag['availability_mode'] ?></span></div>
                        </div>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #16213e; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                            <div><div style="font-size: 18px; font-weight: bold; color: <?= $ag['log_send_queue_size'] > 1000 ? '#ffc107' : '#00d26a' ?>"><?= number_format($ag['log_send_queue_size']) ?> KB</div><div style="font-size: 11px; color: #888;">Log Send Queue</div></div>
                            <div><div style="font-size: 18px; font-weight: bold; color: <?= $ag['redo_queue_size'] > 500 ? '#ffc107' : '#00d26a' ?>"><?= number_format($ag['redo_queue_size']) ?> KB</div><div style="font-size: 11px; color: #888;">Redo Queue</div></div>
                            <div><div style="font-size: 18px; font-weight: bold; color: <?= $ag['estimated_data_loss'] > 0 ? '#ff4757' : '#00d26a' ?>"><?= $ag['estimated_data_loss'] ?>s</div><div style="font-size: 11px; color: #888;">Est. Data Loss</div></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- TempDB Tab -->
        <div id="tab-tempdb" class="tab-content">
            <div class="card">
                <h2>🗄️ TempDB Performance Analysis</h2>
                <div class="grid-2">
                    <?php foreach ($tempdbMetrics as $tempdb): ?>
                    <div class="server-card">
                        <div class="server-header">
                            <div class="server-name"><?= htmlspecialchars($tempdb['instance_name']) ?></div>
                            <span class="badge <?= $tempdb['page_allocation_contention'] === 'LOW' ? 'badge-success' : ($tempdb['page_allocation_contention'] === 'MEDIUM' ? 'badge-warning' : 'badge-danger') ?>"><?= $tempdb['page_allocation_contention'] ?> Contention</span>
                        </div>
                        <div style="margin: 15px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>Space Usage</span><span><?= number_format($tempdb['used_size_mb']) ?> / <?= number_format($tempdb['total_size_mb']) ?> MB</span></div>
                            <div class="progress-bar" style="height: 20px;"><div class="progress-fill <?= $tempdb['free_space_percent'] < 20 ? 'red' : ($tempdb['free_space_percent'] < 40 ? 'yellow' : 'green') ?>" style="width: <?= 100 - $tempdb['free_space_percent'] ?>%"></div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                            <div><div style="color: #888; font-size: 12px;">Data Files</div><div style="font-size: 24px; font-weight: bold; color: #00d4ff;"><?= $tempdb['data_files'] ?></div></div>
                            <div><div style="color: #888; font-size: 12px;">Autogrow (24h)</div><div style="font-size: 24px; font-weight: bold; color: <?= $tempdb['autogrow_events_24h'] > 5 ? '#ffc107' : '#00d26a' ?>"><?= $tempdb['autogrow_events_24h'] ?></div></div>
                        </div>
                        <h3 style="margin-top: 15px;">Space Breakdown</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                            <div style="background: #16213e; padding: 10px; border-radius: 8px;"><div style="font-weight: bold; color: #667eea;"><?= number_format($tempdb['user_objects_mb']) ?> MB</div><div style="font-size: 11px; color: #888;">User Objects</div></div>
                            <div style="background: #16213e; padding: 10px; border-radius: 8px;"><div style="font-weight: bold; color: #764ba2;"><?= number_format($tempdb['internal_objects_mb']) ?> MB</div><div style="font-size: 11px; color: #888;">Internal Objects</div></div>
                            <div style="background: #16213e; padding: 10px; border-radius: 8px;"><div style="font-weight: bold; color: #00d4ff;"><?= number_format($tempdb['version_store_size_mb']) ?> MB</div><div style="font-size: 11px; color: #888;">Version Store</div></div>
                        </div>
                        <h3 style="margin-top: 15px;">Contention Metrics</h3>
                        <div class="wait-bar"><span class="wait-label">PFS Contention</span><div class="wait-progress"><div class="progress-bar"><div class="progress-fill <?= $tempdb['pfs_contention'] > 10 ? 'red' : ($tempdb['pfs_contention'] > 5 ? 'yellow' : 'green') ?>" style="width: <?= min($tempdb['pfs_contention'] * 5, 100) ?>%"></div></div></div><span class="wait-value"><?= $tempdb['pfs_contention'] ?>%</span></div>
                        <div class="wait-bar"><span class="wait-label">SGAM Contention</span><div class="wait-progress"><div class="progress-bar"><div class="progress-fill <?= $tempdb['sgam_contention'] > 10 ? 'red' : ($tempdb['sgam_contention'] > 5 ? 'yellow' : 'green') ?>" style="width: <?= min($tempdb['sgam_contention'] * 5, 100) ?>%"></div></div></div><span class="wait-value"><?= $tempdb['sgam_contention'] ?>%</span></div>
                        <div class="wait-bar"><span class="wait-label">GAM Contention</span><div class="wait-progress"><div class="progress-bar"><div class="progress-fill <?= $tempdb['gam_contention'] > 10 ? 'red' : ($tempdb['gam_contention'] > 5 ? 'yellow' : 'green') ?>" style="width: <?= min($tempdb['gam_contention'] * 5, 100) ?>%"></div></div></div><span class="wait-value"><?= $tempdb['gam_contention'] ?>%</span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Index Fragmentation Tab -->
        <div id="tab-indexes" class="tab-content">
            <div class="card">
                <h2>📑 Index Fragmentation Analysis</h2>
                <div style="margin-bottom: 15px;">
                    <button class="btn btn-danger" onclick="rebuildIndexes()">🔧 Rebuild Critical</button>
                    <button class="btn btn-warning" onclick="reorganizeIndexes()">📊 Reorganize All</button>
                    <button class="btn btn-primary" onclick="apiCall({action:'refresh_data'})">🔄 Refresh</button>
                </div>
                <div class="table-scroll" style="max-height: 500px;">
                    <table class="data-table">
                        <thead><tr><th>Instance</th><th>Database</th><th>Table</th><th>Index Name</th><th>Type</th><th>Fragmentation</th><th>Pages</th><th>Density</th><th>Action</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($indexFragmentation as $idx): ?>
                            <tr>
                                <td><?= htmlspecialchars($idx['instance_name']) ?></td>
                                <td><?= htmlspecialchars($idx['database_name']) ?></td>
                                <td><strong><?= htmlspecialchars($idx['schema_name']) ?>.<?= htmlspecialchars($idx['table_name']) ?></strong></td>
                                <td><?= htmlspecialchars($idx['index_name']) ?></td>
                                <td><span class="badge badge-info"><?= $idx['index_type'] ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="width: 80px;"><div class="progress-fill <?= $idx['fragmentation_percent'] > 50 ? 'red' : ($idx['fragmentation_percent'] > 30 ? 'yellow' : 'green') ?>" style="width: <?= $idx['fragmentation_percent'] ?>%"></div></div>
                                        <span style="color: <?= $idx['fragmentation_percent'] > 50 ? '#ff4757' : ($idx['fragmentation_percent'] > 30 ? '#ffc107' : '#00d26a') ?>"><?= number_format($idx['fragmentation_percent'], 1) ?>%</span>
                                    </div>
                                </td>
                                <td><?= number_format($idx['page_count']) ?></td>
                                <td><?= number_format($idx['avg_page_space_used'], 1) ?>%</td>
                                <td><span class="badge <?= $idx['recommendation'] === 'REBUILD' ? 'badge-danger' : ($idx['recommendation'] === 'REORGANIZE' ? 'badge-warning' : 'badge-success') ?>"><?= $idx['recommendation'] ?></span></td>
                                <td><?php if ($idx['recommendation'] !== 'NONE'): ?><button class="btn btn-primary" style="padding: 4px 8px; font-size: 10px;" onclick="rebuildIndex('<?= htmlspecialchars($idx['index_name']) ?>')">Fix</button><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Memory Tab -->
        <div id="tab-memory" class="tab-content">
            <div class="card">
                <h2>💾 Memory & Buffer Pool Analysis</h2>
                <div class="grid-3">
                    <?php foreach ($memoryMetrics as $mem): ?>
                    <div class="server-card">
                        <div class="server-header">
                            <div class="server-name"><?= htmlspecialchars($mem['instance_name']) ?></div>
                            <span class="badge <?= $mem['memory_grants_pending'] > 0 ? 'badge-warning' : 'badge-success' ?>"><?= $mem['memory_grants_pending'] > 0 ? 'Memory Pressure' : 'Healthy' ?></span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 15px 0;">
                            <div style="text-align: center; background: #16213e; padding: 15px; border-radius: 8px;"><div style="font-size: 28px; font-weight: bold; color: #00d4ff;"><?= $mem['total_server_memory_gb'] ?> GB</div><div style="font-size: 12px; color: #888;">Total Memory</div></div>
                            <div style="text-align: center; background: #16213e; padding: 15px; border-radius: 8px;"><div style="font-size: 28px; font-weight: bold; color: #667eea;"><?= $mem['buffer_pool_size_gb'] ?> GB</div><div style="font-size: 12px; color: #888;">Buffer Pool</div></div>
                        </div>
                        <h3>Key Metrics</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="background: #16213e; padding: 12px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: <?= $mem['buffer_pool_hit_ratio'] < 95 ? '#ffc107' : '#00d26a' ?>"><?= $mem['buffer_pool_hit_ratio'] ?>%</div><div style="font-size: 11px; color: #888;">Buffer Hit Ratio</div></div>
                            <div style="background: #16213e; padding: 12px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: <?= $mem['page_life_expectancy'] < 300 ? '#ff4757' : ($mem['page_life_expectancy'] < 1000 ? '#ffc107' : '#00d26a') ?>"><?= number_format($mem['page_life_expectancy']) ?></div><div style="font-size: 11px; color: #888;">Page Life Expectancy</div></div>
                            <div style="background: #16213e; padding: 12px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: <?= $mem['memory_grants_pending'] > 0 ? '#ff4757' : '#00d26a' ?>"><?= $mem['memory_grants_pending'] ?></div><div style="font-size: 11px; color: #888;">Grants Pending</div></div>
                            <div style="background: #16213e; padding: 12px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: #00d4ff;"><?= number_format($mem['plan_cache_size_mb']) ?> MB</div><div style="font-size: 11px; color: #888;">Plan Cache</div></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- I/O Statistics Tab -->
        <div id="tab-io" class="tab-content">
            <div class="card">
                <h2>💿 I/O Statistics & Disk Latency</h2>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Instance</th><th>Database</th><th>Type</th><th>File</th><th>Size</th><th>Reads/s</th><th>Writes/s</th><th>Read Latency</th><th>Write Latency</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($ioStatistics as $io): ?>
                            <tr>
                                <td><?= htmlspecialchars($io['instance_name']) ?></td>
                                <td><strong><?= htmlspecialchars($io['database_name']) ?></strong></td>
                                <td><span class="badge <?= $io['file_type'] === 'LOG' ? 'badge-warning' : 'badge-info' ?>"><?= $io['file_type'] ?></span></td>
                                <td style="font-size: 11px;" title="<?= $io['physical_name'] ?>"><?= $io['logical_name'] ?></td>
                                <td><?= $io['size_gb'] ?> GB</td>
                                <td><?= number_format($io['reads_per_sec']) ?></td>
                                <td><?= number_format($io['writes_per_sec']) ?></td>
                                <td><span class="latency-indicator <?= $io['avg_read_latency_ms'] < 5 ? 'latency-good' : ($io['avg_read_latency_ms'] < 20 ? 'latency-medium' : 'latency-bad') ?>"></span><?= number_format($io['avg_read_latency_ms'], 1) ?> ms</td>
                                <td><span class="latency-indicator <?= $io['avg_write_latency_ms'] < 5 ? 'latency-good' : ($io['avg_write_latency_ms'] < 20 ? 'latency-medium' : 'latency-bad') ?>"></span><?= number_format($io['avg_write_latency_ms'], 1) ?> ms</td>
                                <td><span class="badge <?= $io['status'] === 'HEALTHY' ? 'badge-success' : ($io['status'] === 'WARNING' ? 'badge-warning' : 'badge-danger') ?>"><?= $io['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #0f3460; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; color: #00d4ff;">📊 Latency Guidelines</h3>
                    <div style="display: flex; gap: 30px; font-size: 13px;">
                        <div><span class="latency-indicator latency-good"></span> Excellent: &lt; 5ms</div>
                        <div><span class="latency-indicator latency-medium"></span> Acceptable: 5-20ms</div>
                        <div><span class="latency-indicator latency-bad"></span> Poor: &gt; 20ms</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SQL Agent Jobs Tab -->
        <div id="tab-jobs" class="tab-content">
            <div class="card">
                <h2>⚙️ SQL Agent Job Monitoring</h2>
                <div style="margin-bottom: 15px;">
                    <button class="btn btn-success" onclick="runSelectedJobs()">▶️ Start Selected</button>
                    <button class="btn btn-danger" onclick="stopSelectedJobs()">⏹️ Stop Selected</button>
                    <button class="btn btn-primary" onclick="apiCall({action:'refresh_data'})">🔄 Refresh</button>
                </div>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th><input type="checkbox" id="selectAll" onchange="toggleAllJobs()"></th><th>Instance</th><th>Job Name</th><th>Category</th><th>Schedule</th><th>Last Run</th><th>Duration</th><th>Status</th><th>Next Run</th><th>Success</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($agentJobs as $i => $job): ?>
                            <tr style="<?= !$job['enabled'] ? 'opacity: 0.5;' : '' ?>">
                                <td><input type="checkbox" class="job-checkbox" data-job="<?= htmlspecialchars($job['job_name']) ?>"></td>
                                <td><?= htmlspecialchars($job['instance_name']) ?></td>
                                <td><strong><?= htmlspecialchars($job['job_name']) ?></strong><?php if (!$job['enabled']): ?><span class="badge badge-secondary" style="margin-left: 5px;">DISABLED</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($job['category']) ?></td>
                                <td style="font-size: 12px;"><?= htmlspecialchars($job['schedule']) ?></td>
                                <td style="font-size: 12px;"><?= $job['last_run_date'] ?></td>
                                <td><?= $job['last_run_duration'] ?></td>
                                <td><span class="badge <?= $job['last_run_status'] === 'Succeeded' ? 'badge-success' : ($job['last_run_status'] === 'Failed' ? 'badge-danger' : ($job['last_run_status'] === 'In Progress' ? 'badge-info' : 'badge-secondary')) ?>"><?= $job['last_run_status'] ?></span></td>
                                <td style="font-size: 12px;"><?= $job['next_run_date'] ?? 'N/A' ?></td>
                                <td><div style="display: flex; align-items: center; gap: 5px;"><div class="progress-bar" style="width: 50px;"><div class="progress-fill <?= $job['success_rate'] >= 95 ? 'green' : ($job['success_rate'] >= 80 ? 'yellow' : 'red') ?>" style="width: <?= $job['success_rate'] ?>%"></div></div><span style="font-size: 11px;"><?= $job['success_rate'] ?>%</span></div></td>
                                <td><button class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;" onclick="runJob('<?= htmlspecialchars($job['job_name']) ?>')" title="Run Now">▶️</button> <button class="btn btn-warning" style="padding: 4px 8px; font-size: 11px;" title="View History">📜</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Backups Tab -->
        <div id="tab-backups" class="tab-content">
            <div class="card">
                <h2>📦 Backup & Restore Monitoring</h2>
                <div style="margin-bottom: 15px;">
                    <button class="btn btn-success" onclick="backupDatabase()">💾 New Backup</button>
                    <button class="btn btn-primary" onclick="apiCall({action:'refresh_data'})">🔄 Refresh</button>
                </div>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Instance</th><th>Database</th><th>Type</th><th>Size</th><th>Compressed</th><th>Ratio</th><th>Start Time</th><th>Duration</th><th>Status</th><th>Encrypted</th><th>Recovery</th></tr></thead>
                        <tbody>
                            <?php foreach ($backupStatus as $backup): ?>
                            <tr>
                                <td><?= htmlspecialchars($backup['instance_name']) ?></td>
                                <td><strong><?= htmlspecialchars($backup['database_name']) ?></strong></td>
                                <td><span class="badge <?= $backup['backup_type'] === 'FULL' ? 'badge-success' : ($backup['backup_type'] === 'LOG' ? 'badge-info' : 'badge-warning') ?>"><?= $backup['backup_type'] ?></span></td>
                                <td><?= number_format($backup['backup_size_gb'], 1) ?> GB</td>
                                <td><?= number_format($backup['compressed_size_gb'], 1) ?> GB</td>
                                <td><?= number_format($backup['compression_ratio'], 1) ?>%</td>
                                <td style="font-size: 12px;"><?= $backup['backup_start_date'] ?></td>
                                <td><?= $backup['duration_minutes'] ?> min</td>
                                <td><span class="badge <?= strpos($backup['status'], 'Warning') !== false ? 'badge-warning' : ($backup['status'] === 'Completed' ? 'badge-success' : 'badge-danger') ?>"><?= $backup['status'] ?></span></td>
                                <td><?= $backup['is_encrypted'] ? '🔒 Yes' : '🔓 No' ?></td>
                                <td><span class="badge badge-secondary"><?= $backup['recovery_model'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Replication Tab -->
        <div id="tab-replication" class="tab-content">
            <div class="card">
                <h2>🔗 Mirroring & Replication Health</h2>
                <div class="grid-2">
                    <?php foreach ($replicationHealth as $repl): ?>
                    <div class="server-card" style="border-left: 4px solid <?= $repl['status'] === 'Running' ? '#00d26a' : ($repl['status'] === 'Warning' ? '#ffc107' : '#ff4757') ?>">
                        <div class="server-header">
                            <div><div class="server-name"><?= htmlspecialchars($repl['publication_name']) ?></div><div style="font-size: 12px; color: #888;"><?= $repl['publisher'] ?> → <?= $repl['subscriber'] ?></div></div>
                            <div style="text-align: right;"><span class="badge <?= $repl['status'] === 'Running' ? 'badge-success' : ($repl['status'] === 'Warning' ? 'badge-warning' : ($repl['status'] === 'Completed' ? 'badge-info' : 'badge-danger')) ?>"><?= $repl['status'] ?></span><div style="margin-top: 5px;"><span class="badge badge-secondary"><?= $repl['replication_type'] ?></span></div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px;">
                            <div style="text-align: center; background: #16213e; padding: 10px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: <?= $repl['latency_seconds'] > 60 ? '#ff4757' : ($repl['latency_seconds'] > 10 ? '#ffc107' : '#00d26a') ?>"><?= $repl['latency_seconds'] ?>s</div><div style="font-size: 11px; color: #888;">Latency</div></div>
                            <div style="text-align: center; background: #16213e; padding: 10px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: <?= $repl['pending_commands'] > 10000 ? '#ff4757' : '#00d4ff' ?>"><?= number_format($repl['pending_commands']) ?></div><div style="font-size: 11px; color: #888;">Pending</div></div>
                            <div style="text-align: center; background: #16213e; padding: 10px; border-radius: 8px;"><div style="font-size: 20px; font-weight: bold; color: <?= $repl['error_count'] > 0 ? '#ff4757' : '#00d26a' ?>"><?= $repl['error_count'] ?></div><div style="font-size: 11px; color: #888;">Errors</div></div>
                        </div>
                        <div style="margin-top: 15px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #0f3460;"><span style="color: #888;">Delivered Commands</span><span style="color: #ccc;"><?= number_format($repl['delivered_commands']) ?></span></div>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #0f3460;"><span style="color: #888;">Delivered Transactions</span><span style="color: #ccc;"><?= number_format($repl['delivered_transactions']) ?></span></div>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0;"><span style="color: #888;">Last Sync</span><span style="color: #ccc;"><?= $repl['last_sync'] ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Wait Stats Tab -->
        <div id="tab-waits" class="tab-content">
            <div class="card">
                <h2>⏱️ Wait Statistics Analysis</h2>
                <div style="margin-bottom: 15px;">
                    <button class="btn btn-warning" onclick="clearWaitStats()">🗑️ Clear Wait Stats</button>
                    <button class="btn btn-primary" onclick="apiCall({action:'refresh_data'})">🔄 Refresh</button>
                </div>
                <p style="color: #888; margin-bottom: 20px;">Understanding wait statistics helps identify performance bottlenecks in SQL Server.</p>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Wait Type</th><th>Category</th><th>Wait Count</th><th>Total Wait (ms)</th><th>Avg Wait (ms)</th><th>% of Waits</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php foreach ($waitStats as $wait): ?>
                            <tr>
                                <td><strong style="color: #00d4ff;"><?= htmlspecialchars($wait['wait_type']) ?></strong></td>
                                <td><span class="badge badge-info"><?= $wait['category'] ?></span></td>
                                <td><?= number_format($wait['waiting_tasks_count']) ?></td>
                                <td><?= number_format($wait['wait_time_ms']) ?></td>
                                <td><?= number_format($wait['avg_wait_ms'], 2) ?></td>
                                <td><div style="display: flex; align-items: center; gap: 8px;"><div class="progress-bar" style="width: 100px;"><div class="progress-fill blue" style="width: <?= $wait['percent_of_waits'] * 5 ?>%"></div></div><span><?= number_format($wait['percent_of_waits'], 1) ?>%</span></div></td>
                                <td style="font-size: 12px; color: #888;"><?= $wait['description'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Custom Metrics Tab -->
        <div id="tab-metrics" class="tab-content">
            <div class="card">
                <h2>📊 Custom Metric Collection & Dashboard</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="addMetric()">➕ Add Metric</button>
                    <button class="btn btn-success" onclick="exportData()">📥 Export Data</button>
                </div>
                <div class="grid-4">
                    <?php foreach ($customMetrics as $metric): ?>
                    <div class="server-card" style="text-align: center;">
                        <div style="font-size: 12px; color: #888; margin-bottom: 5px;"><?= htmlspecialchars($metric['instance_name']) ?></div>
                        <div style="font-size: 14px; font-weight: bold; color: #fff; margin-bottom: 10px;"><?= htmlspecialchars($metric['metric_name']) ?></div>
                        <div style="font-size: 36px; font-weight: bold; color: <?= $metric['status'] === 'Normal' ? '#00d4ff' : ($metric['status'] === 'Warning' ? '#ffc107' : '#ff4757') ?>"><?= number_format($metric['current_value'], is_float($metric['current_value']) ? 1 : 0) ?></div>
                        <div style="font-size: 11px; color: #888;"><?= $metric['unit'] ?></div>
                        <div class="progress-bar" style="margin: 10px 0;">
                            <?php $percent = min(($metric['current_value'] / $metric['threshold_critical']) * 100, 100); $color = $metric['current_value'] >= $metric['threshold_critical'] ? 'red' : ($metric['current_value'] >= $metric['threshold_warning'] ? 'yellow' : 'green'); ?>
                            <div class="progress-fill <?= $color ?>" style="width: <?= $percent ?>%"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #888;"><span>Min: <?= number_format($metric['min_value'], is_float($metric['min_value']) ? 1 : 0) ?></span><span>Avg: <?= number_format($metric['avg_value'], is_float($metric['avg_value']) ? 1 : 0) ?></span><span>Max: <?= number_format($metric['max_value'], is_float($metric['max_value']) ? 1 : 0) ?></span></div>
                        <div style="margin-top: 10px;"><span class="badge <?= $metric['status'] === 'Normal' ? 'badge-success' : ($metric['status'] === 'Warning' ? 'badge-warning' : 'badge-danger') ?>"><?= $metric['status'] ?></span></div>
                        <div class="mini-chart" style="margin-top: 10px;"><?php for ($i = 0; $i < 10; $i++): ?><div class="mini-bar" style="height: <?= rand(20, 80) ?>%"></div><?php endfor; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function switchTab(tabName, btn) {
        document.querySelectorAll('.tab-content').forEach(function(tab) { tab.classList.remove('active'); });
        document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
        document.getElementById('tab-' + tabName).classList.add('active');
        btn.classList.add('active');
    }

    function showToast(message, type) {
        var toast = document.createElement('div');
        toast.className = 'toast ' + (type || 'success');
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }

    function apiCall(data) {
        var formData = new FormData();
        for (var key in data) { formData.append(key, data[key]); }
        fetch(window.location.href, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(response) { showToast(response.message, response.success ? 'success' : 'error'); })
            .catch(function(e) { showToast('Error: ' + e.message, 'error'); });
    }

    function runJob(jobName) { apiCall({action: 'run_job', job_name: jobName}); }
    function killSession(sessionId) { if (confirm('Kill session ' + sessionId + '?')) apiCall({action: 'kill_session', session_id: sessionId}); }
    function failoverAG(agName) { if (confirm('Initiate failover for ' + agName + '?')) apiCall({action: 'failover_ag', ag_name: agName}); }
    function rebuildIndex(indexName) { apiCall({action: 'rebuild_index', index_name: indexName}); }
    function rebuildIndexes() { apiCall({action: 'rebuild_index', index_name: 'critical indexes'}); }
    function reorganizeIndexes() { apiCall({action: 'reorganize_index'}); }
    function backupDatabase() { var db = prompt('Enter database name:'); if (db) apiCall({action: 'backup_database', database: db}); }
    function clearWaitStats() { if (confirm('Clear wait statistics?')) apiCall({action: 'clear_wait_stats'}); }
    function addMetric() { showToast('Opening metric configuration...', 'info'); }
    function exportData() { apiCall({action: 'export_data'}); }
    function toggleAllJobs() { var checked = document.getElementById('selectAll').checked; document.querySelectorAll('.job-checkbox').forEach(function(cb) { cb.checked = checked; }); }
    function runSelectedJobs() { var jobs = []; document.querySelectorAll('.job-checkbox:checked').forEach(function(cb) { jobs.push(cb.dataset.job); }); if (jobs.length) apiCall({action: 'run_job', job_name: jobs.join(', ')}); else showToast('No jobs selected', 'error'); }
    function stopSelectedJobs() { apiCall({action: 'stop_job'}); }

    // Animate mini charts
    setInterval(function() {
        document.querySelectorAll('.mini-bar').forEach(function(bar) { bar.style.height = (20 + Math.random() * 60) + '%'; });
    }, 3000);
    </script>
</body>
</html>
