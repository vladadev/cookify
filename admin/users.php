<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo    = get_db();
$errors = [];
$flash  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'create') {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['user', 'admin'], true) ? $_POST['role'] : 'user';

        if (empty($name))                                   $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Invalid email.';
        if (strlen($password) < 8)                          $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'A user with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$name, $email, $hash, $role]);
                $flash = ['type' => 'success', 'msg' => 'User created successfully.'];
            }
        }

    } elseif ($action === 'change_password') {
        $new_password = trim($_POST['new_password'] ?? '');
        if (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } else {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user_id]);
            $flash = ['type' => 'success', 'msg' => 'Password changed successfully.'];
        }

    } elseif ($user_id === current_user_id()) {
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

    if (empty($errors) && $flash) {
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }
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
        <a href="<?= BASE_URL ?>/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/admin/comments.php">Comments</a>
    </nav>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<!-- Add New User -->
<div class="admin-section">
    <h2>Add New User</h2>
    <form method="POST" action="" class="admin-form">
        <input type="hidden" name="action" value="create">
        <div class="form-row">
            <div class="form-group">
                <label for="new_name">Full Name</label>
                <input type="text" id="new_name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="new_email">Email</label>
                <input type="email" id="new_email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="new_password">Password</label>
                <input type="password" id="new_password" name="password">
            </div>
            <div class="form-group">
                <label for="new_role">Role</label>
                <select id="new_role" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create User</button>
    </form>
</div>

<!-- Users Table -->
<div class="admin-section">
    <h2>All Users (<?= count($users) ?>)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
                <th>Status</th><th>Failed</th><th>Last Login</th><th>Registered</th><th>Actions</th>
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
                    <td><?= $u['last_login_at'] ? date('M j, Y H:i', strtotime($u['last_login_at'])) : '—' ?></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td class="table-actions">
                        <!-- Change role -->
                        <form method="POST" action="" style="display:inline">
                            <input type="hidden" name="action"  value="set_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>

                        <!-- Change password -->
                        <button type="button" class="btn btn-small btn-secondary"
                                onclick="document.getElementById('pwd-form-<?= $u['id'] ?>').classList.toggle('hidden')">
                            🔑 Pwd
                        </button>

                        <?php if ($u['is_locked']): ?>
                            <form method="POST" action="" style="display:inline">
                                <input type="hidden" name="action"  value="unlock">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-small btn-secondary">Unlock</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($u['id'] !== current_user_id()): ?>
                            <form method="POST" action="" style="display:inline"
                                  onsubmit="return confirm('Delete this user and all their content?')">
                                <input type="hidden" name="action"  value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Change password inline form -->
                <tr id="pwd-form-<?= $u['id'] ?>" class="hidden">
                    <td colspan="9">
                        <form method="POST" action="" class="inline-pwd-form">
                            <input type="hidden" name="action"  value="change_password">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <strong>New password for <?= htmlspecialchars($u['name']) ?>:</strong>
                            <input type="password" name="new_password" placeholder="Min 8 characters" minlength="8" required>
                            <button type="submit" class="btn btn-small btn-primary">Save Password</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
