<?php

namespace Niang\Core\Console;

/**
 * Copie le dossier de thème d'un paquet Composer tiers (déjà installé dans vendor/) vers
 * resources/scaffold/themes/<slug>/, où ProjectScaffolder le découvre tel quel — un thème est
 * « juste un dossier », donc ProjectScaffolder n'a besoin d'aucune modification pour en profiter.
 *
 * Convention : le composer.json du paquet déclare `extra.niangpro-theme`, un chemin relatif vers
 * un dossier qui reproduit l'arborescence d'un thème (app/, config/, routes/web.php...), déjà
 * nommé comme le slug voulu (son basename devient le slug : niang theme:add ne demande jamais de
 * nom séparément). `niang theme:add <vendor/paquet>` (Commander) lance `composer require --dev`
 * puis appelle install() — logique pure et testable séparée de l'I/O, sur le modèle de
 * ProjectScaffolder et HealthCheck : cette classe ne lance elle-même aucun process composer.
 */
class ThemePackageInstaller
{
    public function __construct(private string $projectRoot)
    {
    }

    /**
     * @return string le slug du thème installé
     *
     * @throws \InvalidArgumentException si le paquet n'est pas installé dans vendor/, ne déclare
     *                                   pas extra.niangpro-theme, ou pointe vers un dossier absent
     */
    public function install(string $package): string
    {
        $manifestPath = "$this->projectRoot/vendor/$package/composer.json";

        if (!is_file($manifestPath)) {
            throw new \InvalidArgumentException(
                "Paquet introuvable : vendor/$package/composer.json (composer require --dev $package a-t-il réussi ?)"
            );
        }

        $manifest = $this->readManifest($manifestPath);
        $relative = $manifest['extra']['niangpro-theme'] ?? null;

        if (!is_string($relative) || trim($relative, '/') === '') {
            throw new \InvalidArgumentException("$package ne déclare pas de clé extra.niangpro-theme dans son composer.json.");
        }

        $relative = trim($relative, '/');
        $source = "$this->projectRoot/vendor/$package/$relative";

        if (!is_dir($source)) {
            throw new \InvalidArgumentException("Dossier de thème introuvable : vendor/$package/$relative");
        }

        $slug = basename($source);

        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug) || $slug === ProjectScaffolder::DEFAULT_TYPE) {
            throw new \InvalidArgumentException(
                "Slug de thème invalide : « $slug » (dossier vendor/$package/$relative) — attendu : lettres "
                . 'minuscules, chiffres, tirets, différent de « ' . ProjectScaffolder::DEFAULT_TYPE . ' ».'
            );
        }

        $this->copyDirectory($source, "$this->projectRoot/resources/scaffold/themes/$slug");

        return $slug;
    }

    /** @return array<string, mixed> */
    private function readManifest(string $path): array
    {
        try {
            $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException("$path est invalide : " . $e->getMessage(), 0, $e);
        }

        return is_array($manifest) ? $manifest : [];
    }

    private function copyDirectory(string $source, string $destination): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);
            $target = "$destination/$relative";

            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                    throw new \RuntimeException("Impossible de créer le dossier $target");
                }

                continue;
            }

            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true) && !is_dir(dirname($target))) {
                throw new \RuntimeException('Impossible de créer le dossier ' . dirname($target));
            }

            if (!copy($item->getPathname(), $target)) {
                throw new \RuntimeException("Impossible de copier $relative vers $target");
            }
        }
    }
}
