<?php

declare(strict_types=1);

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
    /** Du plus grave au moins grave (PSR-3). */
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /**
     * Clés de contexte jamais écrites en clair (roadmap : ne jamais loguer mots de passe, jetons,
     * secrets, données de carte), à n'importe quelle profondeur du contexte.
     */
    private const SENSITIVE = '/pass(word|wd)?|secret|token|api[_-]?key|authorization|cookie|card|cvv|cvc|iban/i';

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!self::shouldLog($level)) {
            return;
        }

        $context = self::redact($context);

        $text = sprintf(
            '%s: %s%s',
            strtoupper($level),
            self::interpolate($message, $context),
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        $line = '[' . date('Y-m-d H:i:s') . "] $text\n";

        match (self::channel()) {
            'single' => self::writeFile(base_path('storage/logs/niangpro.log'), $line),
            'errorlog' => error_log($text),
            'syslog' => self::toSyslog($level, $text),
            'stderr' => file_put_contents('php://stderr', $line),
            default => self::writeDaily($line),
        };
    }

    /**
     * LOG_CHANNEL : 'daily' (défaut, un fichier par jour, conservation LOG_DAYS), 'single' (un seul
     * fichier storage/logs/niangpro.log), 'errorlog' (error_log de PHP, donc le journal du serveur
     * web ou de PHP-FPM), 'syslog' (journal système), 'stderr' (conteneurs Docker). Une valeur
     * inconnue retombe sur 'daily' : journaliser ne doit jamais faire échouer une requête.
     */
    public static function channel(): string
    {
        $channel = (string) Config::get('logging.channel', 'daily');

        return in_array($channel, ['daily', 'single', 'errorlog', 'syslog', 'stderr'], true) ? $channel : 'daily';
    }

    private static function toSyslog(string $level, string $text): void
    {
        $priority = match (strtolower($level)) {
            'emergency' => LOG_EMERG,
            'alert' => LOG_ALERT,
            'critical' => LOG_CRIT,
            'error' => LOG_ERR,
            'warning' => LOG_WARNING,
            'notice' => LOG_NOTICE,
            'info' => LOG_INFO,
            default => LOG_DEBUG,
        };

        openlog((string) Config::get('logging.syslog_ident', 'niangpro'), LOG_PID, LOG_USER);
        syslog($priority, $text);
        closelog();
    }

    private static function writeFile(string $file, string $line): void
    {
        $dir = dirname($file);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function writeDaily(string $line): void
    {
        $dir = base_path('storage/logs');

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $file = "$dir/" . date('Y-m-d') . '.log';

        // Premier message de la journée : le bon moment pour supprimer les fichiers trop anciens.
        if (!is_file($file)) {
            self::prune($dir);
        }

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /** Vrai si $level atteint le niveau minimal (logging.level) ; un niveau configuré inconnu vaut 'debug'. */
    public static function shouldLog(string $level): bool
    {
        $minimum = array_search(strtolower((string) Config::get('logging.level', 'debug')), self::LEVELS, true);
        $current = array_search(strtolower($level), self::LEVELS, true);

        if ($minimum === false || $current === false) {
            return true;
        }

        return $current <= $minimum;
    }

    /** Supprime les fichiers de plus de logging.days jours (0 : jamais). @return int fichiers supprimés */
    public static function prune(?string $dir = null): int
    {
        $days = (int) Config::get('logging.days', 14);

        if ($days <= 0) {
            return 0;
        }

        $dir ??= base_path('storage/logs');
        $oldestKept = date('Y-m-d', time() - ($days - 1) * 86400);
        $deleted = 0;

        foreach (glob("$dir/*.log") ?: [] as $file) {
            $date = basename($file, '.log');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $date < $oldestKept && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private static function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE, $key) === 1) {
                $context[$key] = '[masqué]';
            } elseif (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
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
