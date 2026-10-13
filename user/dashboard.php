<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireLogin();
$userId = Auth::id();

// Metrics
$totalNotes = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM notes WHERE user_id = ?", [$userId])['c'] ?? 0);
$quizStats = Database::fetchOne("SELECT COUNT(*) AS total, COALESCE(AVG(score / NULLIF(total_questions, 0) * 100), 0) AS avg_score FROM quizzes WHERE user_id = ?", [$userId]);
$totalQuizzes = (int) ($quizStats['total'] ?? 0);
$avgScore = round((float) ($quizStats['avg_score'] ?? 0));
$totalCards = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM flashcards WHERE user_id = ?", [$userId])['c'] ?? 0);

// Recent activity
$recentNotes = Database::query("SELECT id, title, file_name, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 4", [$userId]);
$recentQuizzes = Database::query("SELECT id, score, total_questions, created_at FROM quizzes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);

// Score trend chart data
$chartRows = Database::query("SELECT score, total_questions, DATE_FORMAT(created_at, '%d %b') AS label FROM quizzes WHERE user_id = ? ORDER BY created_at ASC LIMIT 10", [$userId]);
$chartLabels = array_column($chartRows, 'label');
$chartScores = array_map(fn($r) => $r['total_questions'] > 0 ? round(($r['score'] / $r['total_questions']) * 100) : 0, $chartRows);

// Subjects with note counts
$subjects = Database::query("
    SELECT s.id, s.name, s.code, s.icon, s.color, COUNT(n.id) as note_count
    FROM subjects s
    LEFT JOIN notes n ON n.subject_id = s.id AND n.user_id = ?
    GROUP BY s.id
", [$userId]);

$pageTitle = 'Student Dashboard';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<!-- Statistics Overview -->
<section class="stats-grid">
  <div class="stat-item" style="background: var(--green-gradient);">
    <span class="stat-title"><i class="fa-solid fa-file-lines me-1"></i> Uploaded Notes</span>
    <span class="stat-value"><?= $totalNotes ?></span>
    <i class="fa-solid fa-file-pdf"></i>
  </div>
  <div class="stat-item" style="background: var(--gold-gradient);">
    <span class="stat-title"><i class="fa-solid fa-circle-question me-1"></i> Quizzes Taken</span>
    <span class="stat-value"><?= $totalQuizzes ?></span>
    <i class="fa-solid fa-list-check"></i>
  </div>
  <div class="stat-item" style="background: var(--blue-gradient);">
    <span class="stat-title"><i class="fa-solid fa-chart-line me-1"></i> Average Score</span>
    <span class="stat-value"><?= $avgScore ?>%</span>
    <i class="fa-solid fa-bullseye"></i>
  </div>
  <div class="stat-item" style="background: var(--purple-gradient);">
    <span class="stat-title"><i class="fa-solid fa-layer-group me-1"></i> Active Flashcards</span>
    <span class="stat-value"><?= $totalCards ?></span>
    <i class="fa-solid fa-clone"></i>
  </div>
</section>

<!-- Quick Action Grid -->
<section class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="upload.php" class="glass-card p-3 d-flex align-items-center gap-3 text-decoration-none glass-card-hover">
      <div class="brand-icon" style="background: var(--green-gradient); width: 38px; height: 38px; font-size: 1rem;"><i class="fa-solid fa-cloud-arrow-up text-white"></i></div>
      <div><strong style="color: var(--text-main);">Upload PDF</strong><br><small class="text-muted">Add new notes</small></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="ask_ai.php" class="glass-card p-3 d-flex align-items-center gap-3 text-decoration-none glass-card-hover">
      <div class="brand-icon" style="background: var(--gold-gradient); width: 38px; height: 38px; font-size: 1rem;"><i class="fa-solid fa-comments text-dark"></i></div>
      <div><strong style="color: var(--text-main);">Ask AI Tutor</strong><br><small class="text-muted">Ask questions</small></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="quiz.php" class="glass-card p-3 d-flex align-items-center gap-3 text-decoration-none glass-card-hover">
      <div class="brand-icon" style="background: var(--blue-gradient); width: 38px; height: 38px; font-size: 1rem;"><i class="fa-solid fa-circle-question text-white"></i></div>
      <div><strong style="color: var(--text-main);">Practice Quiz</strong><br><small class="text-muted">10 MCQs test</small></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="flashcards.php" class="glass-card p-3 d-flex align-items-center gap-3 text-decoration-none glass-card-hover">
      <div class="brand-icon" style="background: var(--purple-gradient); width: 38px; height: 38px; font-size: 1rem;"><i class="fa-solid fa-layer-group text-white"></i></div>
      <div><strong style="color: var(--text-main);">Flashcards</strong><br><small class="text-muted">Quick revision</small></div>
    </a>
  </div>
</section>

<!-- Chart & Recent Activities Row -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="glass-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-chart-area me-2 text-warning"></i>Quiz Score Progression</h5>
        <a href="quiz.php" class="btn btn-sm btn-gold">Take Quiz</a>
      </div>
      <?php if (empty($chartScores)): ?>
        <div class="p-5 text-center text-muted">
          <i class="fa-solid fa-chart-simple fa-3x mb-3 text-secondary"></i>
          <p class="mb-0">No quiz attempts yet. Generate and take a quiz to visualize your score progression!</p>
        </div>
      <?php else: ?>
        <canvas id="scoreChartCanvas" height="130"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="glass-card p-4 h-100">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>Recent Notes</h5>
      <?php if (empty($recentNotes)): ?>
        <div class="p-4 text-center text-muted">
          <p class="mb-2">No notes uploaded yet.</p>
          <a href="upload.php" class="btn btn-sm btn-gold">Upload Your First PDF</a>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($recentNotes as $n): ?>
            <div class="d-flex justify-content-between align-items-center p-2 rounded theme-box">
              <div class="d-flex align-items-center gap-2 overflow-hidden">
                <i class="fa-solid fa-file-pdf text-danger fs-5"></i>
                <div class="text-truncate">
                  <strong class="d-block text-truncate small"><?= e($n['title']) ?></strong>
                  <small class="text-muted" style="font-size: 0.72rem;"><?= e(date('d M Y', strtotime($n['created_at']))) ?></small>
                </div>
              </div>
              <div class="d-flex gap-1 flex-shrink-0">
                <a href="ask_ai.php?note_id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-warning py-0 px-2" title="Ask AI"><i class="fa-solid fa-comment-dots"></i></a>
                <a href="quiz.php?note_id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-theme py-0 px-2" title="Quiz"><i class="fa-solid fa-circle-question"></i></a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Subject Modules Grid -->
<section class="glass-card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="fa-solid fa-graduation-cap me-2 text-warning"></i>B.Sc CS Subject Modules</h5>
    <a href="notes.php" class="small text-warning text-decoration-none">View All Notes &rarr;</a>
  </div>
  <div class="row g-3">
    <?php foreach ($subjects as $s): ?>
      <div class="col-md-4 col-sm-6">
        <div class="p-3 rounded theme-box d-flex align-items-center gap-3">
          <div class="p-2 rounded" style="background: rgba(255,255,255,0.06); color: <?= e($s['color']) ?>; font-size: 1.25rem;">
            <i class="fa-solid <?= e($s['icon']) ?>"></i>
          </div>
          <div>
            <strong class="d-block small"><?= e($s['name']) ?></strong>
            <small class="text-muted"><?= (int) $s['note_count'] ?> notes saved</small>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if (!empty($chartScores)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('scoreChartCanvas');
  if (ctx) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Score %',
          data: <?= json_encode($chartScores) ?>,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.15)',
          fill: true,
          tension: 0.35,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
        },
        plugins: { legend: { display: false } }
      }
    });
  }
});
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
