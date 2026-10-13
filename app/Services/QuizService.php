<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Config\Env;
use Throwable;

final class QuizService
{
    private const SESSION_KEY = 'v2_active_quizzes';

    public static function generate(int $userId, int $noteId, int $count = 10): array
    {
        $note = Database::fetchOne("SELECT id, title, file_name, extracted_text FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        if (!$note) {
            // Also allow if admin or public note
            $note = Database::fetchOne("SELECT id, title, file_name, extracted_text FROM notes WHERE id = ?", [$noteId]);
            if (!$note) {
                throw new \RuntimeException("Selected note not found.");
            }
        }

        $context = $note['extracted_text'];
        if (trim($context) === '') {
            throw new \RuntimeException("This PDF note has no readable text. Please upload a PDF with selectable text.");
        }

        $questions = [];

        // 1. Try Gemini Cloud AI if API key is set
        if (AIService::hasApiKey()) {
            try {
                $questions = self::generateWithGemini($context, $note['file_name'], $count);
            } catch (Throwable) {
                $questions = [];
            }
        }

        // 2. Fall back to local Note Intelligence
        if (empty($questions) || count($questions) < 3) {
            $questions = self::generateWithNoteIntelligence($context, $note['file_name'], $count);
        }

        $questions = array_slice(array_values($questions), 0, $count);
        if (empty($questions)) {
            throw new \RuntimeException("Could not generate quiz questions from this document. Please ensure it contains technical content.");
        }

        // Store full quiz in session for safe server-side grading
        $quizToken = bin2hex(random_bytes(16));
        $_SESSION[self::SESSION_KEY][$quizToken] = [
            'user_id' => $userId,
            'note_id' => $noteId,
            'count' => count($questions),
            'questions' => $questions, // contains correct_answer & explanation
            'created_at' => time()
        ];

        // Return client-safe questions (stripping out correct_answer and explanation until submitted)
        $clientQuestions = array_map(static fn($q) => [
            'question' => (string) ($q['question'] ?? ''),
            'option_a' => (string) ($q['option_a'] ?? ''),
            'option_b' => (string) ($q['option_b'] ?? ''),
            'option_c' => (string) ($q['option_c'] ?? ''),
            'option_d' => (string) ($q['option_d'] ?? ''),
        ], $questions);

        return [
            'quiz_token' => $quizToken,
            'total' => count($clientQuestions),
            'questions' => $clientQuestions,
            'note_title' => $note['title'] ?? $note['file_name']
        ];
    }

    public static function submit(int $userId, string $quizToken, array $userAnswers, int $timeSpent = 0): array
    {
        if (empty($_SESSION[self::SESSION_KEY][$quizToken])) {
            throw new \RuntimeException("Quiz session expired or already submitted. Please generate a new quiz.");
        }

        $cached = $_SESSION[self::SESSION_KEY][$quizToken];
        unset($_SESSION[self::SESSION_KEY][$quizToken]);

        $questions = $cached['questions'];
        $noteId = $cached['note_id'];
        $score = 0;
        $gradedQuestions = [];

        foreach ($questions as $idx => $q) {
            $correct = strtoupper((string) ($q['correct_answer'] ?? 'A'));
            $userAns = strtoupper((string) ($userAnswers[$idx] ?? ''));
            $isCorrect = $userAns !== '' && $userAns === $correct;

            if ($isCorrect) {
                $score++;
            }

            $gradedQuestions[] = [
                'question' => $q['question'],
                'option_a' => $q['option_a'],
                'option_b' => $q['option_b'],
                'option_c' => $q['option_c'],
                'option_d' => $q['option_d'],
                'correct_answer' => $correct,
                'user_answer' => $userAns,
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation'] ?? ''
            ];
        }

        $total = count($questions);
        $percent = $total > 0 ? (int) round(($score / $total) * 100) : 0;

        // Persist quiz attempt to database
        $quizId = Database::insert(
            "INSERT INTO quizzes (user_id, note_id, score, total_questions, time_spent) VALUES (?, ?, ?, ?, ?)",
            [$userId, $noteId, $score, $total, $timeSpent]
        );

        foreach ($gradedQuestions as $g) {
            Database::execute(
                "INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer, user_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$quizId, $g['question'], $g['option_a'], $g['option_b'], $g['option_c'], $g['option_d'], $g['correct_answer'], $g['user_answer'], $g['explanation']]
            );
        }

        return [
            'quiz_id' => $quizId,
            'score' => $score,
            'total' => $total,
            'percentage' => $percent,
            'review' => $gradedQuestions
        ];
    }

    public static function retake(int $userId, int $quizId): array
    {
        $quiz = Database::fetchOne("SELECT q.id, q.note_id, n.title, n.file_name FROM quizzes q LEFT JOIN notes n ON n.id = q.note_id WHERE q.id = ? AND q.user_id = ?", [$quizId, $userId]);
        if (!$quiz) {
            throw new \RuntimeException("Quiz not found.");
        }

        $rows = Database::query("SELECT question, option_a, option_b, option_c, option_d, correct_answer, explanation FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC", [$quizId]);
        if (empty($rows)) {
            throw new \RuntimeException("No questions found for this quiz.");
        }

        $quizToken = bin2hex(random_bytes(16));
        $_SESSION[self::SESSION_KEY][$quizToken] = [
            'user_id' => $userId,
            'note_id' => (int) $quiz['note_id'],
            'count' => count($rows),
            'questions' => $rows,
            'created_at' => time()
        ];

        $clientQuestions = array_map(static fn($q) => [
            'question' => (string) ($q['question'] ?? ''),
            'option_a' => (string) ($q['option_a'] ?? ''),
            'option_b' => (string) ($q['option_b'] ?? ''),
            'option_c' => (string) ($q['option_c'] ?? ''),
            'option_d' => (string) ($q['option_d'] ?? ''),
        ], $rows);

        return [
            'quiz_token' => $quizToken,
            'total' => count($clientQuestions),
            'questions' => $clientQuestions,
            'note_title' => $quiz['title'] ?? $quiz['file_name'] ?? 'Quiz',
            'note_id' => (int) $quiz['note_id']
        ];
    }

    private static function generateWithGemini(string $context, string $fileName, int $count): array
    {
        $apiKey = Env::get('GEMINI_API_KEY');
        $model = Env::get('GEMINI_MODEL', 'gemini-3.6-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

        $trimmedContext = function_exists('mb_substr') ? mb_substr($context, 0, 20000) : substr($context, 0, 20000);
        $prompt = "Create exactly {$count} high-quality multiple choice questions for B.Sc Computer Science students based on this document: {$fileName}.\n\n"
                . "DOCUMENT CONTENT:\n{$trimmedContext}\n\n"
                . "REQUIREMENTS:\n"
                . "- Return ONLY a valid JSON array of question objects.\n"
                . "- Each object must have keys: 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer' (must be 'A', 'B', 'C', or 'D'), 'explanation' (1-2 sentences).\n"
                . "- Ensure realistic distractors and accurate facts.";

        $payload = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json',
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 35,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return [];
        }

        $data = json_decode($response, true);
        $rawJson = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $rawJson = trim(preg_replace('/^```json\s*|\s*```$/i', '', trim($rawJson)));
        $decoded = json_decode($rawJson, true);

        if (isset($decoded['questions']) && is_array($decoded['questions'])) {
            return $decoded['questions'];
        }
        return is_array($decoded) ? $decoded : [];
    }

    public static function generateWithNoteIntelligence(string $context, string $fileName, int $count): array
    {
        $cleanTitle = preg_replace('/\.pdf$/i', '', $fileName);
        $cleanTitle = ucwords(str_replace(['_', '-'], ' ', $cleanTitle));

        $rawLines = array_filter(array_map('trim', explode("\n", $context)), fn($l) => $l !== '');

        $items = [];
        $currentItem = null;
        foreach ($rawLines as $line) {
            if (str_contains($line, "\t")) {
                if ($currentItem !== null) {
                    $items[] = $currentItem;
                }
                $parts = array_map('trim', explode("\t", $line));
                if (count($parts) >= 3 && strtolower($parts[0]) !== 'attribute') {
                    $currentItem = [
                        'term' => $parts[0],
                        'category' => $parts[1],
                        'desc' => $parts[2]
                    ];
                } else {
                    $currentItem = null;
                }
            } elseif ($currentItem !== null && !str_starts_with($line, '?') && !str_starts_with($line, '❮') && !str_starts_with($line, '❯')) {
                $currentItem['desc'] .= ' ' . $line;
            } elseif (preg_match('/^([A-Za-z0-9_ -]{2,35})\s*[:–-]\s*(.+)/', $line, $m)) {
                if ($currentItem !== null) {
                    $items[] = $currentItem;
                }
                $currentItem = [
                    'term' => trim($m[1]),
                    'category' => 'Core Concept',
                    'desc' => trim($m[2])
                ];
            }
        }
        if ($currentItem !== null) {
            $items[] = $currentItem;
        }

        $questions = [];
        $letters = ['A', 'B', 'C', 'D'];
        $validItems = array_values(array_filter($items, fn($it) => strlen($it['desc']) > 12 && strlen($it['term']) >= 2));

        if (count($validItems) >= $count) {
            shuffle($validItems);
            $selected = array_slice($validItems, 0, $count);

            foreach ($selected as $idx => $it) {
                $correctLetter = $letters[$idx % 4];
                $otherTerms = array_values(array_filter($validItems, fn($o) => $o['term'] !== $it['term']));
                shuffle($otherTerms);
                $distractors = array_slice($otherTerms, 0, 3);

                if ($idx % 2 === 0) {
                    $qText = "In {$cleanTitle}, what is the function of '{$it['term']}'?";
                    $correctOption = $it['desc'];
                    $wrongOptions = array_map(fn($d) => $d['desc'], $distractors);
                } else {
                    $shortDesc = rtrim($it['desc'], '.');
                    $qText = "Which item/attribute is defined as: \"{$shortDesc}\"?";
                    $correctOption = $it['term'];
                    $wrongOptions = array_map(fn($d) => $d['term'], $distractors);
                }

                while (count($wrongOptions) < 3) {
                    $wrongOptions[] = 'None of the above';
                }

                $optionsPool = $wrongOptions;
                $opts = [];
                foreach ($letters as $l) {
                    if ($l === $correctLetter) {
                        $opts['option_' . strtolower($l)] = $correctOption;
                    } else {
                        $opts['option_' . strtolower($l)] = array_shift($optionsPool);
                    }
                }

                $questions[] = [
                    'question' => $qText,
                    'option_a' => (string) $opts['option_a'],
                    'option_b' => (string) $opts['option_b'],
                    'option_c' => (string) $opts['option_c'],
                    'option_d' => (string) $opts['option_d'],
                    'correct_answer' => $correctLetter,
                    'explanation' => "'{$it['term']}' ({$it['category']}): {$it['desc']}."
                ];
            }
        } else {
            // General text document extraction
            $sentences = [];
            foreach ($rawLines as $line) {
                $parts = preg_split('/(?<=[.?!])\s+/', $line);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (strlen($p) > 25 && strlen($p) < 180) {
                        $sentences[] = $p;
                    }
                }
            }
            $sentences = array_values(array_unique($sentences));
            shuffle($sentences);
            $selectedSentences = array_slice($sentences, 0, $count);

            foreach ($selectedSentences as $idx => $sentence) {
                $correctLetter = $letters[$idx % 4];
                $words = explode(' ', $sentence);
                $keyWord = '';
                for ($i = 0; $i < count($words); $i++) {
                    if (strlen($words[$i]) > 4 && !in_array(strtolower($words[$i]), ['which', 'there', 'their', 'these', 'those', 'where', 'about'])) {
                        $keyWord = trim($words[$i], ".,:;()[]{}'\"");
                        break;
                    }
                }
                if ($keyWord === '') $keyWord = 'Concept';

                $maskedSentence = preg_replace('/\b' . preg_quote($keyWord, '/') . '\b/i', '______', $sentence, 1);
                $qText = "Complete the statement from {$cleanTitle}: \"{$maskedSentence}\"";

                $distractorWords = ['Syntax', 'Interface', 'Protocol', 'Variable', 'Function', 'Attribute', 'Component', 'Structure'];
                shuffle($distractorWords);
                $distractorWords = array_values(array_filter($distractorWords, fn($w) => strtolower($w) !== strtolower($keyWord)));

                $opts = [];
                $distPool = array_slice($distractorWords, 0, 3);
                foreach ($letters as $l) {
                    if ($l === $correctLetter) {
                        $opts['option_' . strtolower($l)] = $keyWord;
                    } else {
                        $opts['option_' . strtolower($l)] = array_shift($distPool) ?? 'Other';
                    }
                }

                $questions[] = [
                    'question' => $qText,
                    'option_a' => (string) $opts['option_a'],
                    'option_b' => (string) $opts['option_b'],
                    'option_c' => (string) $opts['option_c'],
                    'option_d' => (string) $opts['option_d'],
                    'correct_answer' => $correctLetter,
                    'explanation' => "The correct term is '{$keyWord}' as referenced in: \"{$sentence}\"."
                ];
            }
        }

        return $questions;
    }
}
