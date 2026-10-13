<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Config\Env;

$currentUri = $_SERVER['PHP_SELF'] ?? '';
$user = Auth::user();
$isAdmin = Auth::isAdmin();

$studentLinks = [
    ['href' => 'user/dashboard.php', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
    ['href' => 'user/upload.php', 'icon' => 'fa-cloud-arrow-up', 'label' => 'Upload PDF'],
    ['href' => 'user/notes.php', 'icon' => 'fa-folder-open', 'label' => 'My Notes'],
    ['href' => 'user/ask_ai.php', 'icon' => 'fa-comments', 'label' => 'Ask AI Tutor'],
    ['href' => 'user/quiz.php', 'icon' => 'fa-circle-question', 'label' => 'Practice Quiz'],
    ['href' => 'user/flashcards.php', 'icon' => 'fa-layer-group', 'label' => 'Flashcards'],
    ['href' => 'user/history.php', 'icon' => 'fa-clock-rotate-left', 'label' => 'Quiz History'],
    ['href' => 'user/profile.php', 'icon' => 'fa-user-gear', 'label' => 'My Profile'],
];

$adminLinks = [
    ['href' => 'admin/dashboard.php', 'icon' => 'fa-chart-pie', 'label' => 'Admin Overview'],
    ['href' => 'admin/users.php', 'icon' => 'fa-users', 'label' => 'Manage Users'],
    ['href' => 'admin/uploads.php', 'icon' => 'fa-file-shield', 'label' => 'All Uploads'],
    ['href' => 'admin/reports.php', 'icon' => 'fa-chart-column', 'label' => 'Student Reports'],
    ['href' => 'admin/settings.php', 'icon' => 'fa-sliders', 'label' => 'System Settings'],
];
?>
<div class="app-wrapper">
  <!-- Sidebar -->
  <aside class="app-sidebar" id="appSidebar">
    <a href="<?= base_url('user/dashboard.php') ?>" class="sidebar-brand">
      <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
      <div>
        <span class="sidebar-brand-title">AI Study</span>
        <small class="text-warning text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.1em;">v2.0 Workspace</small>
      </div>
    </a>

    <nav class="sidebar-nav">
      <small class="text-muted text-uppercase fw-bold px-3 py-1 mt-2" style="font-size: 0.68rem;">Study Center</small>
      <?php foreach ($studentLinks as $item): ?>
        <?php $isActive = str_contains($currentUri, $item['href']); ?>
        <a class="nav-link-custom <?= $isActive ? 'active' : '' ?>" href="<?= base_url($item['href']) ?>">
          <i class="fa-solid <?= e($item['icon']) ?>"></i>
          <span><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>

      <?php if ($isAdmin): ?>
        <hr class="border-secondary my-3 opacity-25">
        <small class="text-warning text-uppercase fw-bold px-3 py-1" style="font-size: 0.68rem;">Admin Portal</small>
        <?php foreach ($adminLinks as $item): ?>
          <?php $isActive = str_contains($currentUri, $item['href']); ?>
          <a class="nav-link-custom <?= $isActive ? 'active' : '' ?>" href="<?= base_url($item['href']) ?>">
            <i class="fa-solid <?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>
  </aside>

  <!-- Main Content Area -->
  <div class="app-main <?= isset($isChatPage) && $isChatPage ? 'chat-page-main' : '' ?>">
    <!-- Topbar Header -->
    <header class="app-topbar glass-card">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-icon d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle Navigation">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="page-headline">
          <p class="subtitle"><?= $isAdmin ? 'Administrator Mode' : 'B.Sc Computer Science Workspace' ?></p>
          <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
      </div>

      <div class="topbar-actions">
        <!-- Topbar Status Indicator -->
        <?php if ($isAdmin): ?>
          <?php $hasGeminiKey = Env::get('GEMINI_API_KEY') !== '' && Env::get('GEMINI_API_KEY') !== 'your_GEMINI_API_KEY_here'; ?>
          <span class="badge <?= $hasGeminiKey ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-3 py-2 d-none d-md-inline-block">
            <i class="fa-solid <?= $hasGeminiKey ? 'fa-cloud' : 'fa-brain' ?> me-1"></i>
            <?= $hasGeminiKey ? e(Env::get('GEMINI_MODEL', 'Gemini Cloud AI')) : 'Note Intelligence' ?>
          </span>
        <?php else: ?>
          <span class="badge bg-success rounded-pill px-3 py-2 d-none d-md-inline-block">
            <i class="fa-solid fa-graduation-cap me-1"></i>Student Workspace
          </span>
        <?php endif; ?>

        <!-- Theme Switcher -->
        <button class="btn-icon theme-toggle-btn" type="button" aria-label="Switch Theme">
          <i class="fa-solid fa-moon"></i>
        </button>

        <!-- User Dropdown / Profile -->
        <a href="<?= base_url('user/profile.php') ?>" class="user-badge-avatar text-decoration-none" title="<?= e($user['name'] ?? 'User') ?>">
          <?= e(substr($user['name'] ?? 'U', 0, 1)) ?>
        </a>

        <!-- Logout -->
        <a href="<?= base_url('logout.php') ?>" class="btn btn-outline-danger btn-sm px-3 rounded-pill" title="Sign Out">
          <i class="fa-solid fa-arrow-right-from-bracket me-1"></i><span class="d-none d-sm-inline">Logout</span>
        </a>
      </div>
    </header>
