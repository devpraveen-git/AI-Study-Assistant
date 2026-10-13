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

$pageTitle = 'Interactive Flashcards';
$extraScript = 'flashcards.js';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<div class="glass-card p-4 p-md-5">
  <input type="hidden" id="csrfToken" value="<?= csrf_token() ?>">

  <!-- Top Controls -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pb-3 border-bottom border-secondary border-opacity-25 mb-4">
    <div style="max-width: 360px;" class="flex-grow-1">
      <label class="form-label small fw-bold text-muted mb-1">Study Note</label>
      <select class="form-select" id="flashcardNoteSelect">
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

    <div class="text-end">
      <span class="badge text-bg-warning px-3 py-2 fw-bold" id="cardCounter">Card 0 of 0</span>
      <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Space to flip &bull; &larr; &rarr; to navigate</small>
    </div>
  </div>

  <!-- Progress Bar -->
  <div class="progress mb-4" style="height: 6px;">
    <div class="progress-bar bg-warning" id="flashcardProgressBar" role="progressbar" style="width: 0%;"></div>
  </div>

  <?php if (empty($notes)): ?>
    <div class="p-5 text-center my-4">
      <i class="fa-solid fa-layer-group fa-3x text-warning mb-3"></i>
      <h4 class="fw-bold">No notes available</h4>
      <p class="text-muted mb-3">Upload a study PDF to automatically generate interactive concept flashcards.</p>
      <a href="upload.php" class="btn btn-gold rounded-pill px-4">Upload Study PDF</a>
    </div>
  <?php else: ?>
    <!-- Flashcard 3D Stage -->
    <div id="flashcardSceneWrapper" class="my-4">
      <div class="flashcard-scene">
        <div class="flashcard-inner" id="flashcardInner">
          <!-- Front Face -->
          <div class="flashcard-face flashcard-front">
            <span class="badge text-bg-secondary px-3 py-1 mb-3" id="cardCategoryBadge">Concept</span>
            <h3 class="fw-bold text-warning mb-3" id="cardFrontText">Loading front...</h3>
            <small class="text-muted"><i class="fa-solid fa-hand-pointer me-1"></i> Click card or press Space to flip</small>
          </div>

          <!-- Back Face -->
          <div class="flashcard-face flashcard-back">
            <span class="badge text-bg-success px-3 py-1 mb-3">Definition & Details</span>
            <p class="lead text-light mb-3" id="cardBackText">Loading back...</p>
            <small class="text-white-50"><i class="fa-solid fa-rotate me-1"></i> Click to flip back</small>
          </div>
        </div>
      </div>

      <!-- Bottom Control Actions -->
      <div class="d-flex justify-content-center align-items-center gap-3 mt-4">
        <button type="button" class="btn btn-outline-theme px-3 py-2" id="prevCardBtn" title="Previous card">
          <i class="fa-solid fa-arrow-left me-1"></i> Prev
        </button>

        <button type="button" class="btn btn-gold px-4 py-2" id="flipCardBtn">
          <i class="fa-solid fa-rotate me-1"></i> Flip Card
        </button>

        <button type="button" class="btn btn-outline-theme px-3 py-2" id="nextCardBtn" title="Next card">
          Next <i class="fa-solid fa-arrow-right ms-1"></i>
        </button>
      </div>

      <!-- Mastery Feedback -->
      <div class="d-flex justify-content-center gap-3 mt-3">
        <button type="button" class="btn btn-sm btn-outline-danger px-3 rounded-pill" id="dontKnowCardBtn">
          <i class="fa-solid fa-xmark me-1"></i> Still Learning
        </button>
        <button type="button" class="btn btn-sm btn-outline-success px-3 rounded-pill" id="knowCardBtn">
          <i class="fa-solid fa-check me-1"></i> Got It! Mastered
        </button>
      </div>
    </div>

    <div id="flashcardEmptyState" class="d-none p-5 text-center my-4">
      <i class="fa-solid fa-clone fa-3x text-warning mb-3"></i>
      <h4 class="fw-bold">No flashcards found</h4>
      <p class="text-muted">This note does not have enough structured definitions or terms to create flashcards.</p>
    </div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
