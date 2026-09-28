<?php

/**
 * Vérifie qu'aucune nouveauté du framework n'a été ajoutée sans laisser de trace dans la
 * documentation (../niangpro-docs, un projet NiangPro séparé — voir CONTRIBUTING ou le commit qui
 * introduit ce fichier). Trois sources de vérité, prises directement dans le code, jamais retapées :
 *
 *   - chaque commande listée par `Commander::help()` doit être mentionnée sur la page CLI ;
 *   - chaque fonction globale (packages/<nom>/src/helpers.php) doit être mentionnée quelque part dans la doc ;
 *   - chaque règle de validation enregistrée dans Validator::passes() doit y avoir un exemple.
 *
 * Ce script ne fait PAS partie de `composer test` : il tourne comme job CI séparé (ou
 * `composer docs:check`), volontairement rouge tant que la documentation n'a pas rattrapé le
 * framework — voir le sommaire de ../niangpro-docs pour l'état actuel des pages écrites.
 *
 * Une source introuvable (fichier déplacé, extraction vide) arrête le script en erreur : sans ça,
 * il concluait « rien à signaler » en ne vérifiant plus rien (arrivé au passage en paquets, v2.0).
 *
 * Usage : php docs-check.php [chemin-vers-niangpro-docs]
 */

$repoRoot = __DIR__;
$docsRoot = $argv[1] ?? getenv('NIANGPRO_DOCS_PATH') ?: dirname($repoRoot) . '/niangpro-docs';

if (!is_dir($docsRoot)) {
    fwrite(STDERR, "Projet de documentation introuvable : $docsRoot\n");
    fwrite(STDERR, "Précisez son chemin : php docs-check.php /chemin/vers/niangpro-docs\n");
    exit(2);
}

// Le texte des pages elles-mêmes, plus les données qu'elles rendent dynamiquement (ex. la page
// Référence CLI ne retape aucune commande : elle lit resources/data/cli-reference.php, généré
// depuis Commander::help() — sans ce second dossier, ce script ne verrait aucune commande listée
// là et les signalerait toutes comme manquantes, à tort).
$haystack = collectDocsText($docsRoot . '/resources/views/docs') . collectDocsText($docsRoot . '/resources/data');

$failures = [];

// ---------- Commandes CLI ----------

$cliOutput = shell_exec('php ' . escapeshellarg($repoRoot . '/bin/niang') . ' 2>&1');
$commands = extractCommandNames((string) $cliOutput);

if ($commands === []) {
    abortCheck('aucune commande lue dans la sortie de bin/niang');
}

foreach ($commands as $command) {
    if (!containsToken($haystack, $command)) {
        $failures[] = "commande CLI « $command » absente de la doc (page Référence CLI attendue)";
    }
}

// ---------- Helpers globaux ----------

$helperFiles = glob($repoRoot . '/packages/*/src/helpers.php') ?: [];

if ($helperFiles === []) {
    abortCheck('aucun fichier packages/*/src/helpers.php');
}

$helperNames = array_values(array_unique(array_merge(...array_map('extractHelperNames', $helperFiles))));

foreach ($helperNames as $helper) {
    if (!containsToken($haystack, $helper)) {
        $failures[] = "helper « $helper() » absent de la doc (page Vues attendue)";
    }
}

// ---------- Règles de validation ----------

$rules = extractValidationRules($repoRoot . '/packages/http/src/Validation/Validator.php');

foreach ($rules as $rule) {
    if (!containsToken($haystack, $rule)) {
        $failures[] = "règle de validation « $rule » absente de la doc (page Validation attendue)";
    }
}

// ---------- Rapport ----------

$total = count($commands) + count($helperNames) + count($rules);
$missing = count($failures);

printf("docs-check : %d/%d éléments couverts par %s\n", $total - $missing, $total, $docsRoot);

if ($failures === []) {
    echo "OK — rien à signaler.\n";
    exit(0);
}

echo "\nManquants :\n";
foreach ($failures as $failure) {
    echo "  - $failure\n";
}

exit(1);

// ---------- Fonctions ----------

/** Une source de vérité introuvable : erreur (code 2), jamais un « tout va bien » silencieux. */
function abortCheck(string $reason): never
{
    fwrite(STDERR, "docs-check : $reason — le script ne peut plus rien vérifier, corrigez ses chemins.\n");
    exit(2);
}

/** Concatène le texte source de toutes les pages de doc déjà écrites (récursif). */
function collectDocsText(string $dir): string
{
    if (!is_dir($dir)) {
        return '';
    }

    $text = '';
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($items as $item) {
        if ($item->getExtension() === 'php') {
            $text .= "\n" . file_get_contents($item->getPathname());
        }
    }

    return $text;
}

/** Un token est « couvert » s'il apparaît tel quel dans la doc, insensible à la casse. */
function containsToken(string $haystack, string $token): bool
{
    return stripos($haystack, $token) !== false;
}

/** @return list<string> */
function extractCommandNames(string $helpOutput): array
{
    $commands = [];

    foreach (explode("\n", $helpOutput) as $line) {
        // "  make:controller <Nom>    Génère..." -> "make:controller"
        if (preg_match('/^\s{2}([a-z][a-z0-9:_-]*)/', $line, $m) === 1) {
            $commands[] = $m[1];
        }
    }

    return array_values(array_unique($commands));
}

/** @return list<string> */
function extractHelperNames(string $helpersFile): array
{
    $source = is_file($helpersFile) ? (string) file_get_contents($helpersFile) : abortCheck("fichier introuvable : $helpersFile");
    preg_match_all('/^\s{4}function\s+([a-z_][a-zA-Z0-9_]*)\s*\(/m', $source, $matches);

    return $matches[1] !== [] ? array_values(array_unique($matches[1])) : abortCheck("aucune fonction lue dans $helpersFile");
}

/**
 * Extrait les clés du match($name) de Validator::applyRule() — la liste des règles réellement
 * implémentées, pas une liste retapée à la main qui pourrait diverger. 'nullable' est un modificateur
 * géré à part par runRules() (jamais dans ce match) : il n'a pas sa place dans cette liste.
 *
 * @return list<string>
 */
function extractValidationRules(string $validatorFile): array
{
    $source = is_file($validatorFile) ? (string) file_get_contents($validatorFile) : abortCheck("fichier introuvable : $validatorFile");

    if (!preg_match('/private function applyRule\(.*?\n    \}\n/s', $source, $methodMatch)) {
        abortCheck("méthode applyRule() introuvable dans $validatorFile");
    }

    preg_match_all("/^\s*'([a-z_]+)' => /m", $methodMatch[0], $matches);

    return $matches[1] !== [] ? array_values(array_unique($matches[1])) : abortCheck("aucune règle lue dans $validatorFile");
}
