<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Env.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';

use App\Config\Env;
use App\Core\Auth;

if (!Auth::isAdmin()) {
    json_response(['ok' => false, 'message' => 'Access denied: Administrator privileges required.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
verify_csrf($input['csrf_token'] ?? null);

$key = trim((string) ($input['gemini_api_key'] ?? ''));
$val = $key === '' ? 'your_GEMINI_API_KEY_here' : $key;

Env::set('GEMINI_API_KEY', $val);

json_response([
    'ok' => true,
    'message' => $key === '' ? 'API Key cleared. Switched to Note Intelligence.' : 'Gemini Cloud API Key saved successfully!',
    'has_key' => $key !== ''
]);
