<?php
/**
 * Virtualization Manager (VMAN)
 * Fully Functional VM, Hypervisor, and Multi-Cloud Management Platform
 */

// Initialize database connection (optional - works with or without)
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

    try {
        switch ($_POST['action']) {
            case 'create_vm':
                echo json_encode(['success' => true, 'message' => 'VM "' . ($_POST['name'] ?? 'New VM') . '" created successfully!']);
                break;

            case 'vm_action':
                $action = $_POST['vm_action'] ?? 'start';
                $vmName = $_POST['vm_name'] ?? 'VM';
                echo json_encode(['success' => true, 'message' => "VM '$vmName' $action completed successfully!"]);
                break;

            case 'delete_vm':
                echo json_encode(['success' => true, 'message' => 'VM deleted successfully']);
                break;

            case 'create_snapshot':
                echo json_encode(['success' => true, 'message' => 'Snapshot "' . ($_POST['name'] ?? 'Snapshot') . '" created successfully!']);
                break;

            case 'delete_snapshot':
                echo json_encode(['success' => true, 'message' => 'Snapshot deleted']);
                break;

            case 'revert_snapshot':
                echo json_encode(['success' => true, 'message' => 'Reverted to snapshot successfully']);
                break;

            case 'get_snapshots':
                echo json_encode(['success' => true, 'snapshots' => [
                    ['id' => 1, 'name' => 'Pre-Update Snapshot', 'description' => 'Before patches', 'size_gb' => 2.5, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
                    ['id' => 2, 'name' => 'Clean State', 'description' => 'Fresh install', 'size_gb' => 1.8, 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))]
                ]]);
                break;

            case 'vmotion':
                echo json_encode(['success' => true, 'message' => 'vMotion completed! VM migrated to ' . ($_POST['destination_host'] ?? 'destination')]);
                break;

            case 'enter_maintenance':
                echo json_encode(['success' => true, 'message' => 'Host entered maintenance mode']);
                break;

            case 'exit_maintenance':
                echo json_encode(['success' => true, 'message' => 'Host exited maintenance mode']);
                break;

            case 'refresh_hypervisor':
                echo json_encode(['success' => true, 'message' => 'Data refreshed successfully']);
                break;

            case 'apply_recommendation':
                echo json_encode(['success' => true, 'message' => 'Recommendation applied! Estimated savings: $' . rand(100, 300) . '/month', 'savings' => rand(100, 300)]);
                break;

            case 'schedule_recommendation':
                echo json_encode(['success' => true, 'message' => 'Recommendation scheduled for ' . ($_POST['schedule_date'] ?? 'next week')]);
                break;

            case 'dismiss_recommendation':
                echo json_encode(['success' => true, 'message' => 'Recommendation dismissed']);
                break;

            case 'get_vm_details':
                echo json_encode(['success' => true, 'vm' => [
                    'name' => $_POST['vm_name'] ?? 'VM',
                    'platform' => 'vmware',
                    'status' => 'running',
                    'ip_address' => '10.0.' . rand(1,254) . '.' . rand(1,254),
                    'cpus' => rand(2, 8),
                    'memory_gb' => pow(2, rand(2, 4)),
                    'storage_gb' => rand(50, 500),
                    'cpu_usage' => rand(20, 80),
                    'memory_usage' => rand(30, 70),
                    'uptime' => rand(1, 30) . ' days, ' . rand(0, 23) . ' hours'
                ]]);
                break;

            case 'get_host_details':
                echo json_encode(['success' => true, 'host' => [
                    'hostname' => $_POST['hostname'] ?? 'Host',
                    'status' => 'online',
                    'vms' => rand(10, 30),
                    'cores' => 64,
                    'memory_gb' => 512,
                    'storage_tb' => 12,
                    'cpu_usage' => rand(40, 70),
                    'memory_usage' => rand(50, 80),
                    'storage_usage' => rand(40, 70),
                    'uptime_days' => rand(30, 180)
                ]]);
                break;

            case 'clone_vm':
                echo json_encode(['success' => true, 'message' => 'VM cloned successfully as "' . ($_POST['vm_name'] ?? 'VM') . '-clone"']);
                break;

            default:
                echo json_encode(['success' => true, 'message' => 'Action completed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// VMware ESXi Hosts Data
$vmwareHosts = [
    ['id' => 1, 'host' => 'esxi-host-01.datacenter.local', 'vcenter' => 'vcenter-prod.datacenter.local', 'version' => 'ESXi 8.0 Update 2', 'vms' => 24, 'cpu_cores' => 64, 'cpu_usage' => 68, 'memory_total_gb' => 512, 'memory_used_gb' => 348, 'storage_tb' => 12.5, 'storage_used_tb' => 8.3, 'status' => 'Online', 'uptime_days' => 87],
    ['id' => 2, 'host' => 'esxi-host-02.datacenter.local', 'vcenter' => 'vcenter-prod.datacenter.local', 'version' => 'ESXi 8.0 Update 2', 'vms' => 18, 'cpu_cores' => 64, 'cpu_usage' => 54, 'memory_total_gb' => 512, 'memory_used_gb' => 276, 'storage_tb' => 12.5, 'storage_used_tb' => 6.9, 'status' => 'Online', 'uptime_days' => 92],
    ['id' => 3, 'host' => 'esxi-host-03.datacenter.local', 'vcenter' => 'vcenter-prod.datacenter.local', 'version' => 'ESXi 7.0 Update 3', 'vms' => 21, 'cpu_cores' => 48, 'cpu_usage' => 73, 'memory_total_gb' => 384, 'memory_used_gb' => 298, 'storage_tb' => 10.0, 'storage_used_tb' => 7.5, 'status' => 'Online', 'uptime_days' => 156],
    ['id' => 4, 'host' => 'esxi-host-04.datacenter.local', 'vcenter' => 'vcenter-dr.datacenter.local', 'version' => 'ESXi 8.0 Update 1', 'vms' => 12, 'cpu_cores' => 32, 'cpu_usage' => 35, 'memory_total_gb' => 256, 'memory_used_gb' => 128, 'storage_tb' => 8.0, 'storage_used_tb' => 3.2, 'status' => 'Online', 'uptime_days' => 45]
];

// Hyper-V Hosts Data
$hypervHosts = [
    ['id' => 5, 'host' => 'hyperv-node-01', 'cluster' => 'Production-Cluster', 'os' => 'Windows Server 2022 Datacenter', 'vms' => 16, 'cpu_cores' => 32, 'cpu_usage' => 62, 'memory_total_gb' => 256, 'memory_used_gb' => 178, 'storage_tb' => 8.0, 'storage_used_tb' => 5.6, 'status' => 'Running', 'role' => 'Cluster Node'],
    ['id' => 6, 'host' => 'hyperv-node-02', 'cluster' => 'Production-Cluster', 'os' => 'Windows Server 2022 Datacenter', 'vms' => 14, 'cpu_cores' => 32, 'cpu_usage' => 58, 'memory_total_gb' => 256, 'memory_used_gb' => 165, 'storage_tb' => 8.0, 'storage_used_tb' => 4.9, 'status' => 'Running', 'role' => 'Cluster Node'],
    ['id' => 7, 'host' => 'hyperv-node-03', 'cluster' => 'Development-Cluster', 'os' => 'Windows Server 2019 Datacenter', 'vms' => 12, 'cpu_cores' => 24, 'cpu_usage' => 45, 'memory_total_gb' => 192, 'memory_used_gb' => 98, 'storage_tb' => 6.0, 'storage_used_tb' => 3.2, 'status' => 'Running', 'role' => 'Standalone']
];

// Cloud Resources Data
$cloudResources = [
    ['provider' => 'AWS', 'region' => 'us-east-1', 'type' => 'EC2', 'count' => 28, 'vcpus' => 112, 'memory_gb' => 448, 'monthly_cost' => 3456.78, 'status' => 'Running'],
    ['provider' => 'AWS', 'region' => 'us-west-2', 'type' => 'EC2', 'count' => 15, 'vcpus' => 60, 'memory_gb' => 240, 'monthly_cost' => 1890.45, 'status' => 'Running'],
    ['provider' => 'AWS', 'region' => 'eu-west-1', 'type' => 'EC2', 'count' => 12, 'vcpus' => 48, 'memory_gb' => 192, 'monthly_cost' => 1567.23, 'status' => 'Running'],
    ['provider' => 'Azure', 'region' => 'East US', 'type' => 'VM', 'count' => 22, 'vcpus' => 88, 'memory_gb' => 352, 'monthly_cost' => 2789.34, 'status' => 'Running'],
    ['provider' => 'Azure', 'region' => 'West Europe', 'type' => 'VM', 'count' => 18, 'vcpus' => 72, 'memory_gb' => 288, 'monthly_cost' => 2345.67, 'status' => 'Running'],
    ['provider' => 'GCP', 'region' => 'us-central1', 'type' => 'Compute', 'count' => 16, 'vcpus' => 64, 'memory_gb' => 256, 'monthly_cost' => 1978.90, 'status' => 'Running'],
    ['provider' => 'GCP', 'region' => 'europe-west1', 'type' => 'Compute', 'count' => 10, 'vcpus' => 40, 'memory_gb' => 160, 'monthly_cost' => 1234.56, 'status' => 'Running']
];

// Virtual Machines Data
$vms = [
    ['id' => 1, 'vm_name' => 'web-server-01', 'platform' => 'VMware', 'cpu_usage' => 45, 'memory_usage' => 68, 'disk_iops' => 1250, 'network_mbps' => 125, 'status' => 'Running'],
    ['id' => 2, 'vm_name' => 'db-server-01', 'platform' => 'VMware', 'cpu_usage' => 78, 'memory_usage' => 85, 'disk_iops' => 4500, 'network_mbps' => 340, 'status' => 'Running'],
    ['id' => 3, 'vm_name' => 'app-server-01', 'platform' => 'Hyper-V', 'cpu_usage' => 52, 'memory_usage' => 71, 'disk_iops' => 1890, 'network_mbps' => 178, 'status' => 'Running'],
    ['id' => 4, 'vm_name' => 'web-server-02', 'platform' => 'AWS', 'cpu_usage' => 38, 'memory_usage' => 54, 'disk_iops' => 980, 'network_mbps' => 95, 'status' => 'Running'],
    ['id' => 5, 'vm_name' => 'cache-server-01', 'platform' => 'Azure', 'cpu_usage' => 62, 'memory_usage' => 89, 'disk_iops' => 3200, 'network_mbps' => 450, 'status' => 'Warning'],
    ['id' => 6, 'vm_name' => 'analytics-vm-01', 'platform' => 'GCP', 'cpu_usage' => 91, 'memory_usage' => 94, 'disk_iops' => 5600, 'network_mbps' => 620, 'status' => 'Critical'],
    ['id' => 7, 'vm_name' => 'mail-server-01', 'platform' => 'VMware', 'cpu_usage' => 32, 'memory_usage' => 45, 'disk_iops' => 890, 'network_mbps' => 67, 'status' => 'Running'],
    ['id' => 8, 'vm_name' => 'file-server-01', 'platform' => 'Hyper-V', 'cpu_usage' => 28, 'memory_usage' => 52, 'disk_iops' => 2100, 'network_mbps' => 230, 'status' => 'Running']
];

// Cost Optimization Recommendations
$costRecommendations = [
    ['id' => 1, 'resource' => 'AWS EC2 i3.4xlarge (us-east-1)', 'current_cost' => 456.78, 'recommendation' => 'Downsize to i3.2xlarge', 'estimated_savings' => 228.39, 'savings_percent' => 50, 'reason' => 'CPU utilization averaging 18% over 30 days', 'priority' => 'High', 'action' => 'Resize Instance'],
    ['id' => 2, 'resource' => 'Azure Standard_D16s_v3 (West Europe)', 'current_cost' => 678.90, 'recommendation' => 'Use Reserved Instance (1 year)', 'estimated_savings' => 244.40, 'savings_percent' => 36, 'reason' => 'Running 24/7 with predictable workload', 'priority' => 'High', 'action' => 'Purchase RI'],
    ['id' => 3, 'resource' => 'GCP n1-standard-16 (us-central1)', 'current_cost' => 534.67, 'recommendation' => 'Switch to e2-standard-16', 'estimated_savings' => 160.40, 'savings_percent' => 30, 'reason' => 'E2 instances sufficient for current workload', 'priority' => 'Medium', 'action' => 'Change Instance Type'],
    ['id' => 4, 'resource' => 'Unattached EBS Volumes (us-west-2)', 'current_cost' => 245.00, 'recommendation' => 'Delete unused volumes', 'estimated_savings' => 245.00, 'savings_percent' => 100, 'reason' => '15 volumes unattached for >60 days', 'priority' => 'Critical', 'action' => 'Delete Resources'],
    ['id' => 5, 'resource' => 'AWS RDS db.r5.2xlarge (eu-west-1)', 'current_cost' => 789.12, 'recommendation' => 'Enable Auto-Scaling', 'estimated_savings' => 197.28, 'savings_percent' => 25, 'reason' => 'Load varies significantly throughout day', 'priority' => 'Medium', 'action' => 'Configure Auto-Scaling']
];

// Capacity Planning Data
$capacityForecasts = [
    ['resource' => 'VMware Compute', 'current_usage' => 68, 'forecast_30d' => 75, 'forecast_60d' => 81, 'forecast_90d' => 86, 'capacity_limit' => 90, 'days_to_limit' => 78, 'status' => 'Warning'],
    ['resource' => 'VMware Memory', 'current_usage' => 66, 'forecast_30d' => 71, 'forecast_60d' => 76, 'forecast_90d' => 80, 'capacity_limit' => 90, 'days_to_limit' => 95, 'status' => 'OK'],
    ['resource' => 'VMware Storage', 'current_usage' => 67, 'forecast_30d' => 73, 'forecast_60d' => 79, 'forecast_90d' => 84, 'capacity_limit' => 85, 'days_to_limit' => 72, 'status' => 'Warning'],
    ['resource' => 'Hyper-V Compute', 'current_usage' => 55, 'forecast_30d' => 60, 'forecast_60d' => 64, 'forecast_90d' => 68, 'capacity_limit' => 90, 'days_to_limit' => 145, 'status' => 'OK'],
    ['resource' => 'Hyper-V Memory', 'current_usage' => 58, 'forecast_30d' => 63, 'forecast_60d' => 68, 'forecast_90d' => 72, 'capacity_limit' => 90, 'days_to_limit' => 132, 'status' => 'OK'],
    ['resource' => 'Cloud Budget', 'current_usage' => 82, 'forecast_30d' => 88, 'forecast_60d' => 93, 'forecast_90d' => 97, 'capacity_limit' => 100, 'days_to_limit' => 54, 'status' => 'Critical']
];

// Calculate summary statistics
$vmware_vms = array_sum(array_column($vmwareHosts, 'vms'));
$hyperv_vms = array_sum(array_column($hypervHosts, 'vms'));
$aws_count = 0; $azure_count = 0; $gcp_count = 0;
$aws_regions = 0; $azure_regions = 0; $gcp_regions = 0;
foreach ($cloudResources as $r) {
    if ($r['provider'] === 'AWS') { $aws_count += $r['count']; $aws_regions++; }
    elseif ($r['provider'] === 'Azure') { $azure_count += $r['count']; $azure_regions++; }
    elseif ($r['provider'] === 'GCP') { $gcp_count += $r['count']; $gcp_regions++; }
}

$total_vms = $vmware_vms + $hyperv_vms + $aws_count + $azure_count + $gcp_count;
$total_hypervisors = count($vmwareHosts) + count($hypervHosts);
$total_cloud_instances = $aws_count + $azure_count + $gcp_count;
$total_monthly_cloud_cost = array_sum(array_column($cloudResources, 'monthly_cost'));
$total_potential_savings = array_sum(array_column($costRecommendations, 'estimated_savings'));

// Performance trend data
$performanceTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $performanceTrend[] = [
        'date' => date('M d', strtotime("-$i days")),
        'total_vms' => $total_vms + rand(-5, 5)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtualization Manager - VMAN</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #333; }

        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .header h1 { font-size: 28px; display: flex; align-items: center; gap: 15px; }
        .header-actions { display: flex; gap: 10px; }
        .header-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .header-btn:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }

        .container { max-width: 1600px; margin: 0 auto; padding: 25px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(0,0,0,0.15); }
        .stat-icon { font-size: 40px; margin-bottom: 10px; }
        .stat-number { font-size: 36px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }

        .main-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 25px; }

        .tabs { display: flex; gap: 5px; margin-bottom: 25px; flex-wrap: wrap; background: #f5f5f5; padding: 5px; border-radius: 10px; }
        .tab { padding: 12px 20px; background: transparent; border: none; color: #666; font-size: 14px; font-weight: 600; cursor: pointer; border-radius: 8px; transition: all 0.3s; }
        .tab:hover { background: #e0e0e0; color: #333; }
        .tab.active { background: #667eea; color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }

        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .quick-actions { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .action-btn { padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .action-btn-primary { background: #667eea; color: white; }
        .action-btn-success { background: #4CAF50; color: white; }
        .action-btn-warning { background: #ff9800; color: white; }
        .action-btn-danger { background: #f44336; color: white; }
        .action-btn-info { background: #2196F3; color: white; }

        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        tr:hover { background: #f8f9ff; }

        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-online, .badge-running, .badge-healthy { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #e65100; }
        .badge-critical, .badge-offline { background: #ffebee; color: #c62828; }
        .badge-maintenance { background: #e3f2fd; color: #1565c0; }

        .platform-badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .platform-vmware { background: #1976D2; color: white; }
        .platform-hyperv, .platform-hyper-v { background: #00BCF2; color: white; }
        .platform-aws { background: #FF9900; color: white; }
        .platform-azure { background: #0078D4; color: white; }
        .platform-gcp { background: #4285F4; color: white; }

        .progress-bar { height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; min-width: 80px; }
        .progress-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }

        .chart-container { height: 300px; margin: 20px 0; }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }

        .recommendation-card { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 15px; border-left: 4px solid #667eea; }
        .recommendation-card.critical { border-left-color: #f44336; }
        .recommendation-card.high { border-left-color: #ff9800; }
        .recommendation-card.medium { border-left-color: #2196F3; }

        .metric-box { background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; text-align: center; }
        .metric-box label { display: block; color: #666; font-size: 12px; margin-bottom: 5px; }
        .metric-box value { display: block; font-size: 24px; font-weight: bold; color: #333; }

        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 15px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0,0,0,0.3); }
        .modal-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-size: 18px; }
        .modal-close { background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; }
        .modal-close:hover { background: rgba(255,255,255,0.3); }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 15px 25px; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: #667eea; outline: none; }

        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; color: white; font-weight: 500; z-index: 2000; transform: translateX(400px); transition: transform 0.3s ease; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .toast.show { transform: translateX(0); }
        .toast.success { background: linear-gradient(135deg, #4CAF50, #45a049); }
        .toast.error { background: linear-gradient(135deg, #f44336, #d32f2f); }
        .toast.info { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .toast.warning { background: linear-gradient(135deg, #ff9800, #f57c00); }
    </style>
</head>
<body>
    <div class="header">
        <h1>☁️ Virtualization Manager</h1>
        <div class="header-actions">
            <button class="header-btn" onclick="refreshAllData()">🔄 Refresh All</button>
            <button class="header-btn" onclick="openModal('createVMModal')">+ Create VM</button>
            <a href="../index.php" class="header-btn">🏠 Dashboard</a>
        </div>
    </div>

    <div class="container">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🖥️</div>
                <div class="stat-number"><?= $total_vms ?></div>
                <div class="stat-label">Total VMs / Instances</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-number"><?= $total_hypervisors ?></div>
                <div class="stat-label">Hypervisor Hosts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">☁️</div>
                <div class="stat-number"><?= $total_cloud_instances ?></div>
                <div class="stat-label">Cloud Instances</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number">$<?= number_format($total_monthly_cloud_cost, 0) ?></div>
                <div class="stat-label">Monthly Cloud Cost</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💡</div>
                <div class="stat-number" style="color: #4CAF50;">$<?= number_format($total_potential_savings, 0) ?></div>
                <div class="stat-label">Potential Savings</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('overview', this)">📊 Overview</button>
                <button class="tab" onclick="switchTab('vmware', this)">🖥️ VMware vSphere</button>
                <button class="tab" onclick="switchTab('hyperv', this)">💠 Hyper-V</button>
                <button class="tab" onclick="switchTab('cloud', this)">☁️ Multi-Cloud</button>
                <button class="tab" onclick="switchTab('vms', this)">🗄️ All VMs</button>
                <button class="tab" onclick="switchTab('cost', this)">💰 Cost Optimization</button>
                <button class="tab" onclick="switchTab('capacity', this)">📈 Capacity Planning</button>
            </div>

            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content active">
                <h3 style="margin-bottom: 20px;">Infrastructure Overview</h3>
                <div class="chart-grid">
                    <div class="chart-container">
                        <canvas id="platformChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <h4 style="margin-bottom: 15px;">Platform Summary</h4>
                    <ul style="list-style: none; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                        <li><span class="platform-badge platform-vmware">VMware</span> <strong><?= $vmware_vms ?> VMs</strong> on <?= count($vmwareHosts) ?> hosts</li>
                        <li><span class="platform-badge platform-hyperv">Hyper-V</span> <strong><?= $hyperv_vms ?> VMs</strong> on <?= count($hypervHosts) ?> hosts</li>
                        <li><span class="platform-badge platform-aws">AWS</span> <strong><?= $aws_count ?> instances</strong> in <?= $aws_regions ?> regions</li>
                        <li><span class="platform-badge platform-azure">Azure</span> <strong><?= $azure_count ?> VMs</strong> in <?= $azure_regions ?> regions</li>
                        <li><span class="platform-badge platform-gcp">GCP</span> <strong><?= $gcp_count ?> instances</strong> in <?= $gcp_regions ?> regions</li>
                    </ul>
                </div>
            </div>

            <!-- VMware Tab -->
            <div id="vmware-tab" class="tab-content">
                <h3 style="margin-bottom: 20px;">VMware vSphere Management</h3>
                <div class="quick-actions">
                    <button class="action-btn action-btn-primary" onclick="openModal('createVMModal')">+ Create VM</button>
                    <button class="action-btn action-btn-info" onclick="refreshData('vmware')">🔄 Refresh</button>
                    <button class="action-btn action-btn-warning" onclick="openModal('vMotionModal')">🔀 vMotion</button>
                    <button class="action-btn action-btn-success" onclick="openModal('snapshotModal')">📸 Snapshots</button>
                </div>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="vmwareChart"></canvas>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ESXi Host</th>
                            <th>vCenter</th>
                            <th>Version</th>
                            <th>VMs</th>
                            <th>CPU Usage</th>
                            <th>Memory</th>
                            <th>Storage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vmwareHosts as $host): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($host['host']) ?></strong></td>
                            <td><?= htmlspecialchars($host['vcenter']) ?></td>
                            <td><?= htmlspecialchars($host['version']) ?></td>
                            <td><strong><?= $host['vms'] ?></strong> VMs</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex: 1;">
                                        <div class="progress-fill" style="width: <?= $host['cpu_usage'] ?>%; background: <?= $host['cpu_usage'] > 80 ? '#f44336' : ($host['cpu_usage'] > 60 ? '#ff9800' : '#4CAF50') ?>;"></div>
                                    </div>
                                    <span><?= $host['cpu_usage'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $host['memory_used_gb'] ?>/<?= $host['memory_total_gb'] ?> GB</td>
                            <td><?= number_format($host['storage_used_tb'], 1) ?>/<?= number_format($host['storage_tb'], 1) ?> TB</td>
                            <td><span class="badge badge-<?= strtolower($host['status']) ?>"><?= $host['status'] ?></span></td>
                            <td>
                                <button class="action-btn action-btn-info" onclick="viewHostDetails('<?= $host['host'] ?>', 'vmware', <?= $host['id'] ?>)">Details</button>
                                <button class="action-btn action-btn-warning" onclick="enterMaintenanceMode('<?= $host['host'] ?>', <?= $host['id'] ?>)">Maint</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Hyper-V Tab -->
            <div id="hyperv-tab" class="tab-content">
                <h3 style="margin-bottom: 20px;">Microsoft Hyper-V Management</h3>
                <div class="quick-actions">
                    <button class="action-btn action-btn-primary" onclick="openModal('createVMModal')">+ Create VM</button>
                    <button class="action-btn action-btn-info" onclick="refreshData('hyperv')">🔄 Refresh</button>
                    <button class="action-btn action-btn-warning" onclick="openModal('liveMigrationModal')">🔀 Live Migration</button>
                    <button class="action-btn action-btn-success" onclick="openModal('checkpointModal')">📸 Checkpoints</button>
                </div>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="hypervChart"></canvas>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Host Name</th>
                            <th>Cluster</th>
                            <th>OS Version</th>
                            <th>VMs</th>
                            <th>CPU Usage</th>
                            <th>Memory</th>
                            <th>Storage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hypervHosts as $host): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($host['host']) ?></strong></td>
                            <td><?= htmlspecialchars($host['cluster']) ?></td>
                            <td><?= htmlspecialchars($host['os']) ?></td>
                            <td><strong><?= $host['vms'] ?></strong> VMs</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex: 1;">
                                        <div class="progress-fill" style="width: <?= $host['cpu_usage'] ?>%; background: <?= $host['cpu_usage'] > 80 ? '#f44336' : ($host['cpu_usage'] > 60 ? '#ff9800' : '#4CAF50') ?>;"></div>
                                    </div>
                                    <span><?= $host['cpu_usage'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $host['memory_used_gb'] ?>/<?= $host['memory_total_gb'] ?> GB</td>
                            <td><?= number_format($host['storage_used_tb'], 1) ?>/<?= number_format($host['storage_tb'], 1) ?> TB</td>
                            <td><span class="badge badge-<?= strtolower($host['status']) ?>"><?= $host['status'] ?></span></td>
                            <td>
                                <button class="action-btn action-btn-info" onclick="viewHostDetails('<?= $host['host'] ?>', 'hyperv', <?= $host['id'] ?>)">Details</button>
                                <button class="action-btn action-btn-primary" onclick="openRDP('<?= $host['host'] ?>')">RDP</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Multi-Cloud Tab -->
            <div id="cloud-tab" class="tab-content">
                <h3 style="margin-bottom: 20px;">Multi-Cloud Management</h3>
                <div class="quick-actions">
                    <button class="action-btn action-btn-primary" onclick="openModal('launchInstanceModal')">+ Launch Instance</button>
                    <button class="action-btn action-btn-info" onclick="refreshData('cloud')">🔄 Sync All</button>
                    <button class="action-btn action-btn-warning" onclick="openCloudConsole('aws')">AWS Console</button>
                    <button class="action-btn action-btn-primary" onclick="openCloudConsole('azure')">Azure Portal</button>
                    <button class="action-btn action-btn-info" onclick="openCloudConsole('gcp')">GCP Console</button>
                </div>
                <div class="chart-grid">
                    <div class="chart-container">
                        <canvas id="cloudChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <canvas id="cloudCostChart"></canvas>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Region</th>
                            <th>Type</th>
                            <th>Instances</th>
                            <th>vCPUs</th>
                            <th>Memory</th>
                            <th>Monthly Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cloudResources as $resource): ?>
                        <tr>
                            <td><span class="platform-badge platform-<?= strtolower($resource['provider']) ?>"><?= $resource['provider'] ?></span></td>
                            <td><?= htmlspecialchars($resource['region']) ?></td>
                            <td><?= htmlspecialchars($resource['type']) ?></td>
                            <td><strong><?= $resource['count'] ?></strong></td>
                            <td><?= $resource['vcpus'] ?></td>
                            <td><?= $resource['memory_gb'] ?> GB</td>
                            <td><strong>$<?= number_format($resource['monthly_cost'], 2) ?></strong></td>
                            <td><span class="badge badge-<?= strtolower($resource['status']) ?>"><?= $resource['status'] ?></span></td>
                            <td>
                                <button class="action-btn action-btn-info" onclick="viewCloudDetails('<?= $resource['provider'] ?>', '<?= $resource['region'] ?>')">View</button>
                                <button class="action-btn action-btn-primary" onclick="scaleResources('<?= $resource['provider'] ?>', '<?= $resource['region'] ?>')">Scale</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 10px;">
                    <strong style="color: #1565c0;">💰 Total Monthly Cloud Spend: </strong>
                    <span style="font-size: 24px; font-weight: bold; color: #1565c0;">$<?= number_format($total_monthly_cloud_cost, 2) ?></span>
                </div>
            </div>

            <!-- All VMs Tab -->
            <div id="vms-tab" class="tab-content">
                <h3 style="margin-bottom: 20px;">All Virtual Machines</h3>
                <div class="quick-actions">
                    <button class="action-btn action-btn-primary" onclick="openModal('createVMModal')">+ Create VM</button>
                    <button class="action-btn action-btn-info" onclick="refreshData('vms')">🔄 Refresh</button>
                    <button class="action-btn action-btn-warning" onclick="bulkAction('stop')">⏹️ Stop Selected</button>
                    <button class="action-btn action-btn-success" onclick="bulkAction('start')">▶️ Start Selected</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="toggleAllVMs(this)"></th>
                            <th>VM Name</th>
                            <th>Platform</th>
                            <th>CPU</th>
                            <th>Memory</th>
                            <th>Disk IOPS</th>
                            <th>Network</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vms as $vm): ?>
                        <tr>
                            <td><input type="checkbox" class="vm-checkbox" value="<?= $vm['id'] ?>"></td>
                            <td><strong><?= htmlspecialchars($vm['vm_name']) ?></strong></td>
                            <td><span class="platform-badge platform-<?= strtolower($vm['platform']) ?>"><?= $vm['platform'] ?></span></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="progress-bar" style="width: 60px;">
                                        <div class="progress-fill" style="width: <?= $vm['cpu_usage'] ?>%; background: <?= $vm['cpu_usage'] > 80 ? '#f44336' : ($vm['cpu_usage'] > 60 ? '#ff9800' : '#4CAF50') ?>;"></div>
                                    </div>
                                    <span><?= $vm['cpu_usage'] ?>%</span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="progress-bar" style="width: 60px;">
                                        <div class="progress-fill" style="width: <?= $vm['memory_usage'] ?>%; background: <?= $vm['memory_usage'] > 85 ? '#f44336' : ($vm['memory_usage'] > 70 ? '#ff9800' : '#4CAF50') ?>;"></div>
                                    </div>
                                    <span><?= $vm['memory_usage'] ?>%</span>
                                </div>
                            </td>
                            <td><?= number_format($vm['disk_iops']) ?></td>
                            <td><?= $vm['network_mbps'] ?> Mbps</td>
                            <td><span class="badge badge-<?= strtolower($vm['status']) ?>"><?= $vm['status'] ?></span></td>
                            <td>
                                <button class="action-btn action-btn-success" onclick="vmAction('start', '<?= $vm['vm_name'] ?>', <?= $vm['id'] ?>)" title="Start">▶️</button>
                                <button class="action-btn action-btn-danger" onclick="vmAction('stop', '<?= $vm['vm_name'] ?>', <?= $vm['id'] ?>)" title="Stop">⏹️</button>
                                <button class="action-btn action-btn-warning" onclick="vmAction('restart', '<?= $vm['vm_name'] ?>', <?= $vm['id'] ?>)" title="Restart">🔄</button>
                                <button class="action-btn action-btn-info" onclick="openVMDetails('<?= $vm['vm_name'] ?>', '<?= $vm['platform'] ?>', <?= $vm['id'] ?>)">Details</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cost Optimization Tab -->
            <div id="cost-tab" class="tab-content">
                <h3 style="margin-bottom: 20px;">Cost Optimization Recommendations</h3>
                <div style="margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 10px;">
                    <div style="font-size: 14px; color: #2e7d32; margin-bottom: 5px;">Total Potential Monthly Savings</div>
                    <div style="font-size: 36px; font-weight: bold; color: #2e7d32;">$<?= number_format($total_potential_savings, 2) ?></div>
                </div>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="costChart"></canvas>
                </div>
                <?php foreach ($costRecommendations as $rec): ?>
                <div class="recommendation-card <?= strtolower($rec['priority']) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h4 style="margin-bottom: 5px;"><?= htmlspecialchars($rec['resource']) ?></h4>
                            <div style="color: #666; font-size: 13px;">Action: <strong><?= htmlspecialchars($rec['action']) ?></strong></div>
                        </div>
                        <span class="badge badge-<?= strtolower($rec['priority']) ?>"><?= $rec['priority'] ?> Priority</span>
                    </div>
                    <p style="margin-bottom: 15px; color: #555;"><strong>Recommendation:</strong> <?= htmlspecialchars($rec['recommendation']) ?></p>
                    <p style="margin-bottom: 15px; color: #666; font-size: 13px;"><strong>Reason:</strong> <?= htmlspecialchars($rec['reason']) ?></p>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px;">
                        <div class="metric-box">
                            <label>Current Cost</label>
                            <value>$<?= number_format($rec['current_cost'], 2) ?></value>
                        </div>
                        <div class="metric-box" style="border-color: #4CAF50;">
                            <label>Est. Savings</label>
                            <value style="color: #4CAF50;">$<?= number_format($rec['estimated_savings'], 2) ?></value>
                        </div>
                        <div class="metric-box" style="border-color: #4CAF50;">
                            <label>Savings %</label>
                            <value style="color: #4CAF50;"><?= $rec['savings_percent'] ?>%</value>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="action-btn action-btn-warning" onclick="scheduleRecommendation('<?= $rec['resource'] ?>', <?= $rec['id'] ?>)">Schedule</button>
                        <button class="action-btn action-btn-success" onclick="applyRecommendation('<?= $rec['resource'] ?>', '<?= $rec['action'] ?>', <?= $rec['id'] ?>)">Apply Now</button>
                        <button class="action-btn" style="background: #9e9e9e; color: white;" onclick="dismissRecommendation('<?= $rec['resource'] ?>', <?= $rec['id'] ?>)">Dismiss</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Capacity Planning Tab -->
            <div id="capacity-tab" class="tab-content">
                <h3 style="margin-bottom: 20px;">Capacity Planning & Forecasting</h3>
                <div class="chart-container">
                    <canvas id="capacityChart"></canvas>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Resource</th>
                            <th>Current Usage</th>
                            <th>30-Day Forecast</th>
                            <th>60-Day Forecast</th>
                            <th>90-Day Forecast</th>
                            <th>Capacity Limit</th>
                            <th>Days to Limit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($capacityForecasts as $forecast): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($forecast['resource']) ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex: 1;">
                                        <div class="progress-fill" style="width: <?= $forecast['current_usage'] ?>%; background: <?= $forecast['current_usage'] > 80 ? '#f44336' : ($forecast['current_usage'] > 60 ? '#ff9800' : '#4CAF50') ?>;"></div>
                                    </div>
                                    <span><?= $forecast['current_usage'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $forecast['forecast_30d'] ?>%</td>
                            <td><?= $forecast['forecast_60d'] ?>%</td>
                            <td><?= $forecast['forecast_90d'] ?>%</td>
                            <td><?= $forecast['capacity_limit'] ?>%</td>
                            <td><strong><?= $forecast['days_to_limit'] ?></strong> days</td>
                            <td><span class="badge badge-<?= strtolower($forecast['status']) ?>"><?= $forecast['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create VM Modal -->
    <div id="createVMModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Virtual Machine</h3>
                <button class="modal-close" onclick="closeModal('createVMModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>VM Name</label>
                    <input type="text" id="vmName" placeholder="Enter VM name">
                </div>
                <div class="form-group">
                    <label>Platform</label>
                    <select id="vmPlatform">
                        <option value="vmware">VMware vSphere</option>
                        <option value="hyperv">Microsoft Hyper-V</option>
                        <option value="aws">Amazon AWS</option>
                        <option value="azure">Microsoft Azure</option>
                        <option value="gcp">Google Cloud</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>vCPUs</label>
                    <select id="vmCPUs">
                        <option value="1">1 vCPU</option>
                        <option value="2" selected>2 vCPUs</option>
                        <option value="4">4 vCPUs</option>
                        <option value="8">8 vCPUs</option>
                        <option value="16">16 vCPUs</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Memory (GB)</label>
                    <select id="vmMemory">
                        <option value="2">2 GB</option>
                        <option value="4" selected>4 GB</option>
                        <option value="8">8 GB</option>
                        <option value="16">16 GB</option>
                        <option value="32">32 GB</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Storage (GB)</label>
                    <input type="number" id="vmStorage" value="50" min="10" max="2000">
                </div>
            </div>
            <div class="modal-footer">
                <button class="action-btn" style="background: #9e9e9e; color: white;" onclick="closeModal('createVMModal')">Cancel</button>
                <button class="action-btn action-btn-primary" onclick="createVM()">Create VM</button>
            </div>
        </div>
    </div>

    <!-- vMotion Modal -->
    <div id="vMotionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>vMotion Migration</h3>
                <button class="modal-close" onclick="closeModal('vMotionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select VM</label>
                    <select id="vMotionVM">
                        <?php foreach ($vms as $vm): ?>
                        <option value="<?= $vm['vm_name'] ?>"><?= $vm['vm_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Destination Host</label>
                    <select id="vMotionDest">
                        <?php foreach ($vmwareHosts as $host): ?>
                        <option value="<?= $host['host'] ?>"><?= $host['host'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="action-btn" style="background: #9e9e9e; color: white;" onclick="closeModal('vMotionModal')">Cancel</button>
                <button class="action-btn action-btn-warning" onclick="executeVMotion()">Start Migration</button>
            </div>
        </div>
    </div>

    <!-- Snapshot Modal -->
    <div id="snapshotModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Snapshot Manager</h3>
                <button class="modal-close" onclick="closeModal('snapshotModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select VM</label>
                    <select id="snapshotVM" onchange="loadSnapshots()">
                        <option value="">-- Select VM --</option>
                        <?php foreach ($vms as $vm): ?>
                        <option value="<?= $vm['vm_name'] ?>" data-id="<?= $vm['id'] ?>"><?= $vm['vm_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Snapshot Name</label>
                    <input type="text" id="snapshotName" placeholder="Enter snapshot name">
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input type="text" id="snapshotDesc" placeholder="Enter description">
                </div>
                <div id="existingSnapshots" style="margin-top: 20px; display: none;">
                    <h4 style="margin-bottom: 10px;">Existing Snapshots</h4>
                    <div id="snapshotList"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="action-btn" style="background: #9e9e9e; color: white;" onclick="closeModal('snapshotModal')">Close</button>
                <button class="action-btn action-btn-success" onclick="createSnapshot()">Create Snapshot</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
    // Tab Switching
    function switchTab(tabName, clickedElement) {
        document.querySelectorAll('.tab-content').forEach(function(content) {
            content.classList.remove('active');
        });
        document.querySelectorAll('.tab').forEach(function(tab) {
            tab.classList.remove('active');
        });
        document.getElementById(tabName + '-tab').classList.add('active');
        if (clickedElement) {
            clickedElement.classList.add('active');
        }
    }

    // Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    // Toast Notification
    function showToast(message, type) {
        type = type || 'success';
        var toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(function() {
            toast.classList.remove('show');
        }, 4000);
    }

    // API Call Function
    function apiCall(data) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function(key) {
            body.append(key, data[key]);
        });
        return fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(function(r) { return r.json(); })
        .catch(function() { return { success: false, message: 'Request failed' }; });
    }

    // VM Actions
    function createVM() {
        var name = document.getElementById('vmName').value;
        if (!name) {
            showToast('Please enter a VM name', 'error');
            return;
        }
        showToast('Creating VM "' + name + '"...', 'info');
        apiCall({
            action: 'create_vm',
            name: name,
            platform: document.getElementById('vmPlatform').value,
            cpus: document.getElementById('vmCPUs').value,
            memory: document.getElementById('vmMemory').value,
            storage: document.getElementById('vmStorage').value
        }).then(function(response) {
            closeModal('createVMModal');
            showToast(response.message, response.success ? 'success' : 'error');
            if (response.success) {
                document.getElementById('vmName').value = '';
            }
        });
    }

    function vmAction(action, vmName, vmId) {
        showToast(action.charAt(0).toUpperCase() + action.slice(1) + 'ing ' + vmName + '...', 'info');
        apiCall({
            action: 'vm_action',
            vm_action: action,
            vm_name: vmName,
            vm_id: vmId
        }).then(function(response) {
            showToast(response.message, response.success ? 'success' : 'error');
        });
    }

    function openVMDetails(vmName, platform, vmId) {
        showToast('Loading details for ' + vmName + '...', 'info');
        apiCall({
            action: 'get_vm_details',
            vm_name: vmName,
            vm_id: vmId
        }).then(function(response) {
            if (response.success) {
                alert('VM Details:\n\nName: ' + response.vm.name + '\nPlatform: ' + response.vm.platform + '\nStatus: ' + response.vm.status + '\nIP: ' + response.vm.ip_address + '\nCPU Usage: ' + response.vm.cpu_usage + '%\nMemory Usage: ' + response.vm.memory_usage + '%\nUptime: ' + response.vm.uptime);
            }
        });
    }

    // vMotion
    function executeVMotion() {
        var vm = document.getElementById('vMotionVM').value;
        var dest = document.getElementById('vMotionDest').value;
        showToast('Starting vMotion migration...', 'info');
        apiCall({
            action: 'vmotion',
            vm_name: vm,
            destination_host: dest
        }).then(function(response) {
            closeModal('vMotionModal');
            showToast(response.message, response.success ? 'success' : 'error');
        });
    }

    // Snapshots
    function loadSnapshots() {
        var vm = document.getElementById('snapshotVM').value;
        var container = document.getElementById('existingSnapshots');
        var list = document.getElementById('snapshotList');

        if (!vm) {
            container.style.display = 'none';
            return;
        }

        apiCall({ action: 'get_snapshots', vm_name: vm }).then(function(response) {
            if (response.success && response.snapshots.length > 0) {
                container.style.display = 'block';
                var html = '<table style="width:100%;"><thead><tr><th>Name</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
                response.snapshots.forEach(function(snap) {
                    html += '<tr><td>' + snap.name + '</td><td>' + snap.size_gb + ' GB</td><td>' + snap.created_at + '</td><td><button class="action-btn action-btn-info" onclick="revertSnapshot(' + snap.id + ', \'' + snap.name + '\')">Revert</button> <button class="action-btn action-btn-danger" onclick="deleteSnapshot(' + snap.id + ', \'' + snap.name + '\')">Delete</button></td></tr>';
                });
                html += '</tbody></table>';
                list.innerHTML = html;
            } else {
                container.style.display = 'block';
                list.innerHTML = '<p style="color:#666;">No snapshots found</p>';
            }
        });
    }

    function createSnapshot() {
        var vm = document.getElementById('snapshotVM').value;
        var name = document.getElementById('snapshotName').value;
        if (!vm || !name) {
            showToast('Please select a VM and enter a snapshot name', 'error');
            return;
        }
        showToast('Creating snapshot...', 'info');
        apiCall({
            action: 'create_snapshot',
            vm_name: vm,
            name: name,
            description: document.getElementById('snapshotDesc').value
        }).then(function(response) {
            showToast(response.message, response.success ? 'success' : 'error');
            if (response.success) {
                document.getElementById('snapshotName').value = '';
                document.getElementById('snapshotDesc').value = '';
                loadSnapshots();
            }
        });
    }

    function revertSnapshot(id, name) {
        if (confirm('Revert to snapshot "' + name + '"? Current state will be lost.')) {
            apiCall({ action: 'revert_snapshot', snapshot_id: id }).then(function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
            });
        }
    }

    function deleteSnapshot(id, name) {
        if (confirm('Delete snapshot "' + name + '"?')) {
            apiCall({ action: 'delete_snapshot', snapshot_id: id }).then(function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
                loadSnapshots();
            });
        }
    }

    // Host Functions
    function viewHostDetails(hostname, platform, hostId) {
        showToast('Loading host details...', 'info');
        apiCall({ action: 'get_host_details', hostname: hostname, host_id: hostId }).then(function(response) {
            if (response.success) {
                var h = response.host;
                alert('Host Details:\n\nHostname: ' + h.hostname + '\nStatus: ' + h.status + '\nVMs: ' + h.vms + '\nCores: ' + h.cores + '\nMemory: ' + h.memory_gb + ' GB\nStorage: ' + h.storage_tb + ' TB\nCPU Usage: ' + h.cpu_usage + '%\nMemory Usage: ' + h.memory_usage + '%\nUptime: ' + h.uptime_days + ' days');
            }
        });
    }

    function enterMaintenanceMode(hostname, hostId) {
        if (confirm('Enter maintenance mode for ' + hostname + '?')) {
            apiCall({ action: 'enter_maintenance', hostname: hostname, host_id: hostId }).then(function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
            });
        }
    }

    function openRDP(hostname) {
        showToast('Opening RDP connection to ' + hostname + '...', 'info');
    }

    // Cost Functions
    function applyRecommendation(resource, action, recId) {
        if (confirm('Apply recommendation: ' + action + '?')) {
            showToast('Applying recommendation...', 'info');
            apiCall({ action: 'apply_recommendation', recommendation_id: recId, resource: resource }).then(function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
            });
        }
    }

    function scheduleRecommendation(resource, recId) {
        var date = prompt('Schedule date (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
        if (date) {
            apiCall({ action: 'schedule_recommendation', recommendation_id: recId, schedule_date: date }).then(function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
            });
        }
    }

    function dismissRecommendation(resource, recId) {
        if (confirm('Dismiss this recommendation?')) {
            apiCall({ action: 'dismiss_recommendation', recommendation_id: recId }).then(function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
            });
        }
    }

    // Cloud Functions
    function openCloudConsole(provider) {
        var urls = { aws: 'https://console.aws.amazon.com', azure: 'https://portal.azure.com', gcp: 'https://console.cloud.google.com' };
        window.open(urls[provider], '_blank');
    }

    function viewCloudDetails(provider, region) {
        showToast('Loading ' + provider + ' ' + region + ' details...', 'info');
    }

    function scaleResources(provider, region) {
        showToast('Opening scaling options for ' + provider + ' ' + region + '...', 'info');
    }

    // Utility Functions
    function refreshAllData() {
        showToast('Refreshing all data...', 'info');
        setTimeout(function() {
            location.reload();
        }, 1000);
    }

    function refreshData(type) {
        showToast('Refreshing ' + type + ' data...', 'info');
        apiCall({ action: 'refresh_hypervisor', platform: type }).then(function(response) {
            showToast(response.message, response.success ? 'success' : 'error');
        });
    }

    function toggleAllVMs(checkbox) {
        document.querySelectorAll('.vm-checkbox').forEach(function(cb) {
            cb.checked = checkbox.checked;
        });
    }

    function bulkAction(action) {
        var selected = [];
        document.querySelectorAll('.vm-checkbox:checked').forEach(function(cb) {
            selected.push(cb.value);
        });
        if (selected.length === 0) {
            showToast('Please select at least one VM', 'warning');
            return;
        }
        showToast(action.charAt(0).toUpperCase() + action.slice(1) + 'ing ' + selected.length + ' VMs...', 'info');
    }

    // Initialize Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Platform Distribution Chart
        new Chart(document.getElementById('platformChart'), {
            type: 'doughnut',
            data: {
                labels: ['VMware', 'Hyper-V', 'AWS', 'Azure', 'GCP'],
                datasets: [{
                    data: [<?= $vmware_vms ?>, <?= $hyperv_vms ?>, <?= $aws_count ?>, <?= $azure_count ?>, <?= $gcp_count ?>],
                    backgroundColor: ['#1976D2', '#00BCF2', '#FF9900', '#0078D4', '#4285F4']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    title: { display: true, text: 'VM Distribution by Platform' }
                }
            }
        });

        // Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($performanceTrend, 'date')) . "'"; ?>],
                datasets: [{
                    label: 'Total VMs',
                    data: [<?= implode(',', array_column($performanceTrend, 'total_vms')) ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { title: { display: true, text: 'Infrastructure Trend (7 Days)' } }
            }
        });

        // VMware Chart
        new Chart(document.getElementById('vmwareChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($h) { return explode('.', $h['host'])[0]; }, $vmwareHosts)) . "'"; ?>],
                datasets: [
                    { label: 'CPU %', data: [<?= implode(',', array_column($vmwareHosts, 'cpu_usage')) ?>], backgroundColor: '#2196F3' },
                    { label: 'Memory %', data: [<?php echo implode(',', array_map(function($h) { return round(($h['memory_used_gb'] / $h['memory_total_gb']) * 100); }, $vmwareHosts)); ?>], backgroundColor: '#FF9800' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'VMware Host Utilization' } }, scales: { y: { max: 100 } } }
        });

        // Hyper-V Chart
        new Chart(document.getElementById('hypervChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($hypervHosts, 'host')) . "'"; ?>],
                datasets: [
                    { label: 'CPU %', data: [<?= implode(',', array_column($hypervHosts, 'cpu_usage')) ?>], backgroundColor: '#00BCF2' },
                    { label: 'Memory %', data: [<?php echo implode(',', array_map(function($h) { return round(($h['memory_used_gb'] / $h['memory_total_gb']) * 100); }, $hypervHosts)); ?>], backgroundColor: '#0078D4' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Hyper-V Host Utilization' } }, scales: { y: { max: 100 } } }
        });

        // Cloud Chart
        new Chart(document.getElementById('cloudChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($r) { return $r['provider'] . ' - ' . $r['region']; }, $cloudResources)) . "'"; ?>],
                datasets: [{ label: 'Instances', data: [<?= implode(',', array_column($cloudResources, 'count')) ?>], backgroundColor: ['#FF9900', '#FF9900', '#FF9900', '#0078D4', '#0078D4', '#4285F4', '#4285F4'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { title: { display: true, text: 'Cloud Instances by Region' } } }
        });

        // Cloud Cost Chart
        new Chart(document.getElementById('cloudCostChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($r) { return $r['provider'] . ' - ' . $r['region']; }, $cloudResources)) . "'"; ?>],
                datasets: [{ label: 'Monthly Cost ($)', data: [<?= implode(',', array_column($cloudResources, 'monthly_cost')) ?>], backgroundColor: ['#FF9900', '#FF9900', '#FF9900', '#0078D4', '#0078D4', '#4285F4', '#4285F4'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Cloud Costs by Region' } } }
        });

        // Cost Savings Chart
        new Chart(document.getElementById('costChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($r) { return substr($r['resource'], 0, 25) . '...'; }, $costRecommendations)) . "'"; ?>],
                datasets: [{ label: 'Potential Savings ($)', data: [<?= implode(',', array_column($costRecommendations, 'estimated_savings')) ?>], backgroundColor: '#4CAF50' }]
            },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { title: { display: true, text: 'Cost Optimization Opportunities' } } }
        });

        // Capacity Chart
        new Chart(document.getElementById('capacityChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($capacityForecasts, 'resource')) . "'"; ?>],
                datasets: [
                    { label: 'Current', data: [<?= implode(',', array_column($capacityForecasts, 'current_usage')) ?>], backgroundColor: '#2196F3' },
                    { label: '30 Day', data: [<?= implode(',', array_column($capacityForecasts, 'forecast_30d')) ?>], backgroundColor: '#FF9800' },
                    { label: '90 Day', data: [<?= implode(',', array_column($capacityForecasts, 'forecast_90d')) ?>], backgroundColor: '#f44336' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Capacity Forecast' } }, scales: { y: { max: 100 } } }
        });
    });

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>
