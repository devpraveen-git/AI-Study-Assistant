<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/App.php';
require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Core/Auth.php';
require_once dirname(__DIR__) . '/app/Services/AIService.php';
require_once dirname(__DIR__) . '/app/Services/QuizService.php';

use App\Core\Auth;
use App\Services\QuizService;

if (!Auth::check()) {
    json_response(['ok' => false, 'message' => 'Please sign in to take quizzes.'], 401);
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

$action = $input['action'] ?? 'generate';
$userId = Auth::id();

if ($action === 'generate') {
    $noteId = (int) ($input['note_id'] ?? 0);
    $count = max(5, min(20, (int) ($input['count'] ?? 10)));

    if ($noteId <= 0) {
        json_response(['ok' => false, 'message' => 'Select a note to generate a quiz.'], 400);
    }

    try {
        $quiz = QuizService::generate($userId, $noteId, $count);
        json_response([
            'ok' => true,
            'quiz_token' => $quiz['quiz_token'],
            'total' => $quiz['total'],
            'questions' => $quiz['questions'],
            'note_title' => $quiz['note_title']
        ]);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

if ($action === 'submit') {
    $quizToken = (string) ($input['quiz_token'] ?? '');
    $answers = (array) ($input['answers'] ?? []);
    $timeSpent = (int) ($input['time_spent'] ?? 0);

    if ($quizToken === '') {
        json_response(['ok' => false, 'message' => 'Quiz token is missing.'], 400);
    }

    try {
        $result = QuizService::submit($userId, $quizToken, $answers, $timeSpent);
        json_response([
            'ok' => true,
            'quiz_id' => $result['quiz_id'],
            'score' => $result['score'],
            'total' => $result['total'],
            'percentage' => $result['percentage'],
            'review' => $result['review']
        ]);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

if ($action === 'retake') {
    $quizId = (int) ($input['quiz_id'] ?? 0);
    if ($quizId <= 0) {
        json_response(['ok' => false, 'message' => 'Quiz ID is missing.'], 400);
    }

    try {
        $retake = QuizService::retake($userId, $quizId);
        json_response([
            'ok' => true,
            'quiz_token' => $retake['quiz_token'],
            'total' => $retake['total'],
            'questions' => $retake['questions'],
            'note_title' => $retake['note_title'],
            'note_id' => $retake['note_id']
        ]);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

json_response(['ok' => false, 'message' => 'Unknown action.'], 400);
