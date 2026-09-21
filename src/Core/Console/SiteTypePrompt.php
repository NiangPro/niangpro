<?php

namespace Niang\Core\Console;

/**
 * Détermine quel type de site installer, sans jamais bloquer un script : un choix explicite
 * (`--type=blog` ou la variable NIANG_SITE_TYPE) gagne toujours, sinon on ne pose la question que si
 * quelqu'un peut y répondre, et à défaut on garde le squelette minimal (comportement historique).
 *
 * Aucune lecture de STDIN ni affichage direct ici : la lecture ($ask) et l'affichage ($write) sont
 * injectés, pour que `niang new` (STDIN/echo) et le hook Composer (son propre IOInterface, qui
 * respecte `--no-interaction`) partagent exactement la même logique.
 */
class SiteTypePrompt
{
    public const ENV_VARIABLE = 'NIANG_SITE_TYPE';

    private const MAX_ATTEMPTS = 3;

    public function __construct(private ProjectScaffolder $scaffolder)
    {
    }

    /**
     * @param string|null                $requested   valeur de --type, ou null si le flag est absent
     * @param bool                       $interactive true si un humain peut répondre (TTY, pas de --no-interaction)
     * @param callable(string): ?string  $ask         affiche la question et retourne la réponse (null en fin de flux)
     * @param callable(string): void     $write       affiche un texte tel quel
     * @return string un slug présent dans ProjectScaffolder::catalog()
     *
     * @throws \InvalidArgumentException si un type explicitement demandé n'existe pas
     */
    public function resolve(?string $requested, bool $interactive, callable $ask, callable $write): string
    {
        $requested = $requested !== null && $requested !== '' ? $requested : (getenv(self::ENV_VARIABLE) ?: null);

        if ($requested !== null) {
            $slug = strtolower(trim($requested));

            if (!$this->scaffolder->has($slug)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type de site inconnu : « %s » (disponibles : %s).',
                    $requested,
                    implode(', ', array_keys($this->scaffolder->catalog()))
                ));
            }

            return $slug;
        }

        return $interactive ? $this->prompt($ask, $write) : ProjectScaffolder::DEFAULT_TYPE;
    }

    /** STDIN est-il un terminal ? Faux dans une CI, un pipe ou un `docker run` sans -t. */
    public static function stdinIsInteractive(): bool
    {
        return defined('STDIN') && function_exists('stream_isatty') && @stream_isatty(STDIN);
    }

    /**
     * @param callable(string): ?string $ask
     * @param callable(string): void    $write
     */
    private function prompt(callable $ask, callable $write): string
    {
        $catalog = $this->scaffolder->catalog();
        $slugs = array_keys($catalog);
        $default = ProjectScaffolder::DEFAULT_TYPE;
        $defaultNumber = (int) array_search($default, $slugs, true) + 1;

        $write("\nQuel type de site souhaitez-vous construire ?\n\n");

        foreach ($slugs as $index => $slug) {
            $write(sprintf(
                "  %d) %-10s %s%s\n",
                $index + 1,
                $slug,
                $catalog[$slug],
                $slug === $default ? ' (défaut)' : ''
            ));
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $answer = $ask("\nVotre choix [$defaultNumber] : ");

            if ($answer === null || trim($answer) === '') {
                return $default;
            }

            $answer = strtolower(trim($answer));

            if (ctype_digit($answer) && isset($slugs[(int) $answer - 1])) {
                return $slugs[(int) $answer - 1];
            }

            if (in_array($answer, $slugs, true)) {
                return $answer;
            }

            $write("Choix invalide : « $answer ».\n");
        }

        $write("Aucun choix valide : le squelette minimal est conservé.\n");

        return $default;
    }
}
