<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireLogin();
$userId = Auth::id();

$user = Database::fetchOne("SELECT id, name, email, role, created_at FROM users WHERE id = ?", [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');

        if ($name === '') {
            flash('danger', 'Name cannot be empty.');
        } else {
            if ($newPass !== '') {
                if (strlen($newPass) < 6) {
                    flash('danger', 'New password must be at least 6 characters.');
                } else {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    Database::execute("UPDATE users SET name = ?, password = ? WHERE id = ?", [$name, $hash, $userId]);
                    $_SESSION['user_name'] = $name;
                    flash('success', 'Profile and password updated successfully.');
                    redirect('user/profile.php');
                }
            } else {
                Database::execute("UPDATE users SET name = ? WHERE id = ?", [$name, $userId]);
                $_SESSION['user_name'] = $name;
                flash('success', 'Profile updated successfully.');
                redirect('user/profile.php');
            }
        }
    }
}

// Student Learning Analytics
$notesCount = (int) (Database::fetchOne("SELECT COUNT(*) as c FROM notes WHERE user_id = ?", [$userId])['c'] ?? 0);
$quizzesCount = (int) (Database::fetchOne("SELECT COUNT(*) as c FROM quizzes WHERE user_id = ?", [$userId])['c'] ?? 0);
$avgScoreRow = Database::fetchOne("SELECT AVG(CASE WHEN total_questions > 0 THEN (score * 100.0 / total_questions) ELSE 0 END) as a FROM quizzes WHERE user_id = ?", [$userId]);
$avgScore = round((float) ($avgScoreRow['a'] ?? 0), 1);
$masteredCards = (int) (Database::fetchOne("SELECT COUNT(*) as c FROM flashcards WHERE user_id = ? AND mastery_level >= 2", [$userId])['c'] ?? 0);

$pageTitle = 'My Profile';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<div class="row g-4">
  <!-- Profile & Account Settings -->
  <div class="col-lg-6">
    <section class="glass-card p-4 p-md-5 h-100">
      <h4 class="fw-bold mb-4"><i class="fa-solid fa-user-circle text-warning me-2"></i>Account Information</h4>

      <div class="d-flex align-items-center gap-3 mb-4 p-3 theme-box">
        <div class="user-badge-avatar" style="width: 56px; height: 56px; font-size: 1.5rem;">
          <?= e(substr($user['name'] ?? 'S', 0, 1)) ?>
        </div>
        <div>
          <h5 class="mb-0 fw-bold"><?= e($user['name'] ?? 'Student') ?></h5>
          <span class="text-muted small"><?= e($user['email'] ?? '') ?></span> &bull;
          <span class="badge text-bg-<?= ($user['role'] ?? '') === 'admin' ? 'warning' : 'info' ?> text-uppercase" style="font-size: 0.65rem;">
            <?= e($user['role'] ?? 'student') ?>
          </span>
          <div class="text-muted small mt-1">
            <i class="fa-regular fa-calendar me-1"></i>Joined <?= !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'Recently' ?>
          </div>
        </div>
      </div>

      <form method="post" action="profile.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="update_profile" value="1">

        <div class="mb-3">
          <label class="form-label small fw-bold">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Email Address</label>
          <input type="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" disabled>
          <small class="text-muted">Email address is managed by your university domain.</small>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold">New Password (leave blank to keep current)</label>
          <input type="password" name="new_password" class="form-control" placeholder="Enter at least 6 characters">
        </div>

        <button type="submit" class="btn btn-gold px-4 rounded-pill">
          <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
        </button>
      </form>
    </section>
  </div>

  <!-- Study Progress & Activity Overview -->
  <div class="col-lg-6">
    <section class="glass-card p-4 p-md-5 h-100">
      <h4 class="fw-bold mb-4"><i class="fa-solid fa-chart-pie text-warning me-2"></i>My Study Overview</h4>

      <p class="text-muted small mb-4">
        Track your learning metrics, completed quizzes, and flashcard mastery across all your subjects.
      </p>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <div class="p-3 rounded bg-dark bg-opacity-50 text-center">
            <i class="fa-solid fa-file-pdf text-danger fs-3 mb-2"></i>
            <h3 class="fw-bold mb-0"><?= $notesCount ?></h3>
            <small class="text-muted">Uploaded Notes</small>
          </div>
        </div>
        <div class="col-6">
          <div class="p-3 rounded bg-dark bg-opacity-50 text-center">
            <i class="fa-solid fa-circle-question text-warning fs-3 mb-2"></i>
            <h3 class="fw-bold mb-0"><?= $quizzesCount ?></h3>
            <small class="text-muted">Quizzes Taken</small>
          </div>
        </div>
        <div class="col-6">
          <div class="p-3 rounded bg-dark bg-opacity-50 text-center">
            <i class="fa-solid fa-trophy text-success fs-3 mb-2"></i>
            <h3 class="fw-bold mb-0"><?= $avgScore ?>%</h3>
            <small class="text-muted">Average Score</small>
          </div>
        </div>
        <div class="col-6">
          <div class="p-3 rounded bg-dark bg-opacity-50 text-center">
            <i class="fa-solid fa-bolt text-info fs-3 mb-2"></i>
            <h3 class="fw-bold mb-0"><?= $masteredCards ?></h3>
            <small class="text-muted">Mastered Cards</small>
          </div>
        </div>
      </div>

      <div class="pt-2">
        <h6 class="fw-bold small text-uppercase text-muted mb-3" style="letter-spacing: 0.05em;">Quick Study Actions</h6>
        <div class="d-flex flex-wrap gap-2">
          <a href="upload.php" class="btn btn-sm btn-outline-light rounded-pill">
            <i class="fa-solid fa-arrow-up-from-bracket me-1"></i>Upload Note
          </a>
          <a href="quiz.php" class="btn btn-sm btn-outline-warning rounded-pill">
            <i class="fa-solid fa-clock me-1"></i>Practice Quiz
          </a>
          <a href="flashcards.php" class="btn btn-sm btn-outline-info rounded-pill">
            <i class="fa-solid fa-layer-group me-1"></i>Review Cards
          </a>
          <a href="ask_ai.php" class="btn btn-sm btn-outline-success rounded-pill">
            <i class="fa-solid fa-robot me-1"></i>Ask AI Tutor
          </a>
        </div>
      </div>
    </section>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>