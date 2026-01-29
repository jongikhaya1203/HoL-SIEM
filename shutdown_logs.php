<?php
/**
 * Shutdown Execution Logs Viewer
 * Displays detailed logs for a shutdown execution
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$executionId = $_GET['execution_id'] ?? 0;

// Get execution details
$execution = $conn->query("SELECT e.*, s.sequence_name, s.sequence_type, s.description
    FROM shutdown_executions e
    JOIN shutdown_sequences s ON e.sequence_id = s.id
    WHERE e.id = {$executionId}")->fetch_assoc();

if (!$execution) {
    die("Execution not found");
}

// Get execution logs
$logs = $conn->query("SELECT l.*, step.step_name, step.step_number
    FROM shutdown_execution_logs l
    LEFT JOIN shutdown_sequence_steps step ON l.step_id = step.id
    WHERE l.execution_id = {$executionId}
    ORDER BY l.log_time ASC");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shutdown Execution Logs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #e0e0e0;
            padding: 20px;
        }

        .header {
            background: #2a2a3e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 22px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .header-detail {
            font-size: 14px;
            color: #888;
            margin: 5px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-running { background: #ff9800; color: white; }
        .status-pending { background: #2196f3; color: white; }
        .status-paused { background: #ffc107; color: #000; }
        .status-completed { background: #4caf50; color: white; }
        .status-failed { background: #f44336; color: white; }
        .status-aborted { background: #607d8b; color: white; }

        .log-container {
            background: #000;
            border-radius: 10px;
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .log-entry {
            margin-bottom: 10px;
            padding: 8px;
            border-left: 3px solid;
        }

        .log-entry.INFO { border-color: #2196f3; }
        .log-entry.SUCCESS { border-color: #4caf50; }
        .log-entry.WARNING { border-color: #ff9800; }
        .log-entry.ERROR { border-color: #f44336; }

        .log-time {
            color: #666;
            margin-right: 10px;
        }

        .log-level {
            display: inline-block;
            width: 80px;
            font-weight: 600;
        }

        .log-level.INFO { color: #2196f3; }
        .log-level.SUCCESS { color: #4caf50; }
        .log-level.WARNING { color: #ff9800; }
        .log-level.ERROR { color: #f44336; }

        .log-message {
            color: #e0e0e0;
        }

        .log-step {
            color: #888;
            font-style: italic;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Shutdown Execution Log</h1>
        <div class="header-detail">
            <strong>Sequence:</strong> <?php echo htmlspecialchars($execution['sequence_name']); ?>
        </div>
        <div class="header-detail">
            <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $execution['sequence_type'])); ?>
        </div>
        <div class="header-detail">
            <strong>Status:</strong>
            <span class="status-badge status-<?php echo $execution['execution_status']; ?>">
                <?php echo $execution['execution_status']; ?>
            </span>
        </div>
        <div class="header-detail">
            <strong>Initiated:</strong> <?php echo $execution['initiated_at']; ?> by <?php echo $execution['initiated_by']; ?>
        </div>
        <?php if ($execution['completed_at']): ?>
        <div class="header-detail">
            <strong>Completed:</strong> <?php echo $execution['completed_at']; ?>
        </div>
        <?php endif; ?>
        <?php if ($execution['reason']): ?>
        <div class="header-detail">
            <strong>Reason:</strong> <?php echo htmlspecialchars($execution['reason']); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="log-container">
        <?php while ($log = $logs->fetch_assoc()): ?>
        <div class="log-entry <?php echo $log['log_level']; ?>">
            <span class="log-time"><?php echo date('H:i:s', strtotime($log['log_time'])); ?></span>
            <span class="log-level <?php echo $log['log_level']; ?>">[<?php echo $log['log_level']; ?>]</span>
            <span class="log-message"><?php echo htmlspecialchars($log['message']); ?></span>
            <?php if ($log['step_name']): ?>
            <div class="log-step">└─ Step <?php echo $log['step_number']; ?>: <?php echo htmlspecialchars($log['step_name']); ?></div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>

    <script>
        // Auto-scroll to bottom
        const logContainer = document.querySelector('.log-container');
        logContainer.scrollTop = logContainer.scrollHeight;

        // Auto-refresh for active executions
        <?php if (in_array($execution['execution_status'], ['running', 'paused'])): ?>
        setTimeout(function() {
            location.reload();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
