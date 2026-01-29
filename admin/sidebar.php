<div class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                <span class="icon">🏠</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="tasks.php" class="<?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : '' ?>">
                <span class="icon">✅</span>
                <span>Task Manager</span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                <span class="icon">⚙️</span>
                <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Users</span>
            </a>
        </li>
        <li>
            <a href="tenants.php" class="<?= basename($_SERVER['PHP_SELF']) === 'tenants.php' ? 'active' : '' ?>">
                <span class="icon">🏢</span>
                <span>Tenants</span>
            </a>
        </li>
        <li>
            <a href="backup.php" class="<?= basename($_SERVER['PHP_SELF']) === 'backup.php' ? 'active' : '' ?>">
                <span class="icon">💾</span>
                <span>Backup</span>
            </a>
        </li>
        <li>
            <a href="recommendations.php" class="<?= basename($_SERVER['PHP_SELF']) === 'recommendations.php' ? 'active' : '' ?>">
                <span class="icon">📊</span>
                <span>SolarWinds Benchmark</span>
            </a>
        </li>
        <li style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <a href="../index.php" target="_blank">
                <span class="icon">🔗</span>
                <span>View Dashboard</span>
            </a>
        </li>
    </ul>
</div>
