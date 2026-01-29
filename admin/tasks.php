<?php
/**
 * Task Manager - To-Do List System
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_task') {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $category = $_POST['category'] ?? 'general';
        $due_date = $_POST['due_date'] ?? null;

        if (!empty($title)) {
            $db->query(
                "INSERT INTO tasks (title, description, priority, category, due_date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $description, $priority, $category, $due_date, $_SESSION['admin_username']]
            );

            $message = 'Task created successfully!';
            $messageType = 'success';
        }
    } elseif ($action === 'update_status') {
        $task_id = $_POST['task_id'] ?? 0;
        $status = $_POST['status'] ?? 'pending';

        $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

        $db->query(
            "UPDATE tasks SET status = ?, completed_at = ? WHERE id = ?",
            [$status, $completed_at, $task_id]
        );

        $message = 'Task status updated!';
        $messageType = 'success';
    } elseif ($action === 'delete_task') {
        $task_id = $_POST['task_id'] ?? 0;

        $db->query("DELETE FROM tasks WHERE id = ?", [$task_id]);

        $message = 'Task deleted!';
        $messageType = 'success';
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_priority = $_GET['priority'] ?? 'all';
$filter_category = $_GET['category'] ?? 'all';

// Build query
$where = [];
$params = [];

if ($filter_status !== 'all') {
    $where[] = "status = ?";
    $params[] = $filter_status;
}

if ($filter_priority !== 'all') {
    $where[] = "priority = ?";
    $params[] = $filter_priority;
}

if ($filter_category !== 'all') {
    $where[] = "category = ?";
    $params[] = $filter_category;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$tasks = $db->fetchAll(
    "SELECT * FROM tasks $whereClause ORDER BY
     FIELD(priority, 'critical', 'high', 'medium', 'low'),
     FIELD(status, 'in_progress', 'pending', 'completed', 'cancelled'),
     due_date ASC",
    $params
);

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks")['count'],
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'pending'")['count'],
    'in_progress' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'in_progress'")['count'],
    'completed' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'")['count'],
    'critical' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE priority = 'critical' AND status != 'completed'")['count']
];

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM tasks ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - Admin Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <?php include 'sidebar.php'; ?>

            <div class="content">
                <h1>Task Manager</h1>

                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total'] ?></div>
                            <div class="stat-label">Total Tasks</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['pending'] ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🔄</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['in_progress'] ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['completed'] ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>

                    <?php if ($stats['critical'] > 0): ?>
                    <div class="stat-card" style="border: 2px solid #f44336;">
                        <div class="stat-icon">🔴</div>
                        <div class="stat-info">
                            <div class="stat-number" style="color: #f44336;"><?= $stats['critical'] ?></div>
                            <div class="stat-label">Critical Tasks</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Create Task Form -->
                <div class="card">
                    <h2>Create New Task</h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="create_task">

                        <div class="form-group">
                            <label for="title">Task Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="priority">Priority</label>
                                <select id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <option value="general">General</option>
                                    <option value="feature">Feature Request</option>
                                    <option value="enhancement">Enhancement</option>
                                    <option value="security">Security</option>
                                    <option value="bug">Bug Fix</option>
                                    <option value="integration">Integration</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="due_date">Due Date</label>
                                <input type="date" id="due_date" name="due_date">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Create Task</button>
                    </form>
                </div>

                <!-- Filters -->
                <div class="card">
                    <h2>Filter Tasks</h2>

                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Status</label>
                            <select name="status">
                                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= $filter_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Priority</label>
                            <select name="priority">
                                <option value="all" <?= $filter_priority === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="critical" <?= $filter_priority === 'critical' ? 'selected' : '' ?>>Critical</option>
                                <option value="high" <?= $filter_priority === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="medium" <?= $filter_priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="low" <?= $filter_priority === 'low' ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Category</label>
                            <select name="category">
                                <option value="all">All</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category'] ?>" <?= $filter_category === $cat['category'] ? 'selected' : '' ?>>
                                    <?= ucfirst($cat['category']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary">Apply Filters</button>
                    </form>
                </div>

                <!-- Task List -->
                <div class="card">
                    <h2>Tasks (<?= count($tasks) ?>)</h2>

                    <?php if (empty($tasks)): ?>
                        <p>No tasks found. Create a new task above.</p>
                    <?php else: ?>

                    <ul class="task-list">
                        <?php foreach ($tasks as $task): ?>
                        <li class="task-item priority-<?= $task['priority'] ?> <?= $task['status'] === 'completed' ? 'completed' : '' ?>">
                            <div class="task-content">
                                <div class="task-title">
                                    <?= htmlspecialchars($task['title']) ?>
                                    <span class="badge badge-<?= $task['priority'] ?>">
                                        <?= strtoupper($task['priority']) ?>
                                    </span>
                                    <span class="badge badge-<?= $task['status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                    </span>
                                </div>

                                <?php if ($task['description']): ?>
                                <div style="color: #666; margin: 8px 0;">
                                    <?= htmlspecialchars($task['description']) ?>
                                </div>
                                <?php endif; ?>

                                <div class="task-meta">
                                    <strong>Category:</strong> <?= ucfirst($task['category']) ?> |
                                    <?php if ($task['due_date']): ?>
                                        <strong>Due:</strong> <?= date('M d, Y', strtotime($task['due_date'])) ?> |
                                    <?php endif; ?>
                                    <strong>Created:</strong> <?= date('M d, Y', strtotime($task['created_at'])) ?>
                                    <?php if ($task['created_by']): ?>
                                        by <?= htmlspecialchars($task['created_by']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="task-actions">
                                <?php if ($task['status'] !== 'completed'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <?php if ($task['status'] === 'pending'): ?>
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        Start
                                    </button>
                                    <?php else: ?>
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 12px;">
                                        Complete
                                    </button>
                                    <?php endif; ?>
                                </form>
                                <?php endif; ?>

                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this task?');">
                                    <input type="hidden" name="action" value="delete_task">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
