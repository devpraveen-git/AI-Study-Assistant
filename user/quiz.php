<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireLogin();
$userId = Auth::id();

$notes = Database::query("SELECT id, title, file_name FROM notes WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
$selectedNoteId = (int) ($_GET['note_id'] ?? ($notes[0]['id'] ?? 0));

$pageTitle = 'Practice MCQ Quiz';
$extraScript = 'quiz.js';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<div class="glass-card p-4 p-md-5">
  <input type="hidden" id="csrfToken" value="<?= csrf_token() ?>">

  <!-- Quiz Toolbar -->
  <div class="quiz-top-bar">
    <div class="d-flex align-items-center gap-3 flex-grow-1 flex-wrap">
      <div style="min-width: 240px;" class="flex-grow-1">
        <label class="form-label small fw-bold text-muted mb-1">Select Note Source</label>
        <select class="form-select" id="quizNoteSelect">
          <?php if (empty($notes)): ?>
            <option value="">No notes uploaded yet</option>
          <?php else: ?>
            <?php foreach ($notes as $n): ?>
              <option value="<?= $n['id'] ?>" <?= $selectedNoteId === (int) $n['id'] ? 'selected' : '' ?>>
                <?= e($n['title']) ?> (<?= e($n['file_name']) ?>)
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div style="width: 140px;">
        <label class="form-label small fw-bold text-muted mb-1">Question Count</label>
        <select class="form-select" id="quizCountSelect">
          <option value="5">5 Questions</option>
          <option value="10" selected>10 Questions</option>
          <option value="15">15 Questions</option>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-end gap-3">
      <div class="quiz-timer-box" title="Quiz Countdown Timer">
        <i class="fa-solid fa-stopwatch"></i>
        <span id="quizTimerDisplay">10:00</span>
      </div>

      <button type="button" class="btn btn-gold btn-lg px-4" id="generateQuizBtn" <?= empty($notes) ? 'disabled' : '' ?>>
        <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate Quiz
      </button>
    </div>
  </div>

  <?php if (empty($notes)): ?>
    <div class="p-5 text-center my-4">
      <i class="fa-solid fa-file-circle-plus fa-3x text-warning mb-3"></i>
      <h4 class="fw-bold">No notes available</h4>
      <p class="text-muted mb-3">Please upload a PDF note first to generate practice quizzes from your study material.</p>
      <a href="upload.php" class="btn btn-gold rounded-pill px-4">Upload Study PDF</a>
    </div>
  <?php else: ?>
    <!-- Skeleton Placeholder Loading State -->
    <div id="quizSkeleton" class="d-none my-4">
      <div class="card bg-dark bg-opacity-50 p-4 mb-3 border-secondary placeholder-glow">
        <span class="placeholder col-4 mb-2"></span>
        <span class="placeholder col-10 mb-4"></span>
        <span class="placeholder col-8 mb-2"></span>
        <span class="placeholder col-7 mb-2"></span>
      </div>
      <div class="card bg-dark bg-opacity-50 p-4 mb-3 border-secondary placeholder-glow">
        <span class="placeholder col-3 mb-2"></span>
        <span class="placeholder col-9 mb-4"></span>
        <span class="placeholder col-6 mb-2"></span>
        <span class="placeholder col-8 mb-2"></span>
      </div>
    </div>

    <!-- Active Quiz Questions Form -->
    <form id="quizForm" class="my-2"></form>

    <!-- Score Evaluation & Review Panel -->
    <div id="scorePanel" class="d-none my-4"></div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
