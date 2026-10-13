<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Config/App.php';
require_once dirname(__DIR__) . '/Core/Auth.php';

use App\Core\Auth;

$pageTitle = $pageTitle ?? 'AI Study Assistant v2';
$currentUser = Auth::user();
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="dark light">
  <title><?= e($pageTitle) ?> - AI Study Assistant v2</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 & FontAwesome 6 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <!-- Custom Stylesheet -->
  <link href="<?= asset_url('css/style.css') ?>?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/css/style.css') ?>" rel="stylesheet">
  <script>(function(){const t=localStorage.getItem('v2_theme')||'dark';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
</head>
<body>

<!-- Toast Notification Center -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
  <?php foreach (consume_flash() as $msg): ?>
    <div class="toast align-items-center text-bg-<?= e($msg['type']) ?> border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-medium">
          <i class="fa-solid fa-circle-info me-2"></i><?= e($msg['message']) ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endforeach; ?>
</div>
