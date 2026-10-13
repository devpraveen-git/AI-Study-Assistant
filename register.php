<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Config/App.php';
require_once __DIR__ . '/app/Config/Database.php';
require_once __DIR__ . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

if (Auth::check()) {
    redirect('user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || !$email || strlen($password) < 6) {
        flash('danger', 'Please enter your name, a valid email, and a password of at least 6 characters.');
    } else {
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            flash('danger', 'An account with that email already exists.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $userId = Database::insert(
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')",
                [$name, $email, $hash]
            );

            $user = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'student'
            ];
            Auth::login($user);
            flash('success', 'Your account has been created! Welcome to AI Study Assistant.');
            redirect('user/dashboard.php');
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/app/Views/header.php';
?>
<main class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-5">
  <div style="width: min(100%, 480px);">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="index.php" class="btn btn-outline-theme btn-sm rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Home
      </a>
      <button class="btn-icon theme-toggle-btn" type="button" aria-label="Toggle Theme">
        <i class="fa-solid fa-moon"></i>
      </button>
    </div>

    <div class="glass-card p-4 p-md-5">
      <div class="text-center mb-4">
        <div class="brand-icon mx-auto mb-3"><i class="fa-solid fa-user-plus"></i></div>
        <h2 class="fw-bold mb-1">Create Student Account</h2>
        <p class="text-muted small">Start studying from your own PDF notes with AI</p>
      </div>

      <form method="post" action="register.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
          <label class="form-label small fw-bold">Full Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="name" class="form-control" required placeholder="Praveenkumar S">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" required placeholder="student@example.com">
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold">Password (min 6 characters)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" minlength="6" class="form-control" required placeholder="••••••••">
          </div>
        </div>

        <button type="submit" class="btn btn-gold w-100 py-2 fw-bold rounded-pill">
          <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Create Account
        </button>
      </form>

      <p class="text-center mt-4 mb-0 text-muted small">
        Already registered? <a href="login.php" class="text-warning fw-bold">Sign in here</a>
      </p>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
