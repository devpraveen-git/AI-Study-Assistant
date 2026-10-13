<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Config/Env.php';
require_once __DIR__ . '/app/Config/Database.php';

use App\Config\Env;
use App\Config\Database;

$host = Env::get('DB_HOST', 'localhost');
$port = Env::get('DB_PORT', '3306');
$user = Env::get('DB_USER', 'root');
$pass = Env::get('DB_PASS', '');
$name = Env::get('DB_NAME', 'ai_study_assistant_v2');

$messages = [];
$error = null;

try {
    // 1. Connect without db name to ensure database exists
    $rootPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = "Database `{$name}` verified / created.";

    // 2. Run schema
    $schemaFile = __DIR__ . '/app/Database/schema.sql';
    if (!is_file($schemaFile)) {
        throw new RuntimeException("Schema file not found at: {$schemaFile}");
    }
    $schemaSql = file_get_contents($schemaFile);
    
    $dbPdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $dbPdo->exec($schemaSql);
    $messages[] = "Database tables and subjects initialized.";

    // 3. Seed Admin
    $adminEmail = Env::get('ADMIN_EMAIL', 'admin@aistudy.local');
    $adminPass = Env::get('ADMIN_PASSWORD', 'Admin@123');
    $adminName = Env::get('ADMIN_NAME', 'System Administrator');

    $stmt = $dbPdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if (!$stmt->fetch()) {
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $ins = $dbPdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $ins->execute([$adminName, $adminEmail, $hash]);
        $messages[] = "Default Administrator created ({$adminEmail} / {$adminPass}).";
    }

    // 4. Seed Demo Student
    $studentEmail = Env::get('DEMO_STUDENT_EMAIL', 'student@aistudy.local');
    $studentPass = Env::get('DEMO_STUDENT_PASSWORD', 'Student@123');
    $studentName = Env::get('DEMO_STUDENT_NAME', 'Computer Science Student');

    $stmt = $dbPdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$studentEmail]);
    $student = $stmt->fetch();
    $studentId = $student ? (int) $student['id'] : 0;
    if (!$studentId) {
        $hash = password_hash($studentPass, PASSWORD_DEFAULT);
        $ins = $dbPdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
        $ins->execute([$studentName, $studentEmail, $hash]);
        $studentId = (int) $dbPdo->lastInsertId();
        $messages[] = "Default Student created ({$studentEmail} / {$studentPass}).";
    }

    // 5. Seed sample note if available
    $newPdfDir = __DIR__ . '/uploads/pdfs';
    if (!is_dir($newPdfDir)) {
        mkdir($newPdfDir, 0777, true);
    }

    $sampleName = '1_sample_HTML_Attributes.pdf';
    $samplePath = $newPdfDir . '/' . $sampleName;
    $oldPdf = 'C:/xampp/htdocs/AI-Study-Assistant/uploads/pdfs/2_1785743619_HTML_Attributes.pdf';
    if (!is_file($samplePath) && is_file($oldPdf)) {
        copy($oldPdf, $samplePath);
    }

    if (is_file($samplePath)) {

        $chk = $dbPdo->prepare("SELECT id FROM notes WHERE file_name = ?");
        $chk->execute(['HTML_Attributes.pdf']);
        if (!$chk->fetch()) {
            // Extract text from sample PDF
            require_once __DIR__ . '/vendor/autoload.php';
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($samplePath);
            $text = trim($pdf->getText());

            $subjectId = 5; // Web Technologies
            $insNote = $dbPdo->prepare("INSERT INTO notes (user_id, subject_id, title, file_name, file_path, extracted_text, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insNote->execute([
                $studentId,
                $subjectId,
                'HTML Attributes Reference',
                'HTML_Attributes.pdf',
                'uploads/pdfs/' . $sampleName,
                $text,
                filesize($samplePath)
            ]);
            $messages[] = "Pre-loaded sample note `HTML_Attributes.pdf` for immediate testing.";
        }
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
}

if (php_sapi_name() === 'cli') {
    if ($error) {
        echo "SETUP ERROR: {$error}\n";
        exit(1);
    }
    echo "SETUP COMPLETED SUCCESSFULLY!\n";
    foreach ($messages as $msg) {
        echo " - {$msg}\n";
    }
    exit(0);
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup - AI Study Assistant v2</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #06150e; color: #f7fbf8; min-height: 100vh; display: grid; place-items: center; padding: 20px; }
    .setup-card { max-width: 580px; width: 100%; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12); border-radius: 12px; padding: 32px; backdrop-filter: blur(20px); }
    .btn-gold { background: linear-gradient(135deg, #D4A017, #FF6B35); border: none; color: #150f05; font-weight: 700; }
  </style>
</head>
<body>
  <div class="setup-card">
    <div class="text-center mb-4">
      <i class="fa-solid fa-graduation-cap fa-3x text-warning mb-2"></i>
      <h1 class="h3 font-weight-bold">AI Study Assistant v2</h1>
      <p class="text-muted">Automated Setup & Provisioning</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger mb-4">
        <h5><i class="fa-solid fa-triangle-exclamation me-2"></i>Setup Failed</h5>
        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
      </div>
    <?php else: ?>
      <div class="alert alert-success mb-4">
        <h5><i class="fa-solid fa-circle-check me-2"></i>Setup Complete!</h5>
        <ul class="mb-0 mt-2 ps-3">
          <?php foreach ($messages as $msg): ?>
            <li><?= htmlspecialchars($msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="card bg-dark border-secondary p-3 mb-4 text-start">
        <h6 class="text-warning mb-2"><i class="fa-solid fa-key me-2"></i>Default Login Credentials:</h6>
        <p class="mb-1"><strong>Student:</strong> <code>student@aistudy.local</code> / <code>Student@123</code></p>
        <p class="mb-0"><strong>Admin:</strong> <code>admin@aistudy.local</code> / <code>Admin@123</code></p>
      </div>

      <div class="d-grid gap-2">
        <a href="login.php" class="btn btn-gold btn-lg">Go to Login</a>
        <a href="index.php" class="btn btn-outline-light">Home Page</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
