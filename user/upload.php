<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';
require_once dirname(__DIR__) . '/app/Services/PDFService.php';

use App\Config\Database;
use App\Core\Auth;
use App\Services\PDFService;

Auth::requireLogin();
$userId = Auth::id();

$subjects = Database::query("SELECT id, name, code FROM subjects ORDER BY name ASC");

const MAX_PDF_BYTES = 25 * 1024 * 1024; // 25MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $title = trim($_POST['title'] ?? '');
    $subjectId = !empty($_POST['subject_id']) ? (int) $_POST['subject_id'] : null;
    $file = $_FILES['pdf_file'] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        flash('danger', 'Please select a PDF document to upload.');
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        flash('danger', 'File upload error (code ' . $file['error'] . '). Please check file size.');
    } elseif ($file['size'] > MAX_PDF_BYTES) {
        flash('danger', 'File exceeds the maximum 25MB size limit.');
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($mime !== 'application/pdf' || $ext !== 'pdf') {
            flash('danger', 'Only authentic PDF documents are permitted.');
        } else {
            try {
                $cleanBase = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
                $storedFileName = $userId . '_' . time() . '_' . $cleanBase;
                $targetPath = dirname(__DIR__) . '/uploads/pdfs/' . $storedFileName;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    throw new RuntimeException('Failed to save uploaded file to server.');
                }

                // Extract text from PDF
                $extractedText = PDFService::extractText($targetPath);
                if ($extractedText === '') {
                    $extractedText = 'No selectable text was extracted from this PDF. It may contain scanned images without OCR.';
                }

                if ($title === '') {
                    $title = preg_replace('/\.pdf$/i', '', $cleanBase);
                    $title = ucwords(str_replace(['_', '-'], ' ', $title));
                }

                $relativePath = 'uploads/pdfs/' . $storedFileName;
                $noteId = Database::insert(
                    "INSERT INTO notes (user_id, subject_id, title, file_name, file_path, extracted_text, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $subjectId, $title, $cleanBase, $relativePath, $extractedText, (int) $file['size']]
                );

                flash('success', 'PDF uploaded successfully! Extracted ' . number_format(strlen($extractedText)) . ' characters.');
                redirect('user/notes.php');
            } catch (Throwable $e) {
                flash('danger', 'Error processing upload: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Upload PDF Notes';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <section class="glass-card p-4 p-md-5">
      <div class="text-center mb-4">
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i>Upload Study Notes</h3>
        <p class="text-muted small">Upload your class syllabus, textbook chapters, or lecture slides (PDF up to 25MB)</p>
      </div>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- Drag and Drop Zone -->
        <label class="drop-zone mb-4" id="dropZone" style="border: 2px dashed rgba(245, 158, 11, 0.4); border-radius: 12px; padding: 40px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; background: rgba(245, 158, 11, 0.03); transition: all 0.2s ease;">
          <input type="file" name="pdf_file" id="pdfFileInput" accept="application/pdf" hidden required>
          <i class="fa-solid fa-file-pdf fa-3x text-warning mb-3"></i>
          <h5 class="fw-bold mb-1">Click to browse or drop your PDF here</h5>
          <p class="text-muted small mb-2">Supported format: PDF only. Maximum file size: 25MB.</p>
          <span class="badge theme-box px-3 py-2" id="fileNamePreview">No file chosen</span>
        </label>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label small fw-bold">Note Title (optional)</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 3: Normalization & Functional Dependencies">
          </div>
          <div class="col-md-5">
            <label class="form-label small fw-bold">Subject Module</label>
            <select name="subject_id" class="form-select">
              <option value="">General / Other</option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="text-end mt-4">
          <a href="notes.php" class="btn btn-outline-light me-2">Cancel</a>
          <button type="submit" class="btn btn-gold px-4">
            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Upload & Extract Text
          </button>
        </div>
      </form>
    </section>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const dropZone = document.getElementById('dropZone');
  const input = document.getElementById('pdfFileInput');
  const preview = document.getElementById('fileNamePreview');

  if (!dropZone || !input) return;

  ['dragenter', 'dragover'].forEach(name => {
    dropZone.addEventListener(name, (e) => {
      e.preventDefault();
      dropZone.style.background = 'rgba(245, 158, 11, 0.12)';
      dropZone.style.borderColor = '#f59e0b';
    });
  });

  ['dragleave', 'drop'].forEach(name => {
    dropZone.addEventListener(name, (e) => {
      e.preventDefault();
      dropZone.style.background = 'rgba(245, 158, 11, 0.03)';
      dropZone.style.borderColor = 'rgba(245, 158, 11, 0.4)';
    });
  });

  dropZone.addEventListener('drop', (e) => {
    if (e.dataTransfer.files?.length) {
      input.files = e.dataTransfer.files;
      preview.textContent = e.dataTransfer.files[0].name;
      preview.className = 'badge text-bg-success px-3 py-2';
    }
  });

  input.addEventListener('change', () => {
    if (input.files?.length) {
      preview.textContent = input.files[0].name;
      preview.className = 'badge text-bg-success px-3 py-2';
    }
  });
});
</script>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
