<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';
require_once dirname(__DIR__) . '/app/Services/AIService.php';

use App\Config\Database;
use App\Core\Auth;
use App\Services\AIService;

Auth::requireLogin();
$userId = Auth::id();

$notes = Database::query("SELECT id, title, file_name FROM notes WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
$selectedNoteId = (int) ($_GET['note_id'] ?? ($notes[0]['id'] ?? 0));

// Fetch prior chat history for the selected note
$priorMessages = [];
if ($selectedNoteId > 0) {
    $priorMessages = Database::query(
        "SELECT role, message FROM chat_messages WHERE user_id = ? AND note_id = ? ORDER BY id ASC LIMIT 20",
        [$userId, $selectedNoteId]
    );
}
$pageTitle = 'Ask AI Study Tutor';
$extraScript = 'chat.js';
$isChatPage = true;
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<div class="chat-container glass-card">
  <!-- Chat Top Toolbar -->
  <div class="chat-header">
    <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width: 0;">
      <div class="brand-icon flex-shrink-0" style="width: 38px; height: 38px; font-size: 1rem;"><i class="fa-solid fa-robot"></i></div>
      <div class="flex-grow-1" style="max-width: 560px; min-width: 0;">
        <select class="form-select form-select-sm text-truncate" id="noteSelect" onchange="location.href='ask_ai.php?note_id=' + this.value" title="Active Study Note">
          <?php if (empty($notes)): ?>
            <option value="">No notes uploaded yet</option>
          <?php else: ?>
            <?php foreach ($notes as $n): ?>
              <option value="<?= $n['id'] ?>" <?= $selectedNoteId === (int) $n['id'] ? 'selected' : '' ?> title="<?= e($n['title']) ?> (<?= e($n['file_name']) ?>)">
                <?= e($n['title']) ?> (<?= e($n['file_name']) ?>)
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-shrink-0">
      <span id="engineBadge" class="badge text-bg-success rounded-pill px-3 py-2 d-none d-md-inline-block">
        <i class="fa-solid fa-robot me-1"></i>AI Tutor Ready
      </span>
    </div>
  </div>

  <!-- Suggestion Chips -->
  <?php if (!empty($notes)): ?>
    <div class="suggestion-chips py-2 border-bottom border-secondary border-opacity-25">
      <button type="button" class="chip-btn" data-query="overview of this"><i class="fa-solid fa-book-open me-1"></i>Overview of this</button>
      <button type="button" class="chip-btn" data-query="key exam questions from this note"><i class="fa-solid fa-graduation-cap me-1"></i>Exam Questions</button>
      <button type="button" class="chip-btn" data-query="explain the most important concepts and definitions"><i class="fa-solid fa-list-check me-1"></i>Core Definitions</button>
      <button type="button" class="chip-btn" data-query="summarize in 5 bullet points"><i class="fa-solid fa-list-ol me-1"></i>5-Point Summary</button>
    </div>
  <?php endif; ?>

  <!-- Chat Messages Window -->
  <div class="chat-messages" id="chatMessages">
    <?php if (empty($notes)): ?>
      <div class="p-5 text-center my-auto">
        <i class="fa-solid fa-cloud-arrow-up fa-3x text-warning mb-3"></i>
        <h4 class="fw-bold">No notes available</h4>
        <p class="text-muted mb-3">Please upload a PDF note first so the AI tutor can read and answer questions from it.</p>
        <a href="upload.php" class="btn btn-gold rounded-pill px-4">Upload PDF Note</a>
      </div>
    <?php elseif (empty($priorMessages)): ?>
      <div class="chat-bubble assistant">
        <p class="mb-1"><strong>Hello! I am your AI Study Tutor.</strong></p>
        <p class="mb-0 text-muted">I have loaded your selected PDF. You can ask me to explain any concept, generate exam summaries, provide code examples, or type <strong class="text-warning">"overview of this"</strong> to get a full topic breakdown.</p>
      </div>
    <?php else: ?>
      <?php foreach ($priorMessages as $msg): ?>
        <div class="chat-bubble <?= $msg['role'] === 'user' ? 'user' : 'assistant' ?>"
             <?= $msg['role'] === 'assistant' ? 'data-raw="' . htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
          <?php if ($msg['role'] === 'user'): ?>
            <?= nl2br(e($msg['message'])) ?>
          <?php else: ?>
            <div class="bubble-content"><?= nl2br(e($msg['message'])) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Chat Input Bar -->
  <?php if (!empty($notes)): ?>
    <form class="chat-input-bar" id="chatForm">
      <input type="hidden" id="csrfToken" value="<?= csrf_token() ?>">
      <input type="text" class="form-control" id="questionInput" placeholder="Ask about algorithms, DBMS, OS, networking, or type 'overview of this'..." autocomplete="off" required>
      <button type="submit" class="btn btn-gold px-4" title="Send message">
        <i class="fa-solid fa-paper-plane"></i>
      </button>
    </form>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
