<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/users.php';

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

        if (empty($name))                               $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if (strlen($password) < 8)                      $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            if (get_user_by_email($pdo, $email)) {
                $errors[] = 'A user with this email already exists.';
            } else {
                $hash  = password_hash($password, PASSWORD_BCRYPT);
                $token = bin2hex(random_bytes(32));
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
            change_user_password($pdo, $user_id, $new_password);
            $flash = ['type' => 'success', 'msg' => 'Password changed successfully.'];
        }

    } elseif ($user_id === current_user_id()) {
        $flash = ['type' => 'error', 'msg' => 'You cannot modify your own account here.'];

    } else {
        match ($action) {
            'set_role' => (function () use ($pdo, $user_id, &$flash) {
                $role = in_array($_POST['role'] ?? '', ['user', 'admin'], true) ? $_POST['role'] : 'user';
                set_user_role($pdo, $user_id, $role);
                $flash = ['type' => 'success', 'msg' => 'Role updated.'];
            })(),
            'unlock' => (function () use ($pdo, $user_id, &$flash) {
                unlock_user($pdo, $user_id);
                $flash = ['type' => 'success', 'msg' => 'Account unlocked.'];
            })(),
            'delete' => (function () use ($pdo, $user_id, &$flash) {
                delete_user($pdo, $user_id);
                $flash = ['type' => 'success', 'msg' => 'User deleted.'];
            })(),
            default => null,
        };
    }

    if (empty($errors) && $flash) {
        header('Location: ' . BASE_URL . '/views/admin/users.php');
        exit;
    }
}

$users      = get_all_users($pdo);
$page_title = 'Manage Users';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="admin-header">
    <h1>Manage Users</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/views/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users.php" class="active">Users</a>
        <a href="<?= BASE_URL ?>/views/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/views/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/views/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/views/admin/comments.php">Comments</a>
    </nav>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

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

<div class="admin-section">
    <h2>All Users (<?= count($users) ?>)</h2>
    <div class="table-scroll">
    <table class="data-table users-table">
        <thead>
            <tr>
                <th>User</th><th>Role</th><th>Status</th><th>Last Login</th><th class="actions-col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr class="<?= $u['is_locked'] ? 'row-locked' : '' ?>">
                    <td class="user-info-cell">
                        <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                        <div class="user-meta">
                            ID #<?= $u['id'] ?> &bull; Joined <?= date('M j, Y', strtotime($u['created_at'])) ?>
                            <?php if ($u['failed_attempts'] > 0): ?>
                                &bull; <span class="text-danger"><?= $u['failed_attempts'] ?> failed attempt(s)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td>
                        <?php if ($u['is_locked']): ?>
                            <span class="badge badge-danger">Locked</span>
                        <?php elseif (!$u['is_active']): ?>
                            <span class="badge badge-warning">Inactive</span>
                        <?php else: ?>
                            <span class="badge badge-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="nowrap"><?= $u['last_login_at'] ? date('M j, Y', strtotime($u['last_login_at'])) : '—' ?></td>
                    <td class="actions-col">
                        <div class="user-actions">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="set_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" class="role-select" onchange="this.form.submit()"
                                        <?= $u['id'] === current_user_id() ? 'disabled' : '' ?>>
                                    <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                            <button type="button" class="btn btn-small btn-secondary"
                                    onclick="togglePwdForm(<?= $u['id'] ?>)">🔑 Password</button>
                            <?php if ($u['is_locked']): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="unlock">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-warning">🔓 Unlock</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($u['id'] !== current_user_id()): ?>
                                <form method="POST" action=""
                                      onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger">🗑 Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-muted">You</span>
                            <?php endif; ?>
                        </div>
                        <div id="pwd-form-<?= $u['id'] ?>" class="pwd-inline hidden">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="password" name="new_password" placeholder="New password (min 8)" minlength="8" required>
                                <button type="submit" class="btn btn-small btn-primary">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
function togglePwdForm(id) {
    var el = document.getElementById('pwd-form-' + id);
    el.classList.toggle('hidden');
    if (!el.classList.contains('hidden')) el.querySelector('input[type="password"]').focus();
}
</script>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
