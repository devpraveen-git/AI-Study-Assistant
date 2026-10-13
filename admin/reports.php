<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireAdmin();

$reports = Database::query("
    SELECT u.id, u.name, u.email,
           COUNT(q.id) AS total_quizzes,
           COALESCE(MAX(q.score / NULLIF(q.total_questions, 0) * 100), 0) AS max_score,
           COALESCE(AVG(q.score / NULLIF(q.total_questions, 0) * 100), 0) AS avg_score,
           MAX(q.created_at) AS last_quiz_at
    FROM users u
    LEFT JOIN quizzes q ON q.user_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY avg_score DESC, total_quizzes DESC
");

$pageTitle = 'Student Performance Reports';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<section class="glass-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-column text-warning me-2"></i>Performance Leaderboard & Reports</h4>
      <p class="text-muted small mb-0">Overview of student practice frequency and mastery rates</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr class="text-muted small text-uppercase">
          <th>Rank</th>
          <th>Student</th>
          <th>Quizzes Taken</th>
          <th>Best Score</th>
          <th>Average Score</th>
          <th>Last Activity</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $idx => $r): ?>
          <?php 
            $avg = round((float) $r['avg_score']);
            $badgeColor = $avg >= 80 ? 'success' : ($avg >= 60 ? 'primary' : 'warning');
          ?>
          <tr>
            <td>
              <?php if ($idx === 0): ?>
                <span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i>#1</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">#<?= $idx + 1 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e($r['name']) ?></strong><br>
              <small class="text-muted"><?= e($r['email']) ?></small>
            </td>
            <td><?= (int) $r['total_quizzes'] ?></td>
            <td><strong class="text-success"><?= round((float) $r['max_score']) ?>%</strong></td>
            <td>
              <span class="badge text-bg-<?= $badgeColor ?> px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                <?= $avg ?>%
              </span>
            </td>
            <td>
              <small class="text-muted">
                <?= $r['last_quiz_at'] ? e(date('d M Y, h:i A', strtotime($r['last_quiz_at']))) : 'No activity' ?>
              </small>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>

