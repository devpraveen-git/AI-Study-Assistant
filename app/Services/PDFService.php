<?php
declare(strict_types=1);

namespace App\Services;

use Smalot\PdfParser\Parser;
use Throwable;

final class PDFService
{
    public static function extractText(string $filePath): string
    {
        if (!is_file($filePath)) {
            return '';
        }

        // Try Smalot PDF Parser
        try {
            if (class_exists(Parser::class)) {
                $parser = new Parser();
                $pdf = $parser->parseFile($filePath);
                $text = trim($pdf->getText());
                if ($text !== '') {
                    return self::cleanText($text);
                }
            }
        } catch (Throwable) {
            // Fall through to fallback parser
        }

        // Fallback internal text stream extractor
        return self::extractRawTextFallback($filePath);
    }

    private static function cleanText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[^\P{C}\n\t]+/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        return trim($text);
    }

    private static function extractRawTextFallback(string $filePath): string
    {
        $content = @file_get_contents($filePath);
        if ($content === false || $content === '') {
            return '';
        }

        $text = '';
        // Extract text in BT ... ET blocks
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
            foreach ($matches[1] as $block) {
                if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $block, $tMatches)) {
                    $text .= implode(' ', $tMatches[1]) . "\n";
                }
            }
        }

        return self::cleanText($text);
    }
}
