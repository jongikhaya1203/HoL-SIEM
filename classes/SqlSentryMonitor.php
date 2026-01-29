<?php
/**
 * SQL Sentry Monitor Class
 * Advanced SQL Server monitoring for SQL Server 2016-2022
 * Provides comprehensive monitoring for:
 * - Always On Availability Groups
 * - Tempdb performance
 * - Index fragmentation
 * - Memory and buffer pool
 * - I/O statistics and disk latency
 * - SQL Agent jobs
 * - Backup and restore
 * - Mirroring and replication
 * - Custom metrics
 */

class SqlSentryMonitor
{
    private $db;
    private $sqlServerConnection = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Connect to SQL Server using sqlsrv or PDO ODBC
     */
    public function connectToSqlServer($server)
    {
        // For demo purposes, we'll simulate the connection
        // In production, use sqlsrv_connect() or PDO with ODBC
        return true;
    }

    /**
     * Get all monitored SQL Servers
     */
    public function getMonitoredServers()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_server_instances ORDER BY is_primary DESC, instance_name");
        } catch (Exception $e) {
            return $this->getSimulatedServers();
        }
    }

    /**
     * Get simulated server data for demo
     */
    private function getSimulatedServers()
    {
        return [
            [
                'id' => 1,
                'instance_name' => 'SQLPROD01',
                'host' => '192.168.1.50',
                'port' => 1433,
                'version' => 'SQL Server 2022 (16.0.4105.2)',
                'edition' => 'Enterprise',
                'is_primary' => 1,
                'status' => 'online',
                'cpu_usage' => 45,
                'memory_usage' => 72,
                'connections' => 156,
                'last_check' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'instance_name' => 'SQLPROD02',
                'host' => '192.168.1.51',
                'port' => 1433,
                'version' => 'SQL Server 2019 (15.0.4316.3)',
                'edition' => 'Enterprise',
                'is_primary' => 0,
                'status' => 'online',
                'cpu_usage' => 32,
                'memory_usage' => 65,
                'connections' => 89,
                'last_check' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'instance_name' => 'SQLDEV01',
                'host' => '192.168.1.60',
                'port' => 1433,
                'version' => 'SQL Server 2019 (15.0.4316.3)',
                'edition' => 'Developer',
                'is_primary' => 0,
                'status' => 'online',
                'cpu_usage' => 15,
                'memory_usage' => 45,
                'connections' => 12,
                'last_check' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 4,
                'instance_name' => 'SQLREPORT01',
                'host' => '192.168.1.55',
                'port' => 1433,
                'version' => 'SQL Server 2016 SP3 (13.0.6435.1)',
                'edition' => 'Standard',
                'is_primary' => 0,
                'status' => 'warning',
                'cpu_usage' => 78,
                'memory_usage' => 88,
                'connections' => 45,
                'last_check' => date('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get Always On Availability Groups status
     */
    public function getAvailabilityGroups()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_availability_groups ORDER BY group_name");
        } catch (Exception $e) {
            return $this->getSimulatedAvailabilityGroups();
        }
    }

    private function getSimulatedAvailabilityGroups()
    {
        return [
            [
                'id' => 1,
                'group_name' => 'AG_Production',
                'primary_replica' => 'SQLPROD01',
                'secondary_replicas' => 'SQLPROD02, SQLPROD03',
                'sync_state' => 'SYNCHRONIZED',
                'health_state' => 'HEALTHY',
                'failover_mode' => 'AUTOMATIC',
                'availability_mode' => 'SYNCHRONOUS_COMMIT',
                'databases' => 'ERP_DB, CRM_DB, Finance_DB',
                'database_count' => 3,
                'log_send_queue_size' => 125,
                'redo_queue_size' => 45,
                'last_commit_time' => date('Y-m-d H:i:s', strtotime('-2 seconds')),
                'estimated_data_loss' => 0,
                'estimated_recovery_time' => 15
            ],
            [
                'id' => 2,
                'group_name' => 'AG_Reporting',
                'primary_replica' => 'SQLPROD01',
                'secondary_replicas' => 'SQLREPORT01',
                'sync_state' => 'SYNCHRONIZING',
                'health_state' => 'WARNING',
                'failover_mode' => 'MANUAL',
                'availability_mode' => 'ASYNCHRONOUS_COMMIT',
                'databases' => 'Reporting_DB, Analytics_DB',
                'database_count' => 2,
                'log_send_queue_size' => 2450,
                'redo_queue_size' => 1250,
                'last_commit_time' => date('Y-m-d H:i:s', strtotime('-45 seconds')),
                'estimated_data_loss' => 45,
                'estimated_recovery_time' => 120
            ]
        ];
    }

    /**
     * Get Tempdb performance metrics
     */
    public function getTempdbMetrics()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_tempdb_metrics ORDER BY collected_at DESC LIMIT 1");
        } catch (Exception $e) {
            return $this->getSimulatedTempdbMetrics();
        }
    }

    private function getSimulatedTempdbMetrics()
    {
        return [
            [
                'instance_name' => 'SQLPROD01',
                'data_files' => 8,
                'total_size_mb' => 32768,
                'used_size_mb' => 12456,
                'free_size_mb' => 20312,
                'version_store_size_mb' => 1024,
                'internal_objects_mb' => 2456,
                'user_objects_mb' => 8976,
                'free_space_percent' => 62,
                'page_allocation_contention' => 'LOW',
                'sgam_contention' => 2.5,
                'pfs_contention' => 1.8,
                'gam_contention' => 0.5,
                'autogrow_events_24h' => 3,
                'collected_at' => date('Y-m-d H:i:s')
            ],
            [
                'instance_name' => 'SQLPROD02',
                'data_files' => 4,
                'total_size_mb' => 16384,
                'used_size_mb' => 8765,
                'free_size_mb' => 7619,
                'version_store_size_mb' => 512,
                'internal_objects_mb' => 1234,
                'user_objects_mb' => 7019,
                'free_space_percent' => 46,
                'page_allocation_contention' => 'MEDIUM',
                'sgam_contention' => 8.5,
                'pfs_contention' => 12.3,
                'gam_contention' => 3.2,
                'autogrow_events_24h' => 12,
                'collected_at' => date('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get Index Fragmentation data
     */
    public function getIndexFragmentation()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_index_fragmentation WHERE fragmentation_percent > 10 ORDER BY fragmentation_percent DESC LIMIT 20");
        } catch (Exception $e) {
            return $this->getSimulatedIndexFragmentation();
        }
    }

    private function getSimulatedIndexFragmentation()
    {
        return [
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'schema_name' => 'dbo', 'table_name' => 'OrderDetails', 'index_name' => 'IX_OrderDetails_ProductID', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 78.5, 'page_count' => 125000, 'avg_page_space_used' => 65.2, 'recommendation' => 'REBUILD', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'schema_name' => 'dbo', 'table_name' => 'Transactions', 'index_name' => 'PK_Transactions', 'index_type' => 'CLUSTERED', 'fragmentation_percent' => 65.2, 'page_count' => 450000, 'avg_page_space_used' => 72.1, 'recommendation' => 'REBUILD', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'CRM_DB', 'schema_name' => 'sales', 'table_name' => 'CustomerContacts', 'index_name' => 'IX_CustomerContacts_Email', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 45.8, 'page_count' => 32000, 'avg_page_space_used' => 78.5, 'recommendation' => 'REBUILD', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
            ['instance_name' => 'SQLPROD02', 'database_name' => 'Finance_DB', 'schema_name' => 'dbo', 'table_name' => 'LedgerEntries', 'index_name' => 'IX_LedgerEntries_AccountDate', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 38.2, 'page_count' => 89000, 'avg_page_space_used' => 82.3, 'recommendation' => 'REORGANIZE', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'schema_name' => 'inventory', 'table_name' => 'StockMovements', 'index_name' => 'IX_StockMovements_Date', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 32.1, 'page_count' => 67000, 'avg_page_space_used' => 85.6, 'recommendation' => 'REORGANIZE', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-45 minutes'))],
            ['instance_name' => 'SQLREPORT01', 'database_name' => 'Reporting_DB', 'schema_name' => 'reports', 'table_name' => 'DailySummary', 'index_name' => 'IX_DailySummary_ReportDate', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 28.5, 'page_count' => 15000, 'avg_page_space_used' => 88.2, 'recommendation' => 'REORGANIZE', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
            ['instance_name' => 'SQLPROD02', 'database_name' => 'CRM_DB', 'schema_name' => 'dbo', 'table_name' => 'ActivityLog', 'index_name' => 'IX_ActivityLog_UserDate', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 22.4, 'page_count' => 234000, 'avg_page_space_used' => 91.0, 'recommendation' => 'REORGANIZE', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'schema_name' => 'dbo', 'table_name' => 'Products', 'index_name' => 'IX_Products_CategoryID', 'index_type' => 'NONCLUSTERED', 'fragmentation_percent' => 15.3, 'page_count' => 8500, 'avg_page_space_used' => 93.5, 'recommendation' => 'MONITOR', 'last_analyzed' => date('Y-m-d H:i:s', strtotime('-2 hours'))]
        ];
    }

    /**
     * Get Memory and Buffer Pool metrics
     */
    public function getMemoryMetrics()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_memory_metrics ORDER BY collected_at DESC");
        } catch (Exception $e) {
            return $this->getSimulatedMemoryMetrics();
        }
    }

    private function getSimulatedMemoryMetrics()
    {
        return [
            [
                'instance_name' => 'SQLPROD01',
                'total_server_memory_gb' => 128,
                'target_server_memory_gb' => 120,
                'buffer_pool_size_gb' => 96,
                'buffer_pool_hit_ratio' => 99.2,
                'page_life_expectancy' => 7250,
                'memory_grants_pending' => 0,
                'memory_grants_outstanding' => 12,
                'stolen_pages_mb' => 4500,
                'free_pages_mb' => 2340,
                'plan_cache_size_mb' => 8500,
                'plan_cache_hit_ratio' => 98.5,
                'procedure_cache_pages' => 125000,
                'lazy_writes_sec' => 2,
                'checkpoint_pages_sec' => 450,
                'collected_at' => date('Y-m-d H:i:s')
            ],
            [
                'instance_name' => 'SQLPROD02',
                'total_server_memory_gb' => 64,
                'target_server_memory_gb' => 56,
                'buffer_pool_size_gb' => 48,
                'buffer_pool_hit_ratio' => 98.8,
                'page_life_expectancy' => 4500,
                'memory_grants_pending' => 2,
                'memory_grants_outstanding' => 8,
                'stolen_pages_mb' => 2200,
                'free_pages_mb' => 890,
                'plan_cache_size_mb' => 4200,
                'plan_cache_hit_ratio' => 97.2,
                'procedure_cache_pages' => 65000,
                'lazy_writes_sec' => 5,
                'checkpoint_pages_sec' => 280,
                'collected_at' => date('Y-m-d H:i:s')
            ],
            [
                'instance_name' => 'SQLREPORT01',
                'total_server_memory_gb' => 32,
                'target_server_memory_gb' => 28,
                'buffer_pool_size_gb' => 22,
                'buffer_pool_hit_ratio' => 95.5,
                'page_life_expectancy' => 1200,
                'memory_grants_pending' => 5,
                'memory_grants_outstanding' => 15,
                'stolen_pages_mb' => 1100,
                'free_pages_mb' => 120,
                'plan_cache_size_mb' => 2100,
                'plan_cache_hit_ratio' => 92.1,
                'procedure_cache_pages' => 28000,
                'lazy_writes_sec' => 45,
                'checkpoint_pages_sec' => 850,
                'collected_at' => date('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get I/O Statistics and Disk Latency
     */
    public function getIOStatistics()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_io_statistics ORDER BY avg_write_latency_ms DESC");
        } catch (Exception $e) {
            return $this->getSimulatedIOStatistics();
        }
    }

    private function getSimulatedIOStatistics()
    {
        return [
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'file_type' => 'DATA', 'logical_name' => 'ERP_DB_Data', 'physical_name' => 'E:\\Data\\ERP_DB.mdf', 'size_gb' => 450, 'reads_per_sec' => 1250, 'writes_per_sec' => 890, 'read_bytes_per_sec' => 156000000, 'write_bytes_per_sec' => 98000000, 'avg_read_latency_ms' => 2.5, 'avg_write_latency_ms' => 4.2, 'io_stall_read_ms' => 125000, 'io_stall_write_ms' => 234000, 'status' => 'HEALTHY'],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'file_type' => 'LOG', 'logical_name' => 'ERP_DB_Log', 'physical_name' => 'F:\\Logs\\ERP_DB_log.ldf', 'size_gb' => 120, 'reads_per_sec' => 45, 'writes_per_sec' => 2340, 'read_bytes_per_sec' => 5600000, 'write_bytes_per_sec' => 312000000, 'avg_read_latency_ms' => 1.2, 'avg_write_latency_ms' => 1.8, 'io_stall_read_ms' => 8500, 'io_stall_write_ms' => 156000, 'status' => 'HEALTHY'],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'tempdb', 'file_type' => 'DATA', 'logical_name' => 'tempdev', 'physical_name' => 'T:\\TempDB\\tempdb.mdf', 'size_gb' => 32, 'reads_per_sec' => 3450, 'writes_per_sec' => 4560, 'read_bytes_per_sec' => 425000000, 'write_bytes_per_sec' => 567000000, 'avg_read_latency_ms' => 0.8, 'avg_write_latency_ms' => 1.2, 'io_stall_read_ms' => 45000, 'io_stall_write_ms' => 89000, 'status' => 'HEALTHY'],
            ['instance_name' => 'SQLREPORT01', 'database_name' => 'Reporting_DB', 'file_type' => 'DATA', 'logical_name' => 'Reporting_Data', 'physical_name' => 'D:\\Data\\Reporting_DB.mdf', 'size_gb' => 180, 'reads_per_sec' => 2890, 'writes_per_sec' => 120, 'read_bytes_per_sec' => 356000000, 'write_bytes_per_sec' => 15000000, 'avg_read_latency_ms' => 18.5, 'avg_write_latency_ms' => 25.2, 'io_stall_read_ms' => 890000, 'io_stall_write_ms' => 125000, 'status' => 'WARNING'],
            ['instance_name' => 'SQLREPORT01', 'database_name' => 'Reporting_DB', 'file_type' => 'LOG', 'logical_name' => 'Reporting_Log', 'physical_name' => 'D:\\Logs\\Reporting_DB_log.ldf', 'size_gb' => 45, 'reads_per_sec' => 12, 'writes_per_sec' => 450, 'read_bytes_per_sec' => 1500000, 'write_bytes_per_sec' => 56000000, 'avg_read_latency_ms' => 8.5, 'avg_write_latency_ms' => 35.8, 'io_stall_read_ms' => 12000, 'io_stall_write_ms' => 345000, 'status' => 'CRITICAL']
        ];
    }

    /**
     * Get SQL Agent Jobs
     */
    public function getAgentJobs()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_agent_jobs ORDER BY last_run_date DESC");
        } catch (Exception $e) {
            return $this->getSimulatedAgentJobs();
        }
    }

    private function getSimulatedAgentJobs()
    {
        return [
            ['instance_name' => 'SQLPROD01', 'job_name' => 'DatabaseBackup_FULL', 'category' => 'Database Maintenance', 'enabled' => 1, 'schedule' => 'Daily at 02:00', 'last_run_date' => date('Y-m-d 02:00:00'), 'last_run_status' => 'Succeeded', 'last_run_duration' => '00:45:23', 'next_run_date' => date('Y-m-d 02:00:00', strtotime('+1 day')), 'avg_duration' => '00:42:15', 'success_rate' => 100],
            ['instance_name' => 'SQLPROD01', 'job_name' => 'IndexMaintenance', 'category' => 'Database Maintenance', 'enabled' => 1, 'schedule' => 'Weekly - Sunday 03:00', 'last_run_date' => date('Y-m-d 03:00:00', strtotime('last sunday')), 'last_run_status' => 'Succeeded', 'last_run_duration' => '02:15:45', 'next_run_date' => date('Y-m-d 03:00:00', strtotime('next sunday')), 'avg_duration' => '02:10:30', 'success_rate' => 98],
            ['instance_name' => 'SQLPROD01', 'job_name' => 'TransactionLogBackup', 'category' => 'Database Maintenance', 'enabled' => 1, 'schedule' => 'Every 15 minutes', 'last_run_date' => date('Y-m-d H:i:00', strtotime('-15 minutes')), 'last_run_status' => 'Succeeded', 'last_run_duration' => '00:02:12', 'next_run_date' => date('Y-m-d H:i:00', strtotime('+15 minutes')), 'avg_duration' => '00:02:05', 'success_rate' => 100],
            ['instance_name' => 'SQLPROD01', 'job_name' => 'ETL_SalesData', 'category' => 'Data Import', 'enabled' => 1, 'schedule' => 'Hourly', 'last_run_date' => date('Y-m-d H:00:00'), 'last_run_status' => 'Failed', 'last_run_duration' => '00:12:45', 'next_run_date' => date('Y-m-d H:00:00', strtotime('+1 hour')), 'avg_duration' => '00:08:30', 'success_rate' => 92],
            ['instance_name' => 'SQLPROD01', 'job_name' => 'StatisticsUpdate', 'category' => 'Database Maintenance', 'enabled' => 1, 'schedule' => 'Daily at 04:00', 'last_run_date' => date('Y-m-d 04:00:00'), 'last_run_status' => 'Succeeded', 'last_run_duration' => '00:35:18', 'next_run_date' => date('Y-m-d 04:00:00', strtotime('+1 day')), 'avg_duration' => '00:32:45', 'success_rate' => 100],
            ['instance_name' => 'SQLPROD02', 'job_name' => 'ReplicationMonitor', 'category' => 'Replication', 'enabled' => 1, 'schedule' => 'Every 5 minutes', 'last_run_date' => date('Y-m-d H:i:00', strtotime('-5 minutes')), 'last_run_status' => 'Succeeded', 'last_run_duration' => '00:00:45', 'next_run_date' => date('Y-m-d H:i:00', strtotime('+5 minutes')), 'avg_duration' => '00:00:42', 'success_rate' => 99],
            ['instance_name' => 'SQLREPORT01', 'job_name' => 'DailyReportGeneration', 'category' => 'Reporting', 'enabled' => 1, 'schedule' => 'Daily at 06:00', 'last_run_date' => date('Y-m-d 06:00:00'), 'last_run_status' => 'In Progress', 'last_run_duration' => '00:45:00', 'next_run_date' => date('Y-m-d 06:00:00', strtotime('+1 day')), 'avg_duration' => '01:15:00', 'success_rate' => 95],
            ['instance_name' => 'SQLPROD01', 'job_name' => 'ArchiveOldData', 'category' => 'Data Archival', 'enabled' => 0, 'schedule' => 'Monthly - 1st at 01:00', 'last_run_date' => date('Y-m-01 01:00:00', strtotime('last month')), 'last_run_status' => 'Succeeded', 'last_run_duration' => '04:25:30', 'next_run_date' => null, 'avg_duration' => '04:00:00', 'success_rate' => 100]
        ];
    }

    /**
     * Get Backup Status
     */
    public function getBackupStatus()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_backup_history ORDER BY backup_finish_date DESC LIMIT 20");
        } catch (Exception $e) {
            return $this->getSimulatedBackupStatus();
        }
    }

    private function getSimulatedBackupStatus()
    {
        return [
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'backup_type' => 'FULL', 'backup_size_gb' => 125.5, 'compressed_size_gb' => 28.3, 'compression_ratio' => 77.4, 'backup_start_date' => date('Y-m-d 02:00:00'), 'backup_finish_date' => date('Y-m-d 02:35:00'), 'duration_minutes' => 35, 'status' => 'Completed', 'backup_path' => '\\\\BackupServer\\SQLBackups\\SQLPROD01\\ERP_DB_FULL_'.date('Ymd').'.bak', 'is_encrypted' => 1, 'recovery_model' => 'FULL'],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'ERP_DB', 'backup_type' => 'LOG', 'backup_size_gb' => 2.5, 'compressed_size_gb' => 0.8, 'compression_ratio' => 68.0, 'backup_start_date' => date('Y-m-d H:00:00', strtotime('-15 minutes')), 'backup_finish_date' => date('Y-m-d H:02:00', strtotime('-13 minutes')), 'duration_minutes' => 2, 'status' => 'Completed', 'backup_path' => '\\\\BackupServer\\SQLBackups\\SQLPROD01\\ERP_DB_LOG_'.date('YmdHi').'.trn', 'is_encrypted' => 1, 'recovery_model' => 'FULL'],
            ['instance_name' => 'SQLPROD01', 'database_name' => 'CRM_DB', 'backup_type' => 'FULL', 'backup_size_gb' => 45.2, 'compressed_size_gb' => 12.1, 'compression_ratio' => 73.2, 'backup_start_date' => date('Y-m-d 02:35:00'), 'backup_finish_date' => date('Y-m-d 02:48:00'), 'duration_minutes' => 13, 'status' => 'Completed', 'backup_path' => '\\\\BackupServer\\SQLBackups\\SQLPROD01\\CRM_DB_FULL_'.date('Ymd').'.bak', 'is_encrypted' => 1, 'recovery_model' => 'FULL'],
            ['instance_name' => 'SQLPROD02', 'database_name' => 'Finance_DB', 'backup_type' => 'FULL', 'backup_size_gb' => 78.9, 'compressed_size_gb' => 18.5, 'compression_ratio' => 76.5, 'backup_start_date' => date('Y-m-d 02:00:00'), 'backup_finish_date' => date('Y-m-d 02:22:00'), 'duration_minutes' => 22, 'status' => 'Completed', 'backup_path' => '\\\\BackupServer\\SQLBackups\\SQLPROD02\\Finance_DB_FULL_'.date('Ymd').'.bak', 'is_encrypted' => 1, 'recovery_model' => 'FULL'],
            ['instance_name' => 'SQLREPORT01', 'database_name' => 'Reporting_DB', 'backup_type' => 'FULL', 'backup_size_gb' => 180.5, 'compressed_size_gb' => 45.2, 'compression_ratio' => 75.0, 'backup_start_date' => date('Y-m-d 03:00:00'), 'backup_finish_date' => date('Y-m-d 03:55:00'), 'duration_minutes' => 55, 'status' => 'Completed', 'backup_path' => '\\\\BackupServer\\SQLBackups\\SQLREPORT01\\Reporting_DB_FULL_'.date('Ymd').'.bak', 'is_encrypted' => 0, 'recovery_model' => 'SIMPLE'],
            ['instance_name' => 'SQLDEV01', 'database_name' => 'DevTest_DB', 'backup_type' => 'FULL', 'backup_size_gb' => 12.3, 'compressed_size_gb' => 3.2, 'compression_ratio' => 74.0, 'backup_start_date' => date('Y-m-d 01:00:00', strtotime('-2 days')), 'backup_finish_date' => date('Y-m-d 01:05:00', strtotime('-2 days')), 'duration_minutes' => 5, 'status' => 'Warning - Old', 'backup_path' => '\\\\BackupServer\\SQLBackups\\SQLDEV01\\DevTest_DB_FULL_'.date('Ymd', strtotime('-2 days')).'.bak', 'is_encrypted' => 0, 'recovery_model' => 'SIMPLE']
        ];
    }

    /**
     * Get Mirroring and Replication Health
     */
    public function getReplicationHealth()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_replication_status ORDER BY publication_name");
        } catch (Exception $e) {
            return $this->getSimulatedReplicationHealth();
        }
    }

    private function getSimulatedReplicationHealth()
    {
        return [
            [
                'replication_type' => 'Transactional',
                'publisher' => 'SQLPROD01',
                'publication_name' => 'ERP_Publication',
                'subscriber' => 'SQLPROD02',
                'subscription_db' => 'ERP_DB_Replica',
                'status' => 'Running',
                'latency_seconds' => 2,
                'pending_commands' => 125,
                'delivered_commands' => 2456789,
                'delivered_transactions' => 125678,
                'last_sync' => date('Y-m-d H:i:s', strtotime('-5 seconds')),
                'error_count' => 0
            ],
            [
                'replication_type' => 'Transactional',
                'publisher' => 'SQLPROD01',
                'publication_name' => 'CRM_Publication',
                'subscriber' => 'SQLREPORT01',
                'subscription_db' => 'CRM_DB_Replica',
                'status' => 'Running',
                'latency_seconds' => 45,
                'pending_commands' => 8750,
                'delivered_commands' => 1234567,
                'delivered_transactions' => 78945,
                'last_sync' => date('Y-m-d H:i:s', strtotime('-45 seconds')),
                'error_count' => 0
            ],
            [
                'replication_type' => 'Merge',
                'publisher' => 'SQLPROD01',
                'publication_name' => 'Sales_MergePublication',
                'subscriber' => 'SQLPROD02',
                'subscription_db' => 'Sales_DB',
                'status' => 'Warning',
                'latency_seconds' => 180,
                'pending_commands' => 25000,
                'delivered_commands' => 567890,
                'delivered_transactions' => 45678,
                'last_sync' => date('Y-m-d H:i:s', strtotime('-3 minutes')),
                'error_count' => 2
            ],
            [
                'replication_type' => 'Snapshot',
                'publisher' => 'SQLPROD01',
                'publication_name' => 'Reference_Snapshot',
                'subscriber' => 'SQLDEV01',
                'subscription_db' => 'Reference_DB',
                'status' => 'Completed',
                'latency_seconds' => 0,
                'pending_commands' => 0,
                'delivered_commands' => 45000,
                'delivered_transactions' => 1,
                'last_sync' => date('Y-m-d 04:00:00'),
                'error_count' => 0
            ]
        ];
    }

    /**
     * Get Wait Statistics
     */
    public function getWaitStatistics()
    {
        return [
            ['wait_type' => 'PAGEIOLATCH_SH', 'waiting_tasks_count' => 125000, 'wait_time_ms' => 2500000, 'signal_wait_time_ms' => 125000, 'avg_wait_ms' => 20.0, 'percent_of_waits' => 18.5, 'category' => 'Buffer I/O', 'description' => 'Waiting for data page to be read from disk'],
            ['wait_type' => 'LCK_M_X', 'waiting_tasks_count' => 45000, 'wait_time_ms' => 1800000, 'signal_wait_time_ms' => 45000, 'avg_wait_ms' => 40.0, 'percent_of_waits' => 13.3, 'category' => 'Lock', 'description' => 'Waiting for exclusive lock on resource'],
            ['wait_type' => 'WRITELOG', 'waiting_tasks_count' => 890000, 'wait_time_ms' => 1500000, 'signal_wait_time_ms' => 89000, 'avg_wait_ms' => 1.7, 'percent_of_waits' => 11.1, 'category' => 'Log', 'description' => 'Waiting for transaction log write'],
            ['wait_type' => 'ASYNC_NETWORK_IO', 'waiting_tasks_count' => 234000, 'wait_time_ms' => 1200000, 'signal_wait_time_ms' => 23000, 'avg_wait_ms' => 5.1, 'percent_of_waits' => 8.9, 'category' => 'Network', 'description' => 'Waiting for client to consume data'],
            ['wait_type' => 'CXPACKET', 'waiting_tasks_count' => 567000, 'wait_time_ms' => 1100000, 'signal_wait_time_ms' => 56000, 'avg_wait_ms' => 1.9, 'percent_of_waits' => 8.1, 'category' => 'Parallelism', 'description' => 'Parallel query synchronization'],
            ['wait_type' => 'SOS_SCHEDULER_YIELD', 'waiting_tasks_count' => 2345000, 'wait_time_ms' => 950000, 'signal_wait_time_ms' => 234000, 'avg_wait_ms' => 0.4, 'percent_of_waits' => 7.0, 'category' => 'CPU', 'description' => 'Task yielding CPU to other tasks'],
            ['wait_type' => 'PAGELATCH_EX', 'waiting_tasks_count' => 78000, 'wait_time_ms' => 780000, 'signal_wait_time_ms' => 7800, 'avg_wait_ms' => 10.0, 'percent_of_waits' => 5.8, 'category' => 'Buffer Latch', 'description' => 'Waiting for latch on data page in memory'],
            ['wait_type' => 'HADR_SYNC_COMMIT', 'waiting_tasks_count' => 125000, 'wait_time_ms' => 625000, 'signal_wait_time_ms' => 12500, 'avg_wait_ms' => 5.0, 'percent_of_waits' => 4.6, 'category' => 'AlwaysOn', 'description' => 'Waiting for Always On sync commit']
        ];
    }

    /**
     * Get Custom Metrics
     */
    public function getCustomMetrics()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM sql_custom_metrics ORDER BY metric_name");
        } catch (Exception $e) {
            return $this->getSimulatedCustomMetrics();
        }
    }

    private function getSimulatedCustomMetrics()
    {
        return [
            ['metric_name' => 'Active Connections', 'instance_name' => 'SQLPROD01', 'current_value' => 156, 'min_value' => 45, 'max_value' => 250, 'avg_value' => 125, 'threshold_warning' => 200, 'threshold_critical' => 250, 'status' => 'Normal', 'unit' => 'connections'],
            ['metric_name' => 'Batch Requests/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 4567, 'min_value' => 1200, 'max_value' => 8500, 'avg_value' => 3500, 'threshold_warning' => 7000, 'threshold_critical' => 9000, 'status' => 'Normal', 'unit' => 'requests/sec'],
            ['metric_name' => 'SQL Compilations/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 125, 'min_value' => 45, 'max_value' => 350, 'avg_value' => 100, 'threshold_warning' => 250, 'threshold_critical' => 400, 'status' => 'Normal', 'unit' => 'compilations/sec'],
            ['metric_name' => 'SQL Re-Compilations/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 8, 'min_value' => 2, 'max_value' => 25, 'avg_value' => 6, 'threshold_warning' => 15, 'threshold_critical' => 30, 'status' => 'Normal', 'unit' => 'recompilations/sec'],
            ['metric_name' => 'Lock Waits/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 12, 'min_value' => 0, 'max_value' => 45, 'avg_value' => 8, 'threshold_warning' => 30, 'threshold_critical' => 50, 'status' => 'Normal', 'unit' => 'waits/sec'],
            ['metric_name' => 'Full Scans/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 35, 'min_value' => 5, 'max_value' => 120, 'avg_value' => 25, 'threshold_warning' => 80, 'threshold_critical' => 150, 'status' => 'Normal', 'unit' => 'scans/sec'],
            ['metric_name' => 'Transactions/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 1245, 'min_value' => 350, 'max_value' => 2500, 'avg_value' => 1000, 'threshold_warning' => 2000, 'threshold_critical' => 2800, 'status' => 'Normal', 'unit' => 'trans/sec'],
            ['metric_name' => 'Log Flushes/sec', 'instance_name' => 'SQLPROD01', 'current_value' => 456, 'min_value' => 125, 'max_value' => 890, 'avg_value' => 400, 'threshold_warning' => 700, 'threshold_critical' => 1000, 'status' => 'Normal', 'unit' => 'flushes/sec']
        ];
    }

    /**
     * Get blocking sessions
     */
    public function getBlockingSessions()
    {
        return [
            ['session_id' => 55, 'blocking_session_id' => null, 'database_name' => 'ERP_DB', 'wait_type' => 'LCK_M_X', 'wait_time_sec' => 125, 'wait_resource' => 'KEY: 5:72057594039697408 (8194443284a0)', 'command' => 'UPDATE', 'status' => 'suspended', 'blocked_count' => 3, 'query_text' => "UPDATE Orders SET Status = 'Shipped' WHERE OrderID = 12345"],
            ['session_id' => 67, 'blocking_session_id' => 55, 'database_name' => 'ERP_DB', 'wait_type' => 'LCK_M_S', 'wait_time_sec' => 120, 'wait_resource' => 'KEY: 5:72057594039697408 (8194443284a0)', 'command' => 'SELECT', 'status' => 'suspended', 'blocked_count' => 0, 'query_text' => "SELECT * FROM Orders WHERE OrderID = 12345"],
            ['session_id' => 89, 'blocking_session_id' => 55, 'database_name' => 'ERP_DB', 'wait_type' => 'LCK_M_S', 'wait_time_sec' => 115, 'wait_resource' => 'KEY: 5:72057594039697408 (8194443284a0)', 'command' => 'SELECT', 'status' => 'suspended', 'blocked_count' => 0, 'query_text' => "SELECT OrderID, Status FROM Orders WHERE CustomerID = 567"]
        ];
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats()
    {
        $servers = $this->getMonitoredServers();
        $agJobs = $this->getAgentJobs();
        $backups = $this->getBackupStatus();

        $onlineCount = count(array_filter($servers, fn($s) => $s['status'] === 'online'));
        $failedJobs = count(array_filter($agJobs, fn($j) => $j['last_run_status'] === 'Failed'));
        $oldBackups = count(array_filter($backups, fn($b) => strpos($b['status'], 'Warning') !== false));

        return [
            'total_servers' => count($servers),
            'online_servers' => $onlineCount,
            'warning_servers' => count($servers) - $onlineCount,
            'total_jobs' => count($agJobs),
            'failed_jobs' => $failedJobs,
            'running_jobs' => count(array_filter($agJobs, fn($j) => $j['last_run_status'] === 'In Progress')),
            'total_backups' => count($backups),
            'backup_warnings' => $oldBackups,
            'avg_cpu' => round(array_sum(array_column($servers, 'cpu_usage')) / count($servers), 1),
            'avg_memory' => round(array_sum(array_column($servers, 'memory_usage')) / count($servers), 1)
        ];
    }
}
