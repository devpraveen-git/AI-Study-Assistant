<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

Auth::requireAdmin();

// Handle Delete Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_upload_id'])) {
    verify_csrf($_POST['csrf_token'] ?? null);
    $noteId = (int) $_POST['delete_upload_id'];

    $note = Database::fetchOne("SELECT file_path FROM notes WHERE id = ?", [$noteId]);
    if ($note) {
        $abs = dirname(__DIR__) . '/' . $note['file_path'];
        if (is_file($abs)) {
            @unlink($abs);
        }
        Database::execute("DELETE FROM notes WHERE id = ?", [$noteId]);
        flash('success', 'Upload record and file deleted.');
        redirect('admin/uploads.php');
    }
}

$uploads = Database::query("
    SELECT n.id, n.title, n.file_name, n.file_size, n.extracted_text, n.created_at,
           u.name AS student_name, u.email AS student_email,
           s.name AS subject_name, s.code AS subject_code, s.color AS subject_color
    FROM notes n
    JOIN users u ON u.id = n.user_id
    LEFT JOIN subjects s ON s.id = n.subject_id
    ORDER BY n.created_at DESC
");

$pageTitle = 'All Uploaded Notes';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<section class="glass-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-shield text-warning me-2"></i>Uploaded Study Materials</h4>
      <p class="text-muted small mb-0">Review notes uploaded by students across all subjects</p>
    </div>
    <span class="badge text-bg-warning px-3 py-2"><?= count($uploads) ?> Total Files</span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr class="text-muted small text-uppercase">
          <th>Document</th>
          <th>Student</th>
          <th>Subject</th>
          <th>Text Size</th>
          <th>Uploaded Date</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($uploads as $up): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-pdf text-danger fs-5"></i>
                <div>
                  <strong class="d-block text-truncate" style="max-width: 250px;"><?= e($up['title']) ?></strong>
                  <small class="text-muted"><?= e($up['file_name']) ?></small>
                </div>
              </div>
            </td>
            <td>
              <strong class="small"><?= e($up['student_name']) ?></strong><br>
              <small class="text-muted"><?= e($up['student_email']) ?></small>
            </td>
            <td>
              <span class="badge" style="background: <?= e($up['subject_color'] ?? '#475569') ?>;">
                <?= e($up['subject_code'] ?? 'General') ?>
              </span>
            </td>
            <td><small><?= number_format(strlen($up['extracted_text'])) ?> chars (<?= round($up['file_size'] / 1024) ?> KB)</small></td>
            <td><small><?= e(date('d M Y, h:i A', strtotime($up['created_at']))) ?></small></td>
            <td class="text-end">
              <form method="post" onsubmit="return confirm('Delete this upload and all associated flashcards/quizzes?');" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="delete_upload_id" value="<?= $up['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Note">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>

