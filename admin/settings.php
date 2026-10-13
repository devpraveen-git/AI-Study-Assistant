<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Config/Env.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Env;
use App\Core\Auth;

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $apiKey = trim($_POST['gemini_api_key'] ?? '');
    $model = trim($_POST['gemini_model'] ?? 'gemini-3.6-flash');

    Env::set('GEMINI_API_KEY', $apiKey === '' ? 'your_GEMINI_API_KEY_here' : $apiKey);
    Env::set('GEMINI_MODEL', $model);

    flash('success', 'Global system and AI settings saved successfully.');
    redirect('admin/settings.php');
}

$currentKey = Env::get('GEMINI_API_KEY');
$hasKey = $currentKey !== '' && $currentKey !== 'your_GEMINI_API_KEY_here';
$currentModel = Env::get('GEMINI_MODEL', 'gemini-3.6-flash');

$pageTitle = 'System & AI Settings';
require_once dirname(__DIR__) . '/app/Views/header.php';
require_once dirname(__DIR__) . '/app/Views/navbar.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <section class="glass-card p-4 p-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bold mb-1"><i class="fa-solid fa-sliders text-warning me-2"></i>Global AI & System Settings</h4>
          <p class="text-muted small mb-0">Configure Cloud AI integrations and platform parameters</p>
        </div>
        <span class="badge <?= $hasKey ? 'text-bg-success' : 'text-bg-warning' ?> px-3 py-2">
          <?= $hasKey ? 'Cloud AI Mode' : 'Note Intelligence Mode' ?>
        </span>
      </div>

      <form method="post" action="settings.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-4">
          <label class="form-label small fw-bold">Google Gemini API Key (Global)</label>
          <input type="password" name="gemini_api_key" class="form-control font-monospace" 
                 value="<?= e($hasKey ? $currentKey : '') ?>" 
                 placeholder="AIzaSy... (leave blank to enforce Note Intelligence)">
          <small class="text-muted d-block mt-1">If set, all students without custom keys will use this Gemini Cloud AI connection.</small>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold">Active Gemini Model</label>
          <select name="gemini_model" class="form-select font-monospace">
            <option value="gemini-3.6-flash" <?= $currentModel === 'gemini-3.6-flash' ? 'selected' : '' ?>>gemini-3.6-flash (Recommended, Latest & Fastest)</option>
            <option value="gemini-3.5-flash" <?= $currentModel === 'gemini-3.5-flash' ? 'selected' : '' ?>>gemini-3.5-flash (Fast & Balanced)</option>
            <option value="gemini-2.5-flash" <?= $currentModel === 'gemini-2.5-flash' ? 'selected' : '' ?>>gemini-2.5-flash</option>
            <option value="gemini-1.5-flash" <?= $currentModel === 'gemini-1.5-flash' ? 'selected' : '' ?>>gemini-1.5-flash</option>
            <option value="gemini-1.5-pro" <?= $currentModel === 'gemini-1.5-pro' ? 'selected' : '' ?>>gemini-1.5-pro (Deep Reasoning)</option>
          </select>
        </div>

        <div class="p-3 bg-dark bg-opacity-50 rounded border border-secondary border-opacity-25 mb-4">
          <h6 class="text-warning fw-bold mb-2"><i class="fa-solid fa-shield-halved me-1"></i>Fallback Assurance</h6>
          <p class="small text-muted mb-0">
            If the Gemini API key is missing, invalid, or reaches rate limits, the application automatically switches to the built-in <strong>Note Intelligence</strong> engine so students can continue asking questions and generating quizzes without interruptions.
          </p>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-gold px-4">
            <i class="fa-solid fa-check me-2"></i>Save System Settings
          </button>
        </div>
      </form>
    </section>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/app/Views/footer.php'; ?>
