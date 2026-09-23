<?php

namespace Niang\Core\Console;

/**
 * Installe un « thème de site » (vitrine, boutique, blog...) dans un projet tout juste créé.
 *
 * Logique pure, sans lecture de STDIN ni affichage : la question posée à l'utilisateur vit dans
 * SiteTypePrompt, et l'appel est fait à la fois par `niang new` (Commander) et par le hook
 * `post-create-project-cmd` de Composer (ComposerHooks) — même code, deux façades, sur le modèle de
 * Niang\Core\HealthCheck.
 *
 * Un thème est un dossier de resources/scaffold/themes/<slug>/ qui reproduit l'arborescence d'un
 * projet (app/, config/, database/, public/, resources/views/, routes/web.php, tests/...) plus un
 * theme.json optionnel. Ajouter un thème = ajouter un dossier : il apparaît de lui-même dans
 * catalog(), sans rien enregistrer nulle part. resources/scaffold/shared/ suit la même
 * arborescence et fournit ce que tous les thèmes ont en commun (design system, composants).
 *
 * Un module (resources/scaffold/modules/<nom>/, même arborescence) est une brique réutilisée par
 * plusieurs thèmes sans être elle-même un type de site : l'espace d'administration, par exemple, est
 * commun à la boutique et au blog. Il est copié après shared/ et avant le thème, qui peut donc en
 * remplacer n'importe quel fichier.
 *
 * theme.json (tous les champs sont facultatifs) :
 *   label       libellé affiché dans le catalogue (défaut : le slug)
 *   order       position dans le catalogue, croissante (défaut : 100)
 *   modules     modules de resources/scaffold/modules/ à installer avec le thème
 *   remove      chemins du projet cible à supprimer avant la copie (démo remplacée par le thème)
 *   setup       commandes niang lancées automatiquement à la création du projet (ex : migrate,
 *               db:seed) — voir ThemeSetup ; celles qui réussissent disparaissent de next_steps
 *   next_steps  commandes à suggérer à l'utilisateur une fois le thème installé
 *   notes       informations à afficher après ces commandes (ex : identifiants du compte admin de test)
 */
class ProjectScaffolder
{
    /** Type par défaut : le squelette de démonstration tel quel, sans thème installé. */
    public const DEFAULT_TYPE = 'minimal';

    private const MINIMAL_LABEL = 'Minimal — squelette de démonstration';

    private const MANIFEST = 'theme.json';

    private string $scaffoldPath;

    public function __construct(?string $scaffoldPath = null)
    {
        $this->scaffoldPath = rtrim($scaffoldPath ?? dirname(__DIR__, 3) . '/resources/scaffold', '/');
    }

    /**
     * slug => libellé, dans l'ordre d'affichage ; « minimal » est toujours en dernier.
     *
     * @return array<string, string>
     */
    public function catalog(): array
    {
        $themes = [];

        foreach (glob($this->scaffoldPath . '/themes/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);

            if ($slug === self::DEFAULT_TYPE || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug)) {
                continue;
            }

            $manifest = $this->manifest($dir);
            $themes[$slug] = [
                'label' => (string) ($manifest['label'] ?? ucfirst($slug)),
                'order' => (int) ($manifest['order'] ?? 100),
            ];
        }

        uksort($themes, static fn (string $a, string $b) => [$themes[$a]['order'], $a] <=> [$themes[$b]['order'], $b]);

        $catalog = array_map(static fn (array $theme) => $theme['label'], $themes);
        $catalog[self::DEFAULT_TYPE] = self::MINIMAL_LABEL;

        return $catalog;
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->catalog());
    }

    /**
     * Remplace (et non fusionne) les vues, routes et assets de démonstration du projet par ceux du
     * thème : l'installation n'a lieu qu'une fois, à la création du projet, avant que l'utilisateur
     * n'ait touché à quoi que ce soit — une fusion serait plus fragile pour aucun bénéfice.
     * Le type « minimal » ne fait rien : c'est le squelette tel qu'il existe déjà.
     *
     * @throws \InvalidArgumentException si le type est inconnu ou si le dossier cible n'existe pas
     */
    public function install(string $type, string $targetPath): void
    {
        if (!$this->has($type)) {
            throw new \InvalidArgumentException(sprintf(
                'Type de site inconnu : « %s » (disponibles : %s).',
                $type,
                implode(', ', array_keys($this->catalog()))
            ));
        }

        if ($type === self::DEFAULT_TYPE) {
            return;
        }

        if (!is_dir($targetPath)) {
            throw new \InvalidArgumentException("Dossier cible introuvable : $targetPath");
        }

        $sources = [
            $this->scaffoldPath . '/shared',
            ...array_map($this->moduleDir(...), $this->modules($type)),
            $this->themeDir($type),
        ];

        foreach ($sources as $source) {
            foreach ($this->manifest($source)['remove'] ?? [] as $relative) {
                $this->remove($targetPath, (string) $relative);
            }
        }

        foreach ($sources as $source) {
            $this->overlay($source, $targetPath);
        }
    }

    /**
     * Commandes à suggérer une fois le thème installé (ex : migrer et alimenter la base pour une boutique).
     *
     * @return list<string>
     */
    public function nextSteps(string $type): array
    {
        if (!$this->has($type) || $type === self::DEFAULT_TYPE) {
            return [];
        }

        return array_values(array_map('strval', $this->manifest($this->themeDir($type))['next_steps'] ?? []));
    }

    /**
     * Commandes `niang` à exécuter automatiquement dans le projet créé (clé `setup`).
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException si une commande n'a pas la forme « nom[:action] [argument] »
     */
    public function setup(string $type): array
    {
        if (!$this->has($type) || $type === self::DEFAULT_TYPE) {
            return [];
        }

        $commands = array_values(array_map('strval', $this->manifest($this->themeDir($type))['setup'] ?? []));

        foreach ($commands as $command) {
            if (!preg_match('/^[a-z][a-z-]*(:[a-z-]+)?( [A-Za-z0-9_-]+)?$/', $command)) {
                throw new \InvalidArgumentException("Commande de setup invalide dans le theme.json de « $type » : « $command ».");
            }
        }

        return $commands;
    }

    /**
     * nextSteps() sans les commandes déjà exécutées par setup (« ./bin/niang migrate » disparaît
     * si « migrate » a réussi).
     *
     * @param list<string> $done commandes de setup réussies
     * @return list<string>
     */
    public function remainingSteps(string $type, array $done): array
    {
        $doneSteps = array_map(static fn (string $command): string => "./bin/niang $command", $done);

        return array_values(array_diff($this->nextSteps($type), $doneSteps));
    }

    /**
     * Informations à afficher une fois le thème installé, après les commandes de nextSteps().
     *
     * @return list<string>
     */
    public function notes(string $type): array
    {
        if (!$this->has($type) || $type === self::DEFAULT_TYPE) {
            return [];
        }

        return array_values(array_map('strval', $this->manifest($this->themeDir($type))['notes'] ?? []));
    }

    /**
     * @return list<string>
     *
     * @throws \InvalidArgumentException si un module déclaré n'existe pas
     */
    private function modules(string $type): array
    {
        $modules = array_values(array_map('strval', $this->manifest($this->themeDir($type))['modules'] ?? []));

        foreach ($modules as $module) {
            if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/', $module) || !is_dir($this->moduleDir($module))) {
                throw new \InvalidArgumentException("Module inconnu dans le theme.json de « $type » : « $module ».");
            }
        }

        return $modules;
    }

    private function moduleDir(string $module): string
    {
        return $this->scaffoldPath . '/modules/' . $module;
    }

    private function themeDir(string $type): string
    {
        return $this->scaffoldPath . '/themes/' . $type;
    }

    /** @return array<string, mixed> */
    private function manifest(string $dir): array
    {
        $file = $dir . '/' . self::MANIFEST;

        if (!is_file($file)) {
            return [];
        }

        try {
            $manifest = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException("$file est invalide : " . $e->getMessage(), 0, $e);
        }

        return is_array($manifest) ? $manifest : [];
    }

    /** Copie l'arborescence de $source par-dessus $target, sans jamais copier le manifeste lui-même. */
    private function overlay(string $source, string $target): void
    {
        if (!is_dir($source)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);

            if ($relative === self::MANIFEST || basename($relative) === '.DS_Store') {
                continue;
            }

            $destination = $target . '/' . $relative;

            if ($item->isDir()) {
                $this->ensureDirectory($destination);
                continue;
            }

            $this->ensureDirectory(dirname($destination));

            if (!copy($item->getPathname(), $destination)) {
                throw new \RuntimeException("Impossible de copier $relative vers $destination");
            }
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Impossible de créer le dossier $path");
        }
    }

    /** Supprime un fichier ou un dossier du projet cible ; sans effet s'il n'existe pas. */
    private function remove(string $target, string $relative): void
    {
        $segments = explode('/', $relative);

        if ($relative === '' || str_starts_with($relative, '/') || in_array('..', $segments, true) || in_array('.', $segments, true)) {
            throw new \InvalidArgumentException("Chemin à supprimer invalide dans theme.json : « $relative »");
        }

        $path = $target . '/' . $relative;

        if (is_dir($path) && !is_link($path)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }

            rmdir($path);
        } elseif (file_exists($path) || is_link($path)) {
            unlink($path);
        }
    }
}
