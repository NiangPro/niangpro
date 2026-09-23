<?php

namespace Niang\Core\Console;

/**
 * Exécute, dans un projet tout juste créé, les commandes `niang` déclarées par la clé `setup` du
 * theme.json (ex : migrate puis db:seed pour une boutique, qui a besoin de sa base et de son compte
 * administrateur de test dès le premier lancement). Appelée par `niang new` (Commander) et par le
 * hook `post-create-project-cmd` (ComposerHooks).
 *
 * Chaque commande tourne dans un process PHP à part, avec le bin/niang du NOUVEAU projet (donc sa
 * configuration et sa base), sans passer par un shell. La première qui échoue arrête la suite : les
 * commandes restantes seront simplement suggérées à l'utilisateur.
 */
class ThemeSetup
{
    public function __construct(private string $projectRoot)
    {
    }

    /**
     * @param list<string> $commands ex. ['migrate', 'db:seed']
     * @param \Closure(string): void $write reçoit la sortie de chaque commande
     * @return list<string> les commandes réussies, dans l'ordre
     */
    public function run(array $commands, \Closure $write): array
    {
        $done = [];

        foreach ($commands as $command) {
            [$code, $output] = $this->execute($command);

            if (trim($output) !== '') {
                $write(rtrim($output) . "\n");
            }

            if ($code !== 0) {
                $write("La commande « niang $command » a échoué (code $code) : lancez-la vous-même une fois le problème réglé.\n");
                break;
            }

            $done[] = $command;
        }

        return $done;
    }

    /** @return array{0: int, 1: string} */
    private function execute(string $command): array
    {
        $process = proc_open(
            [PHP_BINARY, $this->projectRoot . '/bin/niang', ...explode(' ', $command)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->projectRoot
        );

        if (!is_resource($process)) {
            return [1, 'Impossible de lancer ' . PHP_BINARY . "\n"];
        }

        $output = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);

        return [proc_close($process), $output];
    }
}
