<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Config/App.php';
require_once __DIR__ . '/app/Config/Database.php';
require_once __DIR__ . '/app/Core/Auth.php';

use App\Config\Database;
use App\Core\Auth;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        flash('danger', 'Please enter a valid email address and password.');
    } else {
        $user = Database::fetchOne("SELECT id, name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            if ((int) $user['is_active'] !== 1) {
                flash('danger', 'Your account has been deactivated. Please contact an administrator.');
            } else {
                Auth::login($user);
                flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
            }
        } else {
            flash('danger', 'Invalid email or password.');
        }
    }
}

$pageTitle = 'Student Sign In';
require_once __DIR__ . '/app/Views/header.php';
?>
<main class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-5">
  <div style="width: min(100%, 460px);">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="index.php" class="btn btn-outline-theme btn-sm rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Back
      </a>
      <button class="btn-icon theme-toggle-btn" type="button" aria-label="Toggle Theme">
        <i class="fa-solid fa-moon"></i>
      </button>
    </div>

    <div class="glass-card p-4 p-md-5">
      <div class="text-center mb-4">
        <div class="brand-icon mx-auto mb-3"><i class="fa-solid fa-user-graduate"></i></div>
        <h2 class="fw-bold mb-1">Student Sign In</h2>
        <p class="text-muted small">Enter your credentials to access your study workspace</p>
      </div>

      <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
          <label class="form-label small fw-bold">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" id="loginEmail" class="form-control" required placeholder="student@example.com" autofocus>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" id="loginPass" class="form-control" required placeholder="••••••••">
          </div>
        </div>

        <button type="submit" class="btn btn-gold w-100 py-2 fw-bold rounded-pill">
          <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Sign In
        </button>
      </form>

      <p class="text-center mt-4 mb-0 text-muted small">
        Don't have an account? <a href="register.php" class="text-warning fw-bold">Register here</a>
      </p>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
