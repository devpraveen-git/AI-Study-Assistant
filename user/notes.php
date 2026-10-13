<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireLogin();
$userId = Auth::id();

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note_id'])) {
    verify_csrf($_POST['csrf_token'] ?? null);
    $noteId = (int) $_POST['delete_note_id'];

    $note = Database::fetchOne("SELECT file_path FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId]);
    if ($note) {
        $absPath = dirname(__DIR__) . '/' . $note['file_path'];
        if (is_file($absPath)) {
            @unlink($absPath);
        }
        Database::execute("DELETE FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        flash('success', 'Study note deleted successfully.');
        redirect('user/notes.php');
    }
}

// Filters
$subjectFilter = !empty($_GET['subject']) ? (int) $_GET['subject'] : null;
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT n.id, n.title, n.file_name, n.file_size, n.extracted_text, n.created_at,
           s.name AS subject_name, s.code AS subject_code, s.color AS subject_color
    FROM notes n
    LEFT JOIN subjects s ON s.id = n.subject_id
    WHERE n.user_id = ?
";
$params = [$userId];

if ($subjectFilter) {
    $sql .= " AND n.subject_id = ?";
    $params[] = $subjectFilter;
}

if ($search !== '') {
    $sql .= " AND (n.title LIKE ? OR n.file_name LIKE ? OR n.extracted_text LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY n.created_at DESC";
$notes = Database::query($sql, $params);
$subjects = Database::query("SELECT id, name, code FROM subjects ORDER BY name ASC");

$pageTitle = 'My Study Notes';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<!-- Filter & Search Toolbar -->
<div class="glass-card p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
  <form method="get" class="d-flex flex-wrap gap-2 flex-grow-1">
    <div class="input-group" style="max-width: 320px;">
      <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
      <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search keywords...">
    </div>

    <select name="subject" class="form-select" style="max-width: 220px;" onchange="this.form.submit()">
      <option value="">All Subjects</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $subjectFilter === (int) $s['id'] ? 'selected' : '' ?>>
          <?= e($s['name']) ?> (<?= e($s['code']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <?php if ($search !== '' || $subjectFilter): ?>
      <a href="notes.php" class="btn btn-outline-secondary">Reset</a>
    <?php endif; ?>
  </form>

  <a href="upload.php" class="btn btn-gold">
    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload PDF
  </a>
</div>

<!-- Notes Grid -->
<?php if (empty($notes)): ?>
  <div class="glass-card p-5 text-center">
    <i class="fa-solid fa-file-circle-plus fa-3x text-warning mb-3"></i>
    <h3 class="fw-bold mb-2">No notes found</h3>
    <p class="text-muted mb-4">
      <?= ($search !== '' || $subjectFilter) ? 'No documents match your filter criteria.' : 'Upload your first PDF study material to unlock AI chat, quizzes, and flashcards.' ?>
    </p>
    <a href="upload.php" class="btn btn-gold btn-lg rounded-pill px-4">Upload Study PDF</a>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($notes as $n): ?>
      <?php 
        $textLen = strlen($n['extracted_text']);
        $preview = mb_substr(strip_tags($n['extracted_text']), 0, 180);
      ?>
      <div class="col-lg-4 col-md-6">
        <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between glass-card-hover position-relative">
          <div>
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge" style="background: <?= e($n['subject_color'] ?? '#6b7280') ?>; font-size: 0.72rem;">
                <?= e($n['subject_code'] ?? 'General') ?>
              </span>
              <small class="text-muted"><?= e(date('d M Y', strtotime($n['created_at']))) ?></small>
            </div>

            <h5 class="fw-bold mb-2 text-truncate" title="<?= e($n['title']) ?>">
              <i class="fa-solid fa-file-pdf text-danger me-2"></i><?= e($n['title']) ?>
            </h5>

            <p class="text-muted small mb-3" style="min-height: 52px; line-height: 1.5;">
              <?= e($preview) ?>...
            </p>
          </div>

          <div>
            <div class="d-flex justify-content-between align-items-center small text-muted pt-2 border-top border-secondary border-opacity-25 mb-3">
              <span><i class="fa-solid fa-align-left me-1"></i><?= number_format($textLen) ?> chars</span>
              <span><i class="fa-solid fa-hard-drive me-1"></i><?= round($n['file_size'] / 1024) ?> KB</span>
            </div>

            <!-- Action Toolbar -->
            <div class="d-flex gap-1 flex-wrap">
              <a href="ask_ai.php?note_id=<?= $n['id'] ?>" class="btn btn-sm btn-gold flex-grow-1 text-center" title="Ask AI questions">
                <i class="fa-solid fa-comments me-1"></i>Ask AI
              </a>
              <a href="quiz.php?note_id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-theme flex-grow-1 text-center" title="Take Quiz">
                <i class="fa-solid fa-circle-question me-1"></i>Quiz
              </a>
              <a href="flashcards.php?note_id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-theme" title="Flashcards">
                <i class="fa-solid fa-layer-group"></i>
              </a>
              
              <!-- Delete Note Form -->
              <form method="post" onsubmit="return confirm('Permanently delete this note and its associated quizzes?');" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="delete_note_id" value="<?= $n['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete note">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
