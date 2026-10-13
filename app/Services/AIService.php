<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use Throwable;

final class AIService
{
    public static function hasApiKey(): bool
    {
        $key = Env::get('GEMINI_API_KEY');
        return $key !== '' && $key !== 'your_GEMINI_API_KEY_here';
    }

    public static function ask(string $question, string $context, string $fileName, array $history = []): array
    {
        $question = trim($question);
        if ($question === '') {
            return ['answer' => 'Please enter a question about your study notes.', 'engine' => 'local'];
        }

        // 1. Try Gemini Cloud AI if key is set
        if (self::hasApiKey()) {
            try {
                $cloudAnswer = self::callGeminiApi($question, $context, $fileName, $history);
                if ($cloudAnswer !== '') {
                    return [
                        'answer' => $cloudAnswer,
                        'engine' => 'gemini'
                    ];
                }
            } catch (Throwable) {
                // Fall back gracefully to local engine
            }
        }

        // 2. Use Note Intelligence Engine
        $localAnswer = self::generateNoteIntelligenceAnswer($question, $context, $fileName);
        return [
            'answer' => $localAnswer,
            'engine' => 'local'
        ];
    }

    private static function callGeminiApi(string $question, string $context, string $fileName, array $history): string
    {
        $apiKey = Env::get('GEMINI_API_KEY');
        $model = Env::get('GEMINI_MODEL', 'gemini-3.6-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

        $systemInstruction = "You are an expert, encouraging university tutor for B.Sc Computer Science students. Answer questions clearly, accurately, and concisely using the provided PDF notes as the primary context. If a student greets you (e.g. 'hi', 'hello', 'hey'), greet them warmly as their AI study tutor and invite them to ask any question or request a summary of the loaded notes. Use clean markdown, bold terms, bullet points, and code blocks where helpful.";

        $trimmedContext = function_exists('mb_substr') ? mb_substr($context, 0, 24000) : substr($context, 0, 24000);
        $prompt = "DOCUMENT: {$fileName}\n\nNOTES CONTENT:\n{$trimmedContext}\n\nSTUDENT QUESTION:\n{$question}";

        $contents = [];
        // Add recent conversation history if provided
        if (!empty($history)) {
            $recent = array_slice($history, -4);
            foreach ($recent as $h) {
                $role = ($h['role'] ?? '') === 'user' ? 'user' : 'model';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => (string) ($h['message'] ?? '')]]
                ];
            }
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'temperature' => 0.5,
                'topP' => 0.9,
                'maxOutputTokens' => 3000,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status >= 400) {
            return '';
        }

        $data = json_decode($response, true);
        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    public static function generateNoteIntelligenceAnswer(string $question, string $context, string $fileName): string
    {
        $q = trim($question);
        $qLower = strtolower($q);
        $cleanTitle = preg_replace('/\.pdf$/i', '', $fileName);
        $cleanTitle = ucwords(str_replace(['_', '-'], ' ', $cleanTitle));

        // Check for greetings or small talk
        $isGreeting = preg_match('/^(hi|hello|hey|greetings|good\s*(morning|afternoon|evening)|howdy|sup|who\s*are\s*you|help(\s*me)?|start)(\s*!|\.|\?)*$/i', $qLower) === 1;
        if ($isGreeting) {
            $out = "### 👋 Hello! I'm your AI Study Tutor\n\n";
            $out .= "I'm loaded with your note: **" . htmlspecialchars($cleanTitle) . "** and ready to help you prepare for your exams.\n\n";
            $out .= "Here are a few quick things you can ask me:\n";
            $out .= "- 📖 **\"overview of this\"** — Get a structured revision summary and topic breakdown\n";
            $out .= "- 📝 **\"key exam questions from this note\"** — Generate university exam questions\n";
            $out .= "- 🔍 **\"explain the most important concepts\"** — Break down definitions and technical terms\n";
            $out .= "- 💡 **\"summarize in 5 bullet points\"** — High-impact quick revision points\n\n";
            $out .= "Feel free to type any question or click one of the suggestion chips above!";
            if (!self::hasApiKey()) {
                $out .= "\n\n---\n*⚡ Note Intelligence (Fast local study assistant)*";
            }
            return $out;
        }

        // Check for overview or summary intent
        $isOverview = preg_match('/\b(overview|summary|summarize|explain this|what is this|about this|contents|topics|introduction|syllabus|brief)\b/i', $qLower) === 1;

        $rawLines = array_filter(array_map('trim', explode("\n", $context)), fn($l) => $l !== '');

        // Parse structured terms / table items / definitions
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

        if ($isOverview) {
            $out = "### 📚 Study Overview: " . htmlspecialchars($cleanTitle) . "\n\n";
            $out .= "Here is a structured revision summary of **" . htmlspecialchars($cleanTitle) . "** prepared for your exams:\n\n";

            if (!empty($items)) {
                $totalItems = count($items);
                $categories = array_filter(array_unique(array_column($items, 'category')), fn($c) => strlen($c) > 1 && strlen($c) < 50);

                $out .= "#### 🔍 Key Document Metrics & Scope:\n";
                $out .= "- **Total Identified Key Concepts / Terms**: ~{$totalItems} items\n";
                if (!empty($categories)) {
                    $out .= "- **Primary Contexts & Groups**: " . implode(', ', array_slice($categories, 0, 7)) . "\n";
                }

                $out .= "\n#### 📌 Key Terms & Definitions:\n";
                $sample = array_slice($items, 0, 8);
                foreach ($sample as $it) {
                    $out .= "- **`{$it['term']}`** (" . htmlspecialchars($it['category']) . "): " . htmlspecialchars($it['desc']) . "\n";
                }
            } else {
                $sampleLines = array_slice($rawLines, 0, 7);
                $out .= "#### 📌 Core Sections from Notes:\n";
                foreach ($sampleLines as $l) {
                    $out .= "- " . htmlspecialchars($l) . "\n";
                }
            }

            $out .= "\n#### 💡 B.Sc CS Exam Strategy:\n";
            $out .= "1. Focus on correct syntax, parameter types, and common return values/behaviors.\n";
            $out .= "2. Be prepared for 2-mark definition questions and 5-mark comparative questions.\n";

            if (!self::hasApiKey()) {
                $out .= "\n---\n*⚡ Generated by Built-in Note Intelligence. (You can also connect Google Gemini Cloud AI in Profile & Settings).*";
            }
            return $out;
        }

        // Specific Question Answering
        $stopWords = [
            'what', 'is', 'are', 'the', 'a', 'an', 'of', 'in', 'on', 'for', 'to', 'explain',
            'describe', 'define', 'tell', 'me', 'about', 'can', 'you', 'how', 'why', 'which',
            'does', 'do', 'with', 'from', 'my', 'pdf', 'notes', 'this', 'give', 'hi', 'hello',
            'hey', 'please', 'show', 'find', 'want', 'need', 'say', 'it', 'simply'
        ];
        $words = array_filter(preg_split('/[^A-Za-z0-9_-]+/', $qLower), fn($w) => strlen($w) > 1 && !in_array($w, $stopWords, true));

        $bestMatches = [];
        foreach ($items as $it) {
            $score = 0;
            $termLower = strtolower($it['term']);
            $descLower = strtolower($it['desc']);
            $catLower = strtolower($it['category']);

            if (str_contains($qLower, $termLower)) {
                $score += 25;
            }
            foreach ($words as $w) {
                if ($termLower === $w) $score += 15;
                elseif (strlen($w) >= 3 && preg_match('/\b' . preg_quote($w, '/') . '\b/i', $termLower)) $score += 7;
                if (strlen($w) >= 3 && preg_match('/\b' . preg_quote($w, '/') . '\b/i', $descLower)) $score += 4;
                if (strlen($w) >= 3 && preg_match('/\b' . preg_quote($w, '/') . '\b/i', $catLower)) $score += 2;
            }
            if ($score > 0) {
                $bestMatches[] = ['item' => $it, 'score' => $score];
            }
        }

        usort($bestMatches, fn($a, $b) => $b['score'] <=> $a['score']);

        if (!empty($bestMatches) && $bestMatches[0]['score'] >= 5) {
            $top = $bestMatches[0]['item'];
            $out = "### 📌 " . htmlspecialchars($top['term']) . "\n\n";
            $out .= "**Definition / Purpose**:\n" . htmlspecialchars($top['desc']) . "\n\n";
            $out .= "**Context / Tag / Scope**: `" . htmlspecialchars($top['category']) . "`\n\n";

            if (count($bestMatches) > 1) {
                $out .= "#### 🔍 Related in your Notes:\n";
                for ($i = 1; $i < min(4, count($bestMatches)); $i++) {
                    $rel = $bestMatches[$i]['item'];
                    $out .= "- **`{$rel['term']}`** (" . htmlspecialchars($rel['category']) . "): " . htmlspecialchars($rel['desc']) . "\n";
                }
                $out .= "\n";
            }

            $out .= "#### 💡 Quick Exam Tip:\n";
            $out .= "Remember how `{$top['term']}` integrates with `{$top['category']}` for practical exams and code syntax tests.\n";

            if (!self::hasApiKey()) {
                $out .= "\n---\n*⚡ Generated by Built-in Note Intelligence.*";
            }
            return $out;
        }

        // Line-by-line fallback with strict word-boundary matching (minimum 3 characters)
        $searchWords = array_filter($words, fn($w) => strlen($w) >= 3);
        $lineScores = [];
        if (!empty($searchWords)) {
            foreach ($rawLines as $line) {
                $score = 0;
                $lineLower = strtolower($line);
                foreach ($searchWords as $w) {
                    if (preg_match('/\b' . preg_quote($w, '/') . '\b/i', $lineLower)) {
                        $score += 5;
                    }
                }
                if ($score > 0) {
                    $lineScores[] = ['line' => $line, 'score' => $score];
                }
            }
            usort($lineScores, fn($a, $b) => $b['score'] <=> $a['score']);
        }

        if (!empty($lineScores)) {
            $out = "### 📖 Matching References from " . htmlspecialchars($cleanTitle) . "\n\n";
            $out .= "Here is what was found in your notes regarding **\"" . htmlspecialchars($q) . "\"**:\n\n";
            $sample = array_slice($lineScores, 0, 4);
            foreach ($sample as $ls) {
                $out .= "- " . htmlspecialchars($ls['line']) . "\n";
            }
            $out .= "\n---\n*⚡ Note Intelligence*";
            return $out;
        }

        return "### 📖 Note Search: " . htmlspecialchars($cleanTitle) . "\n\nI reviewed your PDF notes for **\"" . htmlspecialchars($q) . "\"**, but this specific term was not found in the text.\n\n**Suggestions:**\n- Try asking for an overview: *\"overview of this\"*\n- Check the spelling or ask about specific concepts covered in this note.\n\n---\n*⚡ Note Intelligence*";
    }
}
