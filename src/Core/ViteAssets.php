<?php

namespace Niang\Core;

/**
 * Résout l'URL réelle d'un asset construit par Vite, sans rien nécessiter d'installé pour
 * fonctionner : NiangPro n'embarque aucun outillage de build. L'utilisateur configure Vite dans
 * son projet comme il le ferait pour n'importe quel backend (voir README, section Vite) ; cette
 * classe se contente de lire ce que Vite a produit.
 *
 * Deux modes, détectés par la présence d'un fichier — jamais une configuration à renseigner :
 *  - public/hot : présent pendant `npm run dev` (le plugin Vite y écrit l'URL de son serveur de
 *    dev) — bascule dessus pour profiter du rechargement à chaud.
 *  - public/build/manifest.json : présent après `npm run build` — résout l'URL hashée réelle de
 *    chaque entrée, pour ne jamais casser un lien vers un asset renommé à chaque build.
 * Sans l'un ou l'autre, l'appel échoue avec un message clair plutôt qu'une URL cassée silencieuse.
 */
class ViteAssets
{
    private const DEFAULT_DEV_SERVER = 'http://localhost:5173';

    public function __construct(private string $publicPath)
    {
    }

    public function asset(string $entry): string
    {
        $hotFile = "$this->publicPath/hot";

        if (is_file($hotFile)) {
            $devServer = trim((string) file_get_contents($hotFile)) ?: self::DEFAULT_DEV_SERVER;

            return rtrim($devServer, '/') . '/' . ltrim($entry, '/');
        }

        $manifestPath = "$this->publicPath/build/manifest.json";

        if (!is_file($manifestPath)) {
            throw new \RuntimeException(
                'Aucun manifest Vite (public/build/manifest.json) ni serveur de dev (public/hot) : '
                . 'lancez `npm run build`, ou `npm run dev` pendant le développement.'
            );
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (!is_array($manifest) || !isset($manifest[$entry]['file'])) {
            throw new \RuntimeException("Entrée Vite introuvable dans le manifest : « $entry ».");
        }

        return '/build/' . $manifest[$entry]['file'];
    }
}
