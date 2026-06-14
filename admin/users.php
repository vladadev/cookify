<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = (int) ($_POST['user_id'] ?? 0);

    if ($user_id === current_user_id()) {
        $flash = ['type' => 'error', 'msg' => 'You cannot modify your own account here.'];
    } else {
        match ($action) {
            'set_role' => (function() use ($pdo, $user_id, &$flash) {
                $role = in_array($_POST['role'] ?? '', ['user', 'admin'], true) ? $_POST['role'] : 'user';
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $user_id]);
                $flash = ['type' => 'success', 'msg' => 'Role updated.'];
            })(),
            'unlock' => (function() use ($pdo, $user_id, &$flash) {
                $pdo->prepare('UPDATE users SET is_locked = 0, failed_attempts = 0 WHERE id = ?')->execute([$user_id]);
                $flash = ['type' => 'success', 'msg' => 'Account unlocked.'];
            })(),
            'delete' => (function() use ($pdo, $user_id, &$flash) {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);
                $flash = ['type' => 'success', 'msg' => 'User deleted.'];
            })(),
            default => null,
        };
    }

    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

$page_title = 'Manage Users';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="admin-header">
    <h1>Manage Users</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/users.php" class="active">Users</a>
        <a href="<?= BASE_URL ?>/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/admin/comments.php">Comments</a>
    </nav>
</div>

<?php if (isset($flash)): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<table class="data-table">
    <thead>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
            <th>Status</th><th>Failed</th><th>Registered</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <tr class="<?= $u['is_locked'] ? 'row-locked' : '' ?>">
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td>
                    <?php if ($u['is_locked']): ?>
                        <span class="badge badge-danger">Locked</span>
                    <?php elseif (!$u['is_active']): ?>
                        <span class="badge badge-warning">Inactive</span>
                    <?php else: ?>
                        <span class="badge badge-success">Active</span>
                    <?php endif; ?>
                </td>
                <td><?= $u['failed_attempts'] ?></td>
                <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td class="table-actions">
                    <form method="POST" action="" style="display:inline">
                        <input type="hidden" name="action"  value="set_role">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="role" onchange="this.form.submit()">
                            <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </form>

                    <?php if ($u['is_locked']): ?>
                        <form method="POST" action="" style="display:inline">
                            <input type="hidden" name="action"  value="unlock">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-small btn-secondary">Unlock</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($u['id'] !== current_user_id()): ?>
                        <form method="POST" action="" style="display:inline"
                              onsubmit="return confirm('Delete this user?')">
                            <input type="hidden" name="action"  value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
