<?php

namespace Niang\Core\Console;

use Niang\Core\AppKey;

/**
 * Hook `post-create-project-cmd` de composer.json : `composer create-project niangpro/framework
 * mon-app` se contente de copier les fichiers du paquet (il n'exécute jamais `niang new`), donc
 * c'est ici que la question « quel type de site ? » est posée sur ce chemin d'installation.
 *
 * Composer n'est pas une dépendance de ce projet : l'événement reçu (Composer\Script\Event) est donc
 * typé `object` — seuls getIO() puis isInteractive()/ask()/write()/writeError() sont utilisés.
 */
class ComposerHooks
{
    /**
     * Point d'entrée appelé par Composer. Le chargeur de classes du projet est déjà en place à ce
     * stade (les scripts post-create-project s'exécutent après l'installation des dépendances).
     *
     * @param object $event un Composer\Script\Event
     */
    public static function postCreateProject(object $event): void
    {
        self::handle($event, dirname(__DIR__, 3), SiteTypePrompt::stdinIsInteractive(), new ProjectScaffolder());
    }

    /**
     * Séparée de postCreateProject() pour rester testable : racine du projet, état du TTY et
     * scaffolder sont injectés au lieu d'être devinés.
     *
     * @param object $event un Composer\Script\Event
     *
     * @internal
     */
    public static function handle(object $event, string $projectRoot, bool $stdinIsTty, ProjectScaffolder $scaffolder): void
    {
        $io = $event->getIO();

        self::createEnvFile($projectRoot, $io);

        try {
            $type = (new SiteTypePrompt($scaffolder))->resolve(
                null,
                $io->isInteractive() && $stdinIsTty,
                static function (string $question) use ($io): ?string {
                    try {
                        $answer = $io->ask(self::escape($question));
                    } catch (\RuntimeException) {
                        // Composer lève « Aborted » sur une fin de saisie (Ctrl+D) : on garde le squelette
                        // minimal plutôt que de faire échouer un create-project déjà presque terminé.
                        return null;
                    }

                    return $answer === null ? null : (string) $answer;
                },
                static function (string $text) use ($io): void {
                    $io->write(self::escape($text), false);
                }
            );
        } catch (\InvalidArgumentException $e) {
            // Le projet est déjà en place : mieux vaut le garder tel quel qu'échouer après coup
            // sur une faute de frappe dans NIANG_SITE_TYPE.
            $io->writeError('<warning>' . self::escape($e->getMessage()) . ' Squelette minimal conservé.</warning>');

            return;
        }

        $scaffolder->install($type, $projectRoot);

        if ($type === ProjectScaffolder::DEFAULT_TYPE) {
            return;
        }

        $io->write('');
        $io->write('<info>Thème « ' . self::escape($scaffolder->catalog()[$type]) . ' » installé.</info>');

        $setup = $scaffolder->setup($type);
        $done = [];

        if ($setup) {
            $io->write('Préparation du projet (' . self::escape(implode(', ', $setup)) . ')...');
            $done = (new ThemeSetup($projectRoot))->run($setup, static function (string $text) use ($io): void {
                $io->write(self::escape(rtrim($text, "\n")));
            });
        }

        foreach ($scaffolder->remainingSteps($type, $done) as $step) {
            $io->write('  ' . self::escape($step));
        }

        $notes = $scaffolder->notes($type);

        if ($notes) {
            $io->write('');

            foreach ($notes as $note) {
                $io->write(self::escape($note));
            }
        }
    }

    /**
     * .env à partir de .env.example, avec une APP_KEY propre à ce projet — sans quoi les URLs signées
     * (réinitialisation de mot de passe), les jetons API et les cookies n'auraient aucun secret.
     * Un .env déjà présent n'est jamais écrasé.
     */
    private static function createEnvFile(string $projectRoot, object $io): void
    {
        $env = $projectRoot . '/.env';
        $example = $projectRoot . '/.env.example';

        if (file_exists($env) || !file_exists($example)) {
            return;
        }

        copy($example, $env);
        AppKey::writeTo($env);
        $io->write('<info>.env créé, avec une APP_KEY propre à ce projet.</info>');
    }

    /** Un « < » dans un libellé de thème ne doit pas être interprété comme une balise de mise en forme Composer. */
    private static function escape(string $text): string
    {
        return str_replace('<', '\\<', $text);
    }
}
