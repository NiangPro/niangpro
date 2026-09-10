<?php

namespace Niang\Core;

/**
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 */
class Log
{
    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public static function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            self::interpolate($message, $context),
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        $dir = base_path('storage/logs');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents("$dir/" . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function __callStatic(string $name, array $arguments): void
    {
        if (!in_array($name, self::LEVELS, true)) {
            throw new \BadMethodCallException("Niveau de log inconnu : $name");
        }

        self::log($name, $arguments[0] ?? '', $arguments[1] ?? []);
    }

    private static function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        return strtr($message, $replace);
    }
}
