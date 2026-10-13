<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireAdmin();
$currentAdminId = Auth::id();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    if (isset($_POST['toggle_status_id'])) {
        $targetId = (int) $_POST['toggle_status_id'];
        if ($targetId !== $currentAdminId) {
            Database::execute("UPDATE users SET is_active = IF(is_active=1, 0, 1) WHERE id = ?", [$targetId]);
            flash('success', 'User status toggled.');
        }
        redirect('admin/users.php');
    }

    if (isset($_POST['delete_user_id'])) {
        $targetId = (int) $_POST['delete_user_id'];
        if ($targetId !== $currentAdminId) {
            Database::execute("DELETE FROM users WHERE id = ?", [$targetId]);
            flash('success', 'User account permanently deleted.');
        }
        redirect('admin/users.php');
    }

    if (isset($_POST['toggle_role_id'])) {
        $targetId = (int) $_POST['toggle_role_id'];
        if ($targetId !== $currentAdminId) {
            Database::execute("UPDATE users SET role = IF(role='admin', 'student', 'admin') WHERE id = ?", [$targetId]);
            flash('success', 'User role updated.');
        }
        redirect('admin/users.php');
    }
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT id, name, email, role, is_active, created_at FROM users";
$params = [];

if ($search !== '') {
    $sql .= " WHERE name LIKE ? OR email LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like];
}
$sql .= " ORDER BY created_at DESC";

$users = Database::query($sql, $params);

$pageTitle = 'User Management';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<section class="glass-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1"><i class="fa-solid fa-users text-warning me-2"></i>User Management</h4>
      <p class="text-muted small mb-0">View registered students and manage permissions</p>
    </div>

    <form method="get" class="d-flex gap-2">
      <input type="text" name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search users...">
      <button type="submit" class="btn btn-sm btn-gold"><i class="fa-solid fa-magnifying-glass"></i></button>
      <?php if ($search !== ''): ?>
        <a href="users.php" class="btn btn-sm btn-outline-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr class="text-muted small text-uppercase">
          <th>User</th>
          <th>Role</th>
          <th>Account Status</th>
          <th>Registered</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <strong><?= e($u['name']) ?></strong><br>
              <small class="text-muted"><?= e($u['email']) ?></small>
            </td>
            <td>
              <span class="badge text-bg-<?= $u['role'] === 'admin' ? 'warning' : 'info' ?>">
                <?= e(ucfirst($u['role'])) ?>
              </span>
            </td>
            <td>
              <span class="badge text-bg-<?= (int) $u['is_active'] === 1 ? 'success' : 'danger' ?>">
                <?= (int) $u['is_active'] === 1 ? 'Active' : 'Suspended' ?>
              </span>
            </td>
            <td><small><?= e(date('d M Y, h:i A', strtotime($u['created_at']))) ?></small></td>
            <td class="text-end">
              <?php if ((int) $u['id'] !== $currentAdminId): ?>
                <div class="d-inline-flex gap-1">
                  <!-- Toggle Role -->
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="toggle_role_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Admin/Student Role">
                      <i class="fa-solid fa-shield"></i>
                    </button>
                  </form>

                  <!-- Toggle Status -->
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="toggle_status_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $u['is_active'] === 1 ? 'secondary' : 'success' ?>" title="Toggle Active Status">
                      <i class="fa-solid fa-power-off"></i>
                    </button>
                  </form>

                  <!-- Delete User -->
                  <form method="post" onsubmit="return confirm('Permanently delete user <?= e($u['name']) ?>?');" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              <?php else: ?>
                <span class="badge text-bg-secondary">Current Session</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>

