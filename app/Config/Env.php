<?php
declare(strict_types=1);

namespace App\Config;

final class Env
{
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $val] = explode('=', $line, 2);
                self::$values[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        self::load();
        $envVal = $_ENV[$key] ?? getenv($key);
        if ($envVal !== false && $envVal !== null && $envVal !== '') {
            return (string) $envVal;
        }
        return self::$values[$key] ?? $default;
    }

    public static function set(string $key, string $value): bool
    {
        self::load();
        self::$values[$key] = $value;
        $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            return false;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $found = false;
        $newLines = [];
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), $key . '=')) {
                $newLines[] = $key . '="' . addcslashes($value, '"\\') . '"';
                $found = true;
            } else {
                $newLines[] = $line;
            }
        }
        if (!$found) {
            $newLines[] = $key . '="' . addcslashes($value, '"\\') . '"';
        }

        return file_put_contents($envPath, implode("\n", $newLines) . "\n") !== false;
    }
}
