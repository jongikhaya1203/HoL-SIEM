<?php
/**
 * Shutdown Manager HMI
 * Web interface for managing plant shutdowns and startups
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/classes/ShutdownManager.php';

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$shutdownManager = new ShutdownManager($conn);

// Handle actions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'initiate_shutdown':
            $sequenceId = $_POST['sequence_id'];
            $reason = $_POST['reason'] ?? '';
            $isEmergency = isset($_POST['is_emergency']) ? 1 : 0;

            $result = $shutdownManager->initiateShutdown($sequenceId, 'Operator', $reason, $isEmergency);

            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;

        case 'approve_shutdown':
            $executionId = $_POST['execution_id'];
            $result = $shutdownManager->approveShutdown($executionId, 'Supervisor');

            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;

        case 'continue_execution':
            $executionId = $_POST['execution_id'];
            $result = $shutdownManager->continueExecution($executionId, 'Operator');

            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;

        case 'abort_execution':
            $executionId = $_POST['execution_id'];
            $reason = $_POST['abort_reason'] ?? 'Operator aborted';
            $result = $shutdownManager->abortExecution($executionId, 'Operator', $reason);

            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
    }
}

// Get active executions
$activeExecutions = $conn->query("SELECT e.*, s.sequence_name, s.sequence_type, step.step_name as current_step_name
    FROM shutdown_executions e
    JOIN shutdown_sequences s ON e.sequence_id = s.id
    LEFT JOIN shutdown_sequence_steps step ON e.current_step_id = step.id
    WHERE e.execution_status IN ('pending', 'running', 'paused')
    ORDER BY e.initiated_at DESC");

// Get available sequences
$sequences = $conn->query("SELECT s.*, l.level_name, l.level_code,
    (SELECT COUNT(*) FROM shutdown_sequence_steps WHERE sequence_id = s.id) as step_count
    FROM shutdown_sequences s
    LEFT JOIN shutdown_levels l ON s.shutdown_level_id = l.id
    WHERE s.is_active = 1
    ORDER BY s.sequence_type, s.sequence_name");

// Get recent executions
$recentExecutions = $conn->query("SELECT e.*, s.sequence_name, s.sequence_type
    FROM shutdown_executions e
    JOIN shutdown_sequences s ON e.sequence_id = s.id
    ORDER BY e.initiated_at DESC
    LIMIT 10");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Shutdown Manager - SCADA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #c62828 0%, #d32f2f 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .header h1 {
            font-size: 28px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #ffcdd2;
            margin-top: 5px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success { background: #2e7d32; color: white; }
        .alert-error { background: #c62828; color: white; }
        .alert-info { background: #1976d2; color: white; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: #2a2a3e;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            color: #667eea;
        }

        .execution-item {
            background: #1a1a2e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }

        .execution-item.running { border-color: #ffa726; }
        .execution-item.pending { border-color: #42a5f5; }
        .execution-item.paused { border-color: #ffca28; }
        .execution-item.completed { border-color: #66bb6a; }
        .execution-item.failed { border-color: #ef5350; }
        .execution-item.aborted { border-color: #78909c; }

        .execution-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .execution-title {
            font-size: 16px;
            font-weight: 600;
        }

        .status-badge {
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

        .execution-details {
            font-size: 13px;
            color: #b0b0b0;
            margin-bottom: 10px;
        }

        .execution-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }

        .btn-success { background: #4caf50; color: white; }
        .btn-success:hover { background: #45a049; }

        .btn-danger { background: #f44336; color: white; }
        .btn-danger:hover { background: #da190b; }

        .btn-warning { background: #ff9800; color: white; }
        .btn-warning:hover { background: #e68900; }

        .sequence-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .sequence-item {
            background: #1a1a2e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .sequence-item:hover {
            background: #252540;
            transform: translateX(5px);
        }

        .sequence-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .type-shutdown { background: #f44336; color: white; }
        .type-startup { background: #4caf50; color: white; }
        .type-emergency_stop { background: #c62828; color: white; animation: pulse 2s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        form {
            margin-top: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"], textarea, select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #444;
            background: #1a1a2e;
            color: #e0e0e0;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            background: #2a2a3e;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover { color: #fff; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔴 Emergency Shutdown Manager</h1>
        <div class="subtitle">Automated Plant Shutdown & Startup Control System</div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Active Executions -->
        <div class="card">
            <div class="card-header">⚡ Active Shutdown Executions</div>

            <?php if ($activeExecutions->num_rows > 0): ?>
                <?php while ($exec = $activeExecutions->fetch_assoc()): ?>
                <div class="execution-item <?php echo $exec['execution_status']; ?>">
                    <div class="execution-header">
                        <div class="execution-title"><?php echo htmlspecialchars($exec['sequence_name']); ?></div>
                        <span class="status-badge status-<?php echo $exec['execution_status']; ?>">
                            <?php echo $exec['execution_status']; ?>
                        </span>
                    </div>
                    <div class="execution-details">
                        <div>Type: <strong><?php echo ucfirst(str_replace('_', ' ', $exec['sequence_type'])); ?></strong></div>
                        <div>Initiated: <?php echo $exec['initiated_at']; ?> by <?php echo $exec['initiated_by']; ?></div>
                        <?php if ($exec['current_step_name']): ?>
                        <div>Current Step: <strong><?php echo $exec['current_step_name']; ?></strong></div>
                        <?php endif; ?>
                        <?php if ($exec['reason']): ?>
                        <div>Reason: <?php echo htmlspecialchars($exec['reason']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="execution-actions">
                        <?php if ($exec['execution_status'] === 'pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="approve_shutdown">
                            <input type="hidden" name="execution_id" value="<?php echo $exec['id']; ?>">
                            <button type="submit" class="btn btn-success">✓ Approve</button>
                        </form>
                        <?php endif; ?>

                        <?php if ($exec['execution_status'] === 'paused'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="continue_execution">
                            <input type="hidden" name="execution_id" value="<?php echo $exec['id']; ?>">
                            <button type="submit" class="btn btn-primary">▶ Continue</button>
                        </form>
                        <?php endif; ?>

                        <?php if (in_array($exec['execution_status'], ['running', 'paused'])): ?>
                        <button class="btn btn-danger" onclick="confirmAbort(<?php echo $exec['id']; ?>)">✕ Abort</button>
                        <?php endif; ?>

                        <button class="btn btn-primary" onclick="viewLogs(<?php echo $exec['id']; ?>)">📋 View Logs</button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No active shutdown executions</div>
            <?php endif; ?>
        </div>

        <!-- Available Sequences -->
        <div class="card">
            <div class="card-header">📋 Available Shutdown Sequences</div>

            <div class="sequence-list">
                <?php while ($seq = $sequences->fetch_assoc()): ?>
                <div class="sequence-item" onclick="initiateSequence(<?php echo $seq['id']; ?>, '<?php echo htmlspecialchars($seq['sequence_name']); ?>', '<?php echo $seq['sequence_type']; ?>')">
                    <span class="sequence-type type-<?php echo $seq['sequence_type']; ?>">
                        <?php echo str_replace('_', ' ', $seq['sequence_type']); ?>
                    </span>
                    <div style="font-size: 15px; font-weight: 600; margin: 5px 0;">
                        <?php echo htmlspecialchars($seq['sequence_name']); ?>
                    </div>
                    <div style="font-size: 13px; color: #888; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($seq['description']); ?>
                    </div>
                    <div style="font-size: 12px; color: #666;">
                        <?php echo $seq['step_count']; ?> steps •
                        ~<?php echo floor($seq['estimated_duration_seconds'] / 60); ?> min •
                        Level: <?php echo $seq['level_code'] ?? 'N/A'; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Recent Executions -->
    <div class="card">
        <div class="card-header">📊 Recent Executions</div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #444;">
                        <th style="padding: 10px; text-align: left;">Sequence</th>
                        <th style="padding: 10px; text-align: left;">Type</th>
                        <th style="padding: 10px; text-align: left;">Status</th>
                        <th style="padding: 10px; text-align: left;">Initiated By</th>
                        <th style="padding: 10px; text-align: left;">Started</th>
                        <th style="padding: 10px; text-align: left;">Completed</th>
                        <th style="padding: 10px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exec = $recentExecutions->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #333;">
                        <td style="padding: 10px;"><?php echo htmlspecialchars($exec['sequence_name']); ?></td>
                        <td style="padding: 10px;"><?php echo ucfirst(str_replace('_', ' ', $exec['sequence_type'])); ?></td>
                        <td style="padding: 10px;">
                            <span class="status-badge status-<?php echo $exec['execution_status']; ?>">
                                <?php echo $exec['execution_status']; ?>
                            </span>
                        </td>
                        <td style="padding: 10px;"><?php echo $exec['initiated_by']; ?></td>
                        <td style="padding: 10px;"><?php echo date('Y-m-d H:i', strtotime($exec['initiated_at'])); ?></td>
                        <td style="padding: 10px;"><?php echo $exec['completed_at'] ? date('Y-m-d H:i', strtotime($exec['completed_at'])) : '-'; ?></td>
                        <td style="padding: 10px;">
                            <button class="btn btn-primary" onclick="viewLogs(<?php echo $exec['id']; ?>)" style="font-size: 12px; padding: 5px 10px;">View Logs</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Initiate Shutdown Modal -->
    <div id="initiateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-bottom: 20px; color: #667eea;">Initiate Shutdown</h2>

            <form method="POST">
                <input type="hidden" name="action" value="initiate_shutdown">
                <input type="hidden" name="sequence_id" id="sequence_id">

                <div class="form-group">
                    <label>Sequence:</label>
                    <input type="text" id="sequence_name" readonly style="background: #1a1a2e; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Reason for Shutdown:</label>
                    <textarea name="reason" placeholder="Enter reason for initiating this shutdown..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_emergency" id="is_emergency">
                        <span>This is an EMERGENCY shutdown (bypasses approval)</span>
                    </label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger" style="flex: 1;">🔴 Initiate Shutdown</button>
                    <button type="button" class="btn btn-primary" onclick="closeModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Abort Modal -->
    <div id="abortModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('abortModal').style.display='none'">&times;</span>
            <h2 style="margin-bottom: 20px; color: #f44336;">Abort Shutdown Execution</h2>

            <form method="POST">
                <input type="hidden" name="action" value="abort_execution">
                <input type="hidden" name="execution_id" id="abort_execution_id">

                <div class="form-group">
                    <label>Reason for Aborting:</label>
                    <textarea name="abort_reason" placeholder="Enter reason for aborting this execution..." required></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger" style="flex: 1;">Abort Execution</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('abortModal').style.display='none'" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function initiateSequence(id, name, type) {
            document.getElementById('sequence_id').value = id;
            document.getElementById('sequence_name').value = name;

            if (type === 'emergency_stop') {
                document.getElementById('is_emergency').checked = true;
                document.getElementById('modalTitle').textContent = '🚨 EMERGENCY SHUTDOWN';
                document.getElementById('modalTitle').style.color = '#f44336';
            } else {
                document.getElementById('is_emergency').checked = false;
                document.getElementById('modalTitle').textContent = 'Initiate Shutdown';
                document.getElementById('modalTitle').style.color = '#667eea';
            }

            document.getElementById('initiateModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('initiateModal').style.display = 'none';
        }

        function confirmAbort(executionId) {
            document.getElementById('abort_execution_id').value = executionId;
            document.getElementById('abortModal').style.display = 'block';
        }

        function viewLogs(executionId) {
            window.open('shutdown_logs.php?execution_id=' + executionId, '_blank');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const initiateModal = document.getElementById('initiateModal');
            const abortModal = document.getElementById('abortModal');

            if (event.target == initiateModal) {
                initiateModal.style.display = 'none';
            }
            if (event.target == abortModal) {
                abortModal.style.display = 'none';
            }
        }

        // Auto-refresh every 10 seconds
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
<?php $conn->close(); ?>
