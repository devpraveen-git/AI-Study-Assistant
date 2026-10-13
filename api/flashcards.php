<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';
require_once dirname(__DIR__) . '/app/Services/FlashcardService.php';

use App\Core\Auth;
use App\Services\FlashcardService;

if (!Auth::check()) {
    json_response(['ok' => false, 'message' => 'Please sign in.'], 401);
}

$userId = Auth::id();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $noteId = (int) ($_GET['note_id'] ?? 0);
    if ($noteId <= 0) {
        json_response(['ok' => false, 'message' => 'Note ID required.'], 400);
    }

    $cards = FlashcardService::getForNote($userId, $noteId);
    json_response(['ok' => true, 'flashcards' => $cards]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
    verify_csrf($input['csrf_token'] ?? null);

    $cardId = (int) ($input['card_id'] ?? 0);
    $mastery = (int) ($input['mastery'] ?? 0);

    if ($cardId > 0) {
        FlashcardService::updateMastery($userId, $cardId, $mastery);
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'message' => 'Invalid card ID.'], 400);
}
