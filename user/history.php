<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireLogin();
$userId = Auth::id();

// Fetch quiz attempts
$quizzes = Database::query("
    SELECT q.id, q.score, q.total_questions, q.time_spent, q.created_at,
           n.title AS note_title, n.file_name
    FROM quizzes q
    LEFT JOIN notes n ON n.id = q.note_id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
", [$userId]);

$pageTitle = 'Quiz Attempt History';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<section class="glass-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Your Quiz Results</h4>
      <p class="text-muted small mb-0">Track your score history and review mistakes over time</p>
    </div>
    <a href="quiz.php" class="btn btn-gold"><i class="fa-solid fa-plus me-1"></i>Take New Quiz</a>
  </div>

  <?php if (empty($quizzes)): ?>
    <div class="p-5 text-center text-muted">
      <i class="fa-solid fa-clipboard-question fa-3x text-secondary mb-3"></i>
      <h5 class="fw-bold">No quizzes completed yet</h5>
      <p class="small mb-3">Generate your first quiz from your uploaded notes to start practicing.</p>
      <a href="quiz.php" class="btn btn-gold btn-sm rounded-pill px-3">Start a Practice Quiz</a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr class="text-muted small text-uppercase">
            <th>Date & Time</th>
            <th>Study Note</th>
            <th>Score</th>
            <th>Percentage</th>
            <th>Duration</th>
            <th>Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quizzes as $q): ?>
            <?php 
              $percent = $q['total_questions'] > 0 ? round(($q['score'] / $q['total_questions']) * 100) : 0;
              $badgeClass = $percent >= 80 ? 'success' : ($percent >= 60 ? 'primary' : 'warning');
              $badgeLabel = $percent >= 80 ? 'Mastered' : ($percent >= 60 ? 'Passing' : 'Revise');
            ?>
            <tr>
              <td><?= e(date('d M Y, h:i A', strtotime($q['created_at']))) ?></td>
              <td>
                <strong class="d-block small"><?= e($q['note_title'] ?? $q['file_name'] ?? 'General') ?></strong>
              </td>
              <td><span class="fw-bold"><?= $q['score'] ?></span> / <?= $q['total_questions'] ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                    <div class="progress-bar bg-<?= $badgeClass ?>" role="progressbar" style="width: <?= $percent ?>%;"></div>
                  </div>
                  <span class="small fw-bold"><?= $percent ?>%</span>
                </div>
              </td>
              <td><?= $q['time_spent'] > 0 ? gmdate('i\m s\s', (int) $q['time_spent']) : '-' ?></td>
              <td><span class="badge text-bg-<?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
              <td class="text-end">
                <a href="quiz.php?retake_id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-theme" title="Retake this quiz">
                  <i class="fa-solid fa-rotate-right me-1"></i>Retake
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
