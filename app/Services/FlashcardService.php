<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;

final class FlashcardService
{
    public static function getForNote(int $userId, int $noteId): array
    {
        $cards = Database::query(
            "SELECT id, front, back, category, mastery_level FROM flashcards WHERE user_id = ? AND note_id = ? ORDER BY id ASC",
            [$userId, $noteId]
        );

        if (!empty($cards)) {
            return $cards;
        }

        // Generate flashcards from note text if not already created
        return self::generateForNote($userId, $noteId);
    }

    public static function generateForNote(int $userId, int $noteId): array
    {
        $note = Database::fetchOne("SELECT title, file_name, extracted_text FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        if (!$note) {
            $note = Database::fetchOne("SELECT title, file_name, extracted_text FROM notes WHERE id = ?", [$noteId]);
            if (!$note) {
                return [];
            }
        }

        $context = $note['extracted_text'];
        $rawLines = array_filter(array_map('trim', explode("\n", $context)), fn($l) => $l !== '');

        $cards = [];
        $currentItem = null;
        foreach ($rawLines as $line) {
            if (str_contains($line, "\t")) {
                if ($currentItem !== null) {
                    $cards[] = $currentItem;
                }
                $parts = array_map('trim', explode("\t", $line));
                if (count($parts) >= 3 && strtolower($parts[0]) !== 'attribute') {
                    $currentItem = [
                        'front' => $parts[0] . ' (' . $parts[1] . ')',
                        'back' => $parts[2],
                        'category' => $parts[1]
                    ];
                } else {
                    $currentItem = null;
                }
            } elseif ($currentItem !== null && !str_starts_with($line, '?') && !str_starts_with($line, '❮') && !str_starts_with($line, '❯')) {
                $currentItem['back'] .= ' ' . $line;
            } elseif (preg_match('/^([A-Za-z0-9_ -]{2,35})\s*[:–-]\s*(.+)/', $line, $m)) {
                if ($currentItem !== null) {
                    $cards[] = $currentItem;
                }
                $currentItem = [
                    'front' => trim($m[1]),
                    'back' => trim($m[2]),
                    'category' => 'Core Concept'
                ];
            }
        }
        if ($currentItem !== null) {
            $cards[] = $currentItem;
        }

        if (empty($cards)) {
            // General text: create flashcards from sentences
            foreach ($rawLines as $l) {
                if (strlen($l) > 30 && strlen($l) < 200) {
                    $words = explode(' ', $l);
                    $key = $words[0] . ' ' . ($words[1] ?? '');
                    $cards[] = [
                        'front' => $key,
                        'back' => $l,
                        'category' => 'Summary'
                    ];
                }
            }
        }

        $cards = array_slice($cards, 0, 20);

        // Save generated cards to database
        $created = [];
        foreach ($cards as $c) {
            $id = Database::insert(
                "INSERT INTO flashcards (user_id, note_id, front, back, category, mastery_level) VALUES (?, ?, ?, ?, ?, 0)",
                [$userId, $noteId, $c['front'], $c['back'], $c['category']]
            );
            $created[] = [
                'id' => $id,
                'front' => $c['front'],
                'back' => $c['back'],
                'category' => $c['category'],
                'mastery_level' => 0
            ];
        }

        return $created;
    }

    public static function updateMastery(int $userId, int $cardId, int $masteryLevel): bool
    {
        $level = max(0, min(2, $masteryLevel));
        return Database::execute(
            "UPDATE flashcards SET mastery_level = ? WHERE id = ? AND user_id = ?",
            [$level, $cardId, $userId]
        ) > 0;
    }
}
