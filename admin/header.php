<div class="header">
    <div class="header-left">
        <h1>🛡️ Network Scanner CMS</h1>
    </div>

    <div class="header-right">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong><br>
                <small style="color: #666;">Administrator</small>
            </div>
        </div>
        <a href="logout.php" class="btn btn-danger" style="padding: 8px 16px; font-size: 13px;">Logout</a>
    </div>
</div>
