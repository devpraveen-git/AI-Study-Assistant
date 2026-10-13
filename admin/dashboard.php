<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireAdmin();

$totalUsers = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM users WHERE role = 'student'")['c'] ?? 0);
$totalNotes = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM notes")['c'] ?? 0);
$totalQuizzes = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM quizzes")['c'] ?? 0);
$avgScore = round((float) (Database::fetchOne("SELECT COALESCE(AVG(score / NULLIF(total_questions, 0) * 100), 0) AS s FROM quizzes")['s'] ?? 0));

$latestUsers = Database::query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$latestUploads = Database::query("
    SELECT n.id, n.title, n.file_name, n.created_at, u.name as student_name
    FROM notes n
    JOIN users u ON u.id = n.user_id
    ORDER BY n.created_at DESC LIMIT 5
");

$pageTitle = 'Administrator Overview';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<!-- Statistics Overview -->
<section class="stats-grid">
  <div class="stat-item" style="background: var(--blue-gradient);">
    <span class="stat-title"><i class="fa-solid fa-user-graduate me-1"></i> Total Students</span>
    <span class="stat-value"><?= $totalUsers ?></span>
    <i class="fa-solid fa-users"></i>
  </div>
  <div class="stat-item" style="background: var(--green-gradient);">
    <span class="stat-title"><i class="fa-solid fa-file-pdf me-1"></i> Notes Uploaded</span>
    <span class="stat-value"><?= $totalNotes ?></span>
    <i class="fa-solid fa-folder"></i>
  </div>
  <div class="stat-item" style="background: var(--gold-gradient);">
    <span class="stat-title"><i class="fa-solid fa-list-check me-1"></i> Quizzes Taken</span>
    <span class="stat-value"><?= $totalQuizzes ?></span>
    <i class="fa-solid fa-clipboard-check"></i>
  </div>
  <div class="stat-item" style="background: var(--purple-gradient);">
    <span class="stat-title"><i class="fa-solid fa-chart-line me-1"></i> System Avg Score</span>
    <span class="stat-value"><?= $avgScore ?>%</span>
    <i class="fa-solid fa-percent"></i>
  </div>
</section>

<!-- Tables Grid -->
<div class="row g-4">
  <!-- Latest Students -->
  <div class="col-lg-6">
    <div class="glass-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-users me-2 text-warning"></i>Recent Students</h5>
        <a href="users.php" class="btn btn-sm btn-outline-theme">Manage All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="text-muted small">
              <th>Student</th>
              <th>Joined</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latestUsers as $u): ?>
              <tr>
                <td>
                  <strong><?= e($u['name']) ?></strong><br>
                  <small class="text-muted"><?= e($u['email']) ?></small>
                </td>
                <td><small><?= e(date('d M Y', strtotime($u['created_at']))) ?></small></td>
                <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'warning' : 'secondary' ?>"><?= e($u['role']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Latest Uploads -->
  <div class="col-lg-6">
    <div class="glass-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-file-pdf me-2 text-warning"></i>Recent Uploads</h5>
        <a href="uploads.php" class="btn btn-sm btn-outline-theme">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="text-muted small">
              <th>Note Title</th>
              <th>Uploaded By</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latestUploads as $up): ?>
              <tr>
                <td>
                  <strong class="d-block text-truncate" style="max-width: 200px;"><?= e($up['title']) ?></strong>
                  <small class="text-muted"><?= e($up['file_name']) ?></small>
                </td>
                <td><small><?= e($up['student_name']) ?></small></td>
                <td><small><?= e(date('d M Y', strtotime($up['created_at']))) ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
