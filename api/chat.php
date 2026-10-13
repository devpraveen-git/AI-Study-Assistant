<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';
require_once dirname(__DIR__) . '/app/Services/AIService.php';

use App\Config\Database;
use App\Core\Auth;
use App\Services\AIService;

if (!Auth::check()) {
    json_response(['ok' => false, 'message' => 'Please sign in to use the AI study chat.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true, flags: JSON_THROW_ON_ERROR);
} catch (Throwable) {
    json_response(['ok' => false, 'message' => 'Invalid JSON input.'], 400);
}

verify_csrf($input['csrf_token'] ?? null);

$noteId = (int) ($input['note_id'] ?? 0);
$question = trim((string) ($input['question'] ?? ''));

if ($noteId <= 0 || $question === '') {
    json_response(['ok' => false, 'message' => 'Please select a note and enter your study question.'], 400);
}

$userId = Auth::id();
$isAdmin = Auth::isAdmin();
$note = $isAdmin
    ? Database::fetchOne("SELECT id, title, file_name, extracted_text FROM notes WHERE id = ?", [$noteId])
    : Database::fetchOne("SELECT id, title, file_name, extracted_text FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId]);

if (!$note) {
    json_response(['ok' => false, 'message' => 'The selected note could not be found.'], 404);
}

// Fetch recent conversation history for this note
$history = Database::query(
    "SELECT role, message FROM chat_messages WHERE user_id = ? AND note_id = ? ORDER BY id DESC LIMIT 6",
    [$userId, $noteId]
);
$history = array_reverse($history);

// Generate AI response
$result = AIService::ask($question, $note['extracted_text'], $note['file_name'], $history);

// Save conversation to database
Database::insert(
    "INSERT INTO chat_messages (user_id, note_id, role, message) VALUES (?, ?, 'user', ?)",
    [$userId, $noteId, $question]
);
Database::insert(
    "INSERT INTO chat_messages (user_id, note_id, role, message) VALUES (?, ?, 'assistant', ?)",
    [$userId, $noteId, $result['answer']]
);

json_response([
    'ok' => true,
    'answer' => $result['answer'],
    'engine' => $result['engine']
]);
