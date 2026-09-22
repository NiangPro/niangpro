# Changelog

Toutes les évolutions notables de NiangPro sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), le versionnage suit
[Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

## [1.5.0] — 2026-09-22

### Fixed

- **Compatibilité multi-OS (Windows, macOS, Linux)** (P0 #15, NiangPro 2.0 — vingt-et-unième
  jalon ; CI/CD de la roadmap, §58) : audit du code (recherche d'appels shell, de symlinks, de
  chemins codés en dur) avant toute correction — pas de suppositions.
  - **Vrai bug trouvé : `DB::createConnection()` ne reconnaissait un chemin `DB_DATABASE`
    absolu qu'à la convention Unix** (`str_starts_with($path, '/')`). Un chemin Windows absolu
    (`C:\...`, `C:/...`, UNC `\\serveur\partage`) aurait été pris pour un chemin relatif et
    préfixé de `base_path()`, cassant la connexion SQLite. `DB::isAbsolutePath()` reconnaît
    désormais les trois conventions (6 tests, `tests/Unit/Database/DBIsAbsolutePathTest.php`,
    testée par réflexion — méthode privée, sur le modèle de `Psr7Bridge::requireClass`).
  - **Aucun `.gitattributes`** : sans lui, un checkout Windows avec `core.autocrlf=true`
    (réglage très courant) convertit `bin/niang` en CRLF, et son shebang
    (`#!/usr/bin/env php`) échoue sous WSL/Git Bash (`env: 'php\r': No such file or directory`).
    Fins de ligne forcées en LF pour le texte (`* text=auto eol=lf`), CRLF explicite pour les
    `.bat` (convention `cmd.exe`).
  - **`bin/niang.bat`** ajouté : sous CMD/PowerShell natif (hors WSL/Git Bash, où
    `./bin/niang` fonctionne déjà tel quel), le shebang n'est pas interprété — ce wrapper relaie
    vers `php bin/niang`, copié automatiquement par `niang new`/`ProjectScaffolder` comme
    n'importe quel autre fichier de `bin/` (aucune modification de code nécessaire de ce côté).
  - **`StagedProject::linkVendor()` (harnais de test, `ThemeInstallationTest`) utilisait
    `symlink()` sans repli.** Sur Windows sans privilège administrateur ni mode développeur
    activé, `symlink()` échoue silencieusement (avertissement PHP) — repli sur une copie
    complète du paquet concerné dans ce cas (plus lent, mais ne dépend d'aucune configuration
    système préalable). `StagedProject::php()`/`phpunit()` transmettent aussi désormais
    `SystemRoot`/`windir` au sous-process : leur absence peut faire échouer PHP au démarrage
    sur Windows avec un environnement aussi restreint (fonctions socket notamment) — sans
    effet sur Unix, où ils sont simplement absents de `getenv()`.
  - **Vérification** : `composer test && composer lint && composer analyse` déjà vérifiés en CI
    sur Linux (`ubuntu-latest`) pour chaque version PHP supportée (8.1 à 8.4) — nouveau job
    `cross-platform` qui les vérifie aussi réellement sur `windows-latest` et `macos-latest`
    (PHP 8.4), plutôt que de supposer la portabilité. **Honnêteté sur ce qui est mesuré** :
    aucune machine Windows n'était disponible pour vérifier ces correctifs en local avant de
    les pousser — la CI GitHub Actions est ici la première vérification réelle sur cet OS,
    pas une relecture de code seule. Le smoke test de packaging (`packaging-smoke`, seizième
    jalon) reste Linux uniquement : adapter ses scripts bash (`curl`, `pkill`, jobs en arrière-plan)
    à PowerShell est un chantier séparé, hors du périmètre retenu ici.

## [1.4.0] — 2026-09-22

### Added

- **Intégration de frameworks frontend (Alpine.js, htmx, Vue/React via build externe)** (P0 #15,
  NiangPro 2.0 — vingtième jalon ; Assets de la roadmap, §45) : le cœur ne bundle rien ni
  n'impose rien (« PHP reste PHP ») — ce jalon documente et facilite, sans rien intégrer au noyau.
  - **`json_for_html(mixed $data): string`** (`src/helpers.php`) passe des données PHP à du JS
    en sécurité. **Un vrai bug de sécurité trouvé et corrigé en écrivant les tests, pas seulement
    suivi tel quel depuis la spécification** : `json_encode()` avec uniquement
    `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP` protège le *contenu* JSON, mais pas
    les guillemets *structurels* qu'il produit lui-même (`{"clé":"valeur"}`) — dès que `$data`
    est un tableau/objet (le cas d'usage documenté : `data-props="..."`), ces guillemets
    structurels restent des `"` littéraux et cassent l'attribut au premier caractère
    rencontré. Vérifié avec un vrai Chromium (Playwright) : `<div data-props="{"a":"b"}">`
    devient bien un second attribut HTML arbitraire dans le DOM, pas juste une inquiétude
    théorique. Fix : une seconde couche `htmlspecialchars()` sur le résultat JSON — le navigateur
    décode les entités HTML d'un attribut à la lecture (`dataset`, `getAttribute`), donc
    `JSON.parse()` reçoit toujours le JSON d'origine intact, re-vérifié avec Chromium après
    correction. 8 tests (`tests/Unit/HelpersJsonForHtmlTest.php`) : `</script>`, guillemets
    simples et doubles, esperluettes, Unicode, un objet combinant guillemet et balise fermante
    dans la même valeur — chacun via le cycle complet (construire le HTML, le faire reparser,
    `json_decode()` le résultat), jamais `json_decode()` directement sur la sortie du helper
    (qui n'est plus du JSON brut, justement).
  - **`vite_asset(string $entry): string`** (`src/helpers.php`, délègue à `Niang\Core\ViteAssets`,
    logique pure et testable) : lit `public/build/manifest.json` s'il existe (résout l'URL
    hashée réelle, pour ne jamais casser un lien à chaque build Vite), bascule sur le serveur de
    dev (`public/hot`, ou `http://localhost:5173` par défaut) s'il est présent — sinon échoue
    avec un message clair plutôt qu'un lien cassé silencieux. N'exige rien d'installé pour que le
    reste du framework fonctionne : seul un appel explicite suppose Vite configuré.
  - Documentation (README, section « Intégration de frameworks frontend ») avec un exemple
    concret pour chacun : Alpine.js et htmx auto-hébergés (téléchargés dans `public/js/`, aucune
    étape de build, cohérent avec « zéro CDN ») ; Vue/React via Vite configuré côté projet comme
    pour n'importe quel backend, assets construits dans `public/build/` et servis comme des
    fichiers statiques ordinaires.
  - 6 tests supplémentaires (`tests/Unit/ViteAssetsTest.php`) : résolution depuis le manifest,
    bascule sur le serveur de dev, `public/hot` prioritaire sur le manifest, fichier `public/hot`
    vide replié sur l'adresse par défaut, erreur claire si aucun des deux fichiers n'existe, erreur
    claire si l'entrée demandée est absente du manifest.

## [1.3.0] — 2026-09-22

### Added

- **Interopérabilité PSR-7 / PSR-15** (P0 #15, NiangPro 2.0 — dix-neuvième jalon ; PSR de la
  roadmap, §10) : que quelqu'un puisse brancher un middleware PSR-15 tiers (une lib de sécurité,
  un cache HTTP existant sur Packagist...) sans réécrire NiangPro autour de PSR-7.
  `Request`/`Response` restent les objets simples de NiangPro partout — aucun remplacement par
  des objets PSR-7 immuables, ça casserait l'API actuelle pour un bénéfice qui ne le justifie pas.
  - **`psr/http-message` et `psr/http-server-middleware`** (interfaces seules, comme
    `psr/container`/`psr/log` déjà présents) en `require` — zéro poids d'implémentation.
    **`nyholm/psr7`** en `suggest` (jamais en `require`) : psr/http-message ne fournit que des
    interfaces, une implémentation concrète est nécessaire pour en INSTANCIER une (contrairement
    à la simple lecture d'une interface déjà fournie par un tiers). Mise en `require-dev`
    (jamais en production, comme phpunit/phpstan) pour que ce jalon puisse être testé avec une
    vraie conversion plutôt que seulement le message d'erreur en son absence.
  - **`Niang\Core\Http\Psr7Bridge`** : `toPsrRequest()`/`toPsrResponse()` doivent construire un
    objet concret à partir de rien — vérifient la présence de l'implémentation (`class_exists`)
    et lèvent une `RuntimeException` explicite sinon (« installez nyholm/psr7... ») plutôt qu'une
    erreur PHP opaque sur une classe manquante. `fromPsrResponse()`, à l'inverse, n'a besoin
    d'aucune implémentation concrète : `ResponseInterface` expose déjà tout ce qu'il faut lire —
    prouvé par un test qui lui passe un stub maison, sans nyholm. `Response::getHeaders()` ajouté
    (mineur, non cassant, cohérent avec les getters déjà existants) : nécessaire pour transmettre
    tous les en-têtes déjà posés par le pipeline NiangPro lors d'une conversion vers PSR-7, que
    `getHeader(string $key)` seul ne permettait pas.
  - **`Niang\Core\Http\Psr15Adapter`** implémente le contrat `Middleware` existant, reçoit un
    `Psr\Http\Server\MiddlewareInterface` en constructeur, fait le pont dans les deux sens via
    `Psr7Bridge` — s'attache à une route exactement comme un middleware natif :
    `$container->bind(Psr15Adapter::class, fn () => new Psr15Adapter(new UnMiddlewareTiers()))`
    (typiquement dans `ServiceProvider::register()`) puis `Psr15Adapter::class` dans le tableau
    de middlewares d'une route — comportement déjà celui du Container pour toute classe dont une
    dépendance est une interface, rien à modifier dans `Router`/`Container` pour ce jalon.
    Limite assumée et documentée : un middleware PSR-15 qui modifie la réponse ou court-circuite
    fonctionne pleinement ; un middleware qui n'agit que sur des attributs PSR-7 de la requête ne
    peut pas les transmettre à NiangPro par ce pont (`Request` n'a pas de notion d'attributs, lui
    en ajouter reviendrait à commencer la refonte que ce pont évite justement).
  - Pas de sous-namespace `Niang\Core\Http\Psr15\*` : `Psr7Bridge`/`Psr15Adapter` vivent au
    même niveau que `Request`/`Response`, cohérent avec l'existant (`Csrf`, `Cookie`... jamais
    sous-namespacés pour une seule classe).
  - 10 nouveaux tests (`tests/Unit/Http/Psr7BridgeTest.php`, `tests/Unit/Http/
    Psr15AdapterTest.php`) : conversion réelle Request→PSR-7 (méthode, URI, query, body,
    en-têtes) et retour, un vrai middleware PSR-15 écrit pour le test (pas une vraie lib tierce)
    qui modifie effectivement la réponse et un autre qui court-circuite, le message d'erreur
    clair quand aucune implémentation PSR-7 n'est installée (testé sans désinstaller nyholm : la
    vérification `class_exists` est extraite dans une méthode qui prend la classe et le paquet en
    paramètres, invoquée par réflexion avec un nom de classe qui n'existe sûrement pas).

- **Compatibilité navigateurs (Chrome, Firefox, Edge, Safari desktop + iOS)** (P0 #15,
  NiangPro 2.0 — dix-huitième jalon ; rattaché aux Thèmes de site, §63) : audit statique du CSS/JS
  partagé et de celui de chaque thème avant toute correction, cible modernes uniquement (IE11 et
  WebView Android ancien explicitement hors cible — rien de moderne déjà en place n'a été
  sacrifié pour eux).
  - **Rien de récent/spécifique à un moteur trouvé** : aucun `:has()`, `color-mix()`, `@container`
    ni `subgrid` nulle part dans `resources/scaffold/`. Rien à simplifier ni à replier de ce côté.
  - **`body { min-height: 100vh }`** (`niang.css`, seule occurrence de `100vh` du design system —
    les héros des thèmes utilisent déjà `padding-block: clamp(...)`, jamais de hauteur fixe en
    `vh`) : complété par `min-height: 100dvh` en repli progressif, à cause du bug connu de la
    barre d'adresse rétractable de Safari iOS qui laisse un vide sous le pied de page tant qu'elle
    reste affichée.
  - **FAQ (`<details>`/`<summary>`)** : `list-style: none` + `::-webkit-details-marker` masquaient
    déjà le triangle par défaut (Chrome/Firefox honorent `list-style` sur `<summary>`, WebKit ne
    l'honore pas et n'obéit qu'à son propre pseudo-élément) — `::marker { display: none }` ajouté
    en complément explicite, conforme au standard, pour ne dépendre d'aucun comportement implicite.
  - **Autofill** (connexion, inscription, checkout, formulaire de contact) : Chrome et Safari
    peignent un fond propre au navigateur sur un champ mémorisé, indépendant de `color-scheme` —
    cassait le thème sombre. `-webkit-box-shadow: 0 0 0 1000px var(--surface) inset` +
    `-webkit-text-fill-color` ajoutés sur `.field input:-webkit-autofill`.
  - **Vérifié, non applicable — documenté honnêtement plutôt que corrigé pour rien** :
    `env(safe-area-inset-*)` n'a d'effet que si le meta viewport déclare `viewport-fit=cover`, ce
    qui n'est le cas nulle part ici, et aucune barre `position: fixed` n'existe dans le design
    system ni dans aucun thème (le header est `sticky`, pas `fixed`) — l'ajouter serait un
    no-op. Le « bouton clair/sombre manuel » évoqué comme point de vigilance n'existe pas non
    plus : la bascule est uniquement automatique via `prefers-color-scheme` (`niang.js` ne touche
    jamais `localStorage`), rien à encapsuler dans un `try/catch`.
  - **Vérification par mesure réelle, pas seulement une relecture visuelle** : Node étant
    disponible sur cette machine, `tools/browser-smoke/` (outil jetable, jamais un prérequis de
    `composer test` — voir son README) lance un vrai serveur PHP par thème et vérifie, sur les
    trois moteurs que Playwright embarque (Chromium, Firefox, **WebKit** — seul moyen réaliste de
    tester le rendu Safari sans Mac dédié, et qui couvre aussi Safari iOS au passage, même
    moteur), que `/`, `/up`, `/health` répondent sur les 6 thèmes, et que les interactions
    (menu burger, formulaire de contact, FAQ, rendu clair/sombre via `prefers-color-scheme`
    émulé) fonctionnent sur `vitrine` (représentatif : les trois mécanismes viennent du design
    system partagé, communs à tous les thèmes — inutile de répéter la vérification 6 fois).
    **69/69 vérifications réussies sur les 3 moteurs**, exécuté réellement en écrivant ce jalon
    (pas seulement écrit puis supposé fonctionner).
  - **Un vrai piège rencontré et documenté, pas un bug produit** : le premier essai du clic FAQ
    échouait sur les 3 moteurs à l'identique — pas un bug de rendu, mais deux pièges de
    l'automatisation elle-même (la debug toolbar `position: fixed` interceptait les clics près du
    bas de l'écran ; un sélecteur `:not([open])` se ré-évaluait après le clic et la vérification
    portait sur un autre `<details>`). Voir `tools/browser-smoke/README.md`.

- **Extensibilité des thèmes en paquets Composer séparés** (P0 #15, NiangPro 2.0 — dix-septième
  jalon ; complète les « Starter Kits » de la roadmap, §63, voir aussi Plugins/packages, §47) :
  jusqu'ici, un thème n'existait qu'en dossier local de `resources/scaffold/themes/<slug>/` —
  rien ne permettait d'en partager un sans copier-coller ses fichiers dans chaque projet.
  - **Conception : convention légère plutôt qu'un plugin Composer.** Deux options envisagées :
    (a) une convention `extra.niangpro-theme` dans le `composer.json` d'un paquet tiers,
    pointant vers un dossier interne qui reproduit l'arborescence d'un thème — une commande
    copie ce dossier dans le projet courant ; (b) un vrai plugin Composer avec ses propres
    classes d'installateur, packages de type `niangpro-theme`, écouteurs d'événements Composer.
    (b) ajouterait une dépendance de développement (`composer-plugin-api`) et une couche de
    complexité (cycle de vie d'un plugin, compatibilité entre versions de Composer) qu'un thème
    — « juste un dossier », déjà le principe assumé du dixième jalon — ne justifie pas : (a)
    couvre la quasi-totalité des cas réels sans rien ajouter au *runtime* de l'application.
    Choix : (a).
  - **`Niang\Core\Console\ThemePackageInstaller`** (logique pure et testable, aucun process
    composer lancé par cette classe) : lit `extra.niangpro-theme` du `composer.json` d'un paquet
    déjà présent dans `vendor/`, copie le dossier qu'elle désigne vers
    `resources/scaffold/themes/<slug>/` — `<slug>` est le nom de ce dossier tel que le paquet
    l'a lui-même nommé, jamais redemandé séparément. `ProjectScaffolder` n'a besoin d'aucune
    modification pour le proposer ensuite : un thème est déjà « juste un dossier ».
  - **`niang theme:add vendor/paquet`** (Commander, l'I/O : `composer require --dev` — inutile
    en production, seulement à la création de projets — puis `ThemePackageInstaller::install()`).
    Documenté dans le README, section « Publier votre thème comme paquet Composer ».
  - 6 nouveaux tests (`tests/Unit/Console/ThemePackageInstallerTest.php`), fixture locale (pas un
    vrai téléchargement Packagist) : un thème installé apparaît dans
    `ProjectScaffolder::catalog()`, son dossier est copié tel quel, paquet non installé ou sans
    la clé `extra.niangpro-theme` rejeté, dossier de thème déclaré mais absent rejeté, slug
    réservé (`minimal`) rejeté.

- **Un vrai smoke test de packaging en CI** (P0 #15, NiangPro 2.0 — seizième jalon ; CI/CD de la
  roadmap, §58) : jusqu'ici, la CI vérifiait le code (tests, lint, analyse statique) mais jamais
  l'expérience d'installation elle-même — ce qu'aucun test unitaire ne peut couvrir par
  construction (permissions, fichiers manquants, dépendance oubliée dans un thème). Nouveau job
  `packaging-smoke`, en matrice sur les 6 types de site.
  - **`composer create-project` depuis le commit courant, pas depuis Packagist**, via un dépôt
    Composer de type `path` (`{"type":"path","url":".","options":{"symlink":false}}`) —
    `symlink:false` force une copie réelle, pas un lien qui masquerait un souci de packaging.
    La commande telle que donnée ne suffit pas telle quelle : un paquet `path` n'a pas de version
    stable tant qu'il n'est pas tagué, incompatible avec le `minimum-stability: stable` du
    projet — il faut lui donner explicitement la version à installer
    (`niangpro/framework /tmp/niang-demo dev-main`). Vérifié en installant réellement en local
    (avec et sans `--no-dev`) avant d'écrire le workflow, pas seulement lu dans la documentation
    Composer.
  - **Vérifié avant même de démarrer le serveur** : ni `.env` ni `storage/` n'existent dans le
    projet fraîchement créé (tous deux hors du dépôt git, jamais commités) — sans quoi le test ne
    prouverait rien. Confirmé en local avec un `git archive` du commit courant comme source
    (plutôt que le répertoire de travail, qui a un `.env` local qui aurait faussé le test) : la
    suite `README.md` ne documente d'ailleurs aucune étape `.env` pour ce chemin d'installation,
    seulement pour un clone direct du dépôt.
  - **`./bin/niang migrate` puis `./bin/niang db:seed`** (comme le suggèrent les `next_steps` du
    thème, voir dixième jalon) avant de servir : sans ça, `/boutique` (ecommerce) ou `/blog`
    (blog) répondraient 500, une table inexistante plutôt qu'un vrai problème de packaging —
    `db:seed` sur un thème qui n'a pas de seeder (`vitrine`, `portfolio`, `landing`) est un
    no-op silencieux, sans effet ni échec.
  - `php -S 127.0.0.1:8000 -t public` en arrière-plan, attente active sur `/up` (jusqu'à 10s)
    avant de vérifier `/`, `/up`, `/health` et une route stable propre à chaque thème
    (`/a-propos`, `/boutique`, `/blog`, `/projets`, `/mentions-legales`, `/contact` pour
    `minimal`) — 200 attendu partout, log du serveur affiché en cas d'échec pour diagnostiquer
    sans reproduire en local.

- **Compilation du Router + préchargement OPcache** (P0 #15, NiangPro 2.0 — quinzième jalon ;
  Router 2.0 et OPcache/production de la roadmap, §12 et §40) : deux sujets performance
  indépendants des jalons précédents.
  - **Le matching était linéaire par requête, indépendamment de la méthode HTTP.**
    `Router::matchRoute()` testait le pattern regex de **chaque** route enregistrée, dans
    l'ordre de déclaration, avant même de regarder si sa méthode HTTP pouvait correspondre —
    y compris pour un 404 (aucune route ne matche : le tableau entier est parcouru). Mesuré
    avant modification avec un micro-benchmark jetable (1000 routes, premier segment d'URI
    distinct par route) : **0,007 ms/requête pour la première route déclarée, 0,43 ms pour la
    dernière, 0,40 ms pour un 404** — un facteur ~60 entre le meilleur et le pire cas.
  - **Fix : index par premier segment d'URI statique.** `Router` construit désormais, à la
    demande et une seule fois (invalidé à chaque route ajoutée), une table `premier segment =>
    indices de route`. `matchRoute()` n'évalue plus que les routes dont le premier segment
    correspond exactement à celui de l'URL demandée, plus celles dont le premier segment est
    un paramètre (`{slug}`, structurellement indécidable sans évaluer leur pattern). Après
    modification, même benchmark : **0,0083 ms pour la dernière route (~52x plus rapide), 0,004
    ms pour un 404 (~98x plus rapide)** ; sur un scénario plus réaliste (50 ressources × 10
    routes, 10 routes partageant chaque premier segment), 0,013 ms pour la dernière route d'un
    groupe. Rétrocompatible : le comportement de matching (405, fallback, domaines, HEAD
    implicite) est inchangé, seul l'ordre dans lequel les routes sont testées change à
    résultat égal — couvert par la suite `RouterTest` existante, complétée de 3 tests pour les
    cas jamais exercés jusqu'ici (segment dynamique en première position, premiers segments
    distincts qui ne s'interfèrent pas, route ajoutée après que l'index a déjà été construit).
  - **`preload.php`** (racine du projet) précharge `src/Core/**/*.php` via
    `opcache_compile_file()`, liste construite par `glob()` (donc toujours des fichiers
    existants, rien à vérifier à la main de ce côté), protégé par
    `function_exists('opcache_compile_file')`. C'est un réglage **serveur/déploiement**
    (`opcache.preload` dans le `php.ini` du serveur, jamais dans le projet) — documenté dans
    `docs/ROADMAP_TECHNIQUE.md` (section 40). `niang optimize` (une requête CLI ponctuelle) ne
    peut pas l'activer lui-même ; il se contente désormais de rappeler que le fichier existe.
  - **Honnêteté sur ce qui est testé** : le comportement réel d'OPcache ne se prête pas à un
    test PHPUnit. Seule la syntaxe de `preload.php` est vérifiée automatiquement (`php -l`, en
    CI, nouveau step) ; son effet sur les performances ne l'est pas et se vérifie à la main.

- **Écouteurs d'événements différables (`ShouldQueue`)** (P0 #15, NiangPro 2.0 — quatorzième
  jalon ; Queue 2.0 et Events typés de la roadmap, §25-26) : qu'un listener lourd (l'envoi de
  l'email de vérification du douzième jalon, par exemple) ne bloque plus la requête HTTP qui a
  émis l'événement. Interface marqueur `Niang\Core\Contracts\ShouldQueue`, sans méthode requise.
  `Event::listen()` accepte désormais aussi une classe (nom de classe, exposant `handle(...)`)
  en plus d'une closure ; dans `Event::dispatch()`, une classe qui implémente `ShouldQueue` n'est
  plus appelée directement mais enrobée dans `Niang\Core\Jobs\CallQueuedListener` (implémente le
  contrat `Job` existant) et poussée sur `Queue`. **Les listeners enregistrés comme closures
  restent toujours synchrones** — une closure ne survivrait pas sérialisée sur la file ; ce n'est
  pas une limitation à lever, juste une conséquence de ce que `serialize()` peut représenter.
  `App\Listeners\SendVerificationEmailListener` (`ShouldQueue`) remplace l'appel direct que
  `AuthController::register` faisait à l'envoi de l'email de vérification : il est désormais
  déclenché par l'événement `user.registered` déjà émis, en écouteur de classe plutôt que
  closure (voir `AppServiceProvider`).
  - **Effet de bord découvert en écrivant ce jalon, corrigé au passage** : `Event::$listeners`
    et la file `Queue` (sur fichier) ne se réinitialisaient jamais entre deux tests — invisible
    tant qu'aucun listener n'avait d'effet observable (le seul existant se contentait de
    logguer), mais un `ShouldQueue` réel l'a rendu flagrant (jusqu'à 42 envois dupliqués en fin
    de suite, `AppServiceProvider::boot()` empilant ses écouteurs à chaque nouvelle
    `Application` de chaque test). `Event::reset()` et `Queue::reset()` ajoutés, tous deux
    appelés désormais par `Niang\Core\Testing\TestCase::setUp()` — en production, chaque
    requête est un process neuf, le problème ne s'y pose pas.
  - 4 nouveaux tests (`tests/Unit/EventTest.php`, réutilise le pattern de `tests/Unit/
    QueueTest.php`) : un listener `ShouldQueue` finit dans la file au lieu de s'exécuter
    immédiatement, un listener de classe normal et un listener closure restent synchrones
    (non-régression), `Event::reset()` vide bien les écouteurs enregistrés.

- **Authentification par jeton pour l'API** (P0 #15, NiangPro 2.0 — treizième jalon ;
  Authentication 2.0 de la roadmap, §21) : permet à un client hors navigateur (app mobile, SPA
  découplée, script) de s'authentifier sur les routes `/api/*` sans session ni cookie.
  - **Superposition sur `Auth` plutôt que refonte.** `Niang\Core\Auth::id()/check()/user()`
    résolvaient jusqu'ici exclusivement via la session (`Session::get('_auth_user_id')`).
    Plutôt que d'introduire des « guards » multiples (surdimensionné pour ce framework), `Auth`
    gagne un état `$tokenUser`, prioritaire sur la session dans ces trois méthodes, posé par
    `Auth::resolveViaToken()` — `@internal`, appelée uniquement par le nouveau middleware, jamais
    par une application. Le middleware le réinitialise dans un `finally` après chaque requête :
    aucune fuite d'un utilisateur résolu par jeton vers une requête suivante, y compris dans un
    process long (CLI, tests) qui en traiterait plusieurs à la suite.
  - **`personal_access_tokens`** (user_id, name, token_hash, last_used_at, created_at ;
    migration `2026_09_22_110001`). Le jeton en clair n'est jamais stocké ni loggé — mais
    **hachage déterministe (HMAC-SHA256, clé `APP_KEY`, même principe que `Cookie::sign()` et
    `UrlSignature`), pas `Hash::make()`** : contrairement à un mot de passe ou au jeton de reset
    du jalon précédent (toujours vérifiés dans le contexte d'un email déjà connu), un jeton API
    doit être retrouvable à partir de sa seule valeur présentée par le client — un hash salé
    interdirait toute recherche indexée en base. La haute entropie du jeton (32 octets
    aléatoires) rend un hash rapide suffisant, à la différence d'un mot de passe choisi par un
    humain.
  - **`Niang\Core\ApiToken`** (pas de sous-namespace `Niang\Core\Auth\*` : entrerait en conflit
    avec la classe `Niang\Core\Auth` existante) : `issue(user, name): string` retourne le jeton
    en clair une seule fois, à l'émission ; `resolve(plaintext): ?array` retrouve l'utilisateur
    (via `Auth::model()`, nouvel accesseur public du modèle configuré par `Auth::useModel()`) et
    met à jour `last_used_at`.
  - **`App\Middleware\AuthenticateWithToken`** lit `Authorization: Bearer <jeton>`, 401 si
    absent/invalide, sinon `Auth::resolveViaToken()` pour la durée de la requête. Appliquée à
    l'exemple `GET /api/me`, pas au groupe `/api` entier : `GET /api/posts` reste public,
    `POST /api/tokens` (l'émission elle-même) ne peut pas exiger le jeton qu'elle délivre.
  - **`POST /api/tokens`** (`App\Controllers\Api\TokenController`) : émission minimale, sans UI
    (email + password + device_name → jeton), sur le modèle des autres exemples du groupe `/api`.
  - Thèmes de site : aucun n'expose de routes `/api/*` aujourd'hui (vérifié dans les 5 thèmes) —
    rien à propager, contrairement au jalon précédent.
  - 10 nouveaux tests (`tests/Unit/ApiTokenTest.php`, `tests/Feature/ApiTokenAuthTest.php`) :
    émission puis résolution, jeton inconnu ou révoqué rejeté, `last_used_at` mis à jour, seul le
    hash est en base, bout en bout via `/api/tokens` + `/api/me`, et l'authentification par jeton
    n'interfère jamais avec une session de navigateur active en parallèle.

- **Récupération de mot de passe + vérification d'email** (P0 #15, NiangPro 2.0 — douzième
  jalon ; Authentication 2.0 de la roadmap, §21 ; dépend des URLs signées, onzième jalon)
  : `password_reset_tokens` (email, token_hash, created_at) et `users.email_verified_at`
  (migrations `2026_09_22_100001` et `2026_09_22_100002`). `AuthController::sendResetLink`
  génère un jeton aléatoire, le stocke haché (jamais en clair, même logique que `Hash` pour
  les mots de passe — voir `App\Models\PasswordResetToken`), supprime tout jeton existant pour
  cet email, et envoie un `signedRoute()` (`password.reset`, expiration `auth.
  password_reset_expire_minutes`, 60 min par défaut) par email (`App\Mailables\
  ResetPasswordMailable`). Réponse **strictement identique** que l'email corresponde à un
  compte ou non, pour ne jamais révéler quels comptes existent. `AuthController::resetPassword`
  vérifie **à la fois** la signature (middleware `ValidateSignature`, sur les routes GET et
  POST — voir la nouvelle capacité de `Request::create()` ci-dessous) et le jeton en base ;
  celui-ci est détruit après usage, qu'il ait servi ou non (à usage unique). Le token et l'email
  voyagent comme paramètres de route (`/reset-password/{token}/{email}`), pas en query string
  triée : plus simple à canonicaliser puisqu'ils font partie du chemin, seule `expires` reste en
  query string. À l'inscription, `AuthController::register` envoie désormais aussi un
  `signedRoute()` (`verification.verify`, expiration `auth.email_verification_expire_hours`,
  24h par défaut, `App\Mailables\VerifyEmailMailable`) qui renseigne `email_verified_at`.
  Middleware `App\Middleware\EnsureEmailIsVerified` fourni mais **non branché par défaut** (ni
  sur les routes existantes, ni sur les thèmes de site livrés) : une application décide
  elle-même où l'exiger.
  - **`Request::create()`** (client de test) accepte désormais une query string incluse dans
    l'URI quelle que soit la méthode HTTP (`$this->post('/reset-password/...?expires=...
    &signature=...', ['password' => '...'])`), comme le ferait `$_GET` en production
    indépendamment du corps — nécessaire pour tester une route POST protégée par
    `ValidateSignature`. Rétrocompatible : sans `?` dans l'URI, comportement inchangé.
  - Les 5 thèmes sans compte utilisateur (`vitrine`, `blog`, `portfolio`, `landing` — et
    `minimal`, qui hérite du projet racine tel quel) n'embarquent ni les routes ni les tests de
    ce jalon (`resources/scaffold/shared/theme.json`, liste `remove`, même mécanisme que pour
    `AuthTest.php`) ; seul `ecommerce`, qui a déjà un compte client, reçoit les nouvelles routes
    et vues (thémées, alerte `.alert--info`).
  - 12 nouveaux tests (`tests/Feature/PasswordResetTest.php`, `tests/Feature/
    EmailVerificationTest.php`, `tests/Unit/Middleware/EnsureEmailIsVerifiedTest.php`,
    `tests/Unit/Http/RequestTest.php`) : bout en bout (demande → email capturé par
    `Mail::fake()` → URL extraite du corps de l'email → requête dessus → nouveau mot de passe
    fonctionne, l'ancien non), réponse identique compte existant/inconnu, jeton à usage unique,
    lien altéré ou expiré rejeté.

- **URLs signées** (P0 #15, NiangPro 2.0 — onzième jalon ; rattaché à l'Authentication 2.0 de la
  roadmap, §21) : prérequis du reset de mot de passe et de la vérification d'email (prochains
  jalons) — un lien cliquable qui prouve qu'il vient de l'application, sans authentification
  préalable, et qui peut expirer. `Niang\Core\UrlSignature` (classe pure) signe une URL
  (chemin + query string, comme celle que renvoie `route()`) par HMAC-SHA256 sur sa forme
  canonique — paramètres de requête triés par clé, `expires` inclus, `signature` exclue — avec
  `APP_KEY` comme clé, même principe que `Cookie::sign()`. Le tri des paramètres avant hachage
  rend la signature indépendante de l'ordre dans lequel le navigateur renvoie la query string.
  `validate()` recalcule et compare avec `hash_equals` ; renvoie `false` si `expires` est dépassé
  ou si la signature est absente. Nouveau helper `signedRoute(nom, params, expiresInSeconds)`
  (`route()` + `UrlSignature::sign()`) et middleware `App\Middleware\ValidateSignature`
  (`abort(403)` si l'URL courante, reconstruite depuis la requête, ne valide pas) — sur le
  modèle des middlewares existants (`VerifyCsrfToken`, `ThrottleRequests`). 12 nouveaux tests
  (`tests/Unit/UrlSignatureTest.php`, `tests/Unit/Middleware/ValidateSignatureTest.php`) :
  falsification d'un paramètre, expiration, signature absente, avec et sans paramètres nommés.

- **Thèmes de site à la création d'un projet** (P0 #15, NiangPro 2.0 — dixième jalon ; rattaché aux
  « Starter Kits » de la roadmap, §63) : à la création d'un projet, NiangPro demande quel type de site
  construire et installe un thème visiteur complet — plutôt que le squelette de démonstration seul.
  Six types : `vitrine`, `ecommerce`, `blog`, `portfolio`, `landing` et `minimal` (le squelette actuel,
  inchangé, toujours proposé en dernier et choisi par défaut).
  - **Deux chemins d'installation, un seul code.** `composer create-project niangpro/framework mon-app`
    (qui copie simplement les fichiers du paquet et n'exécute jamais `niang new`) déclenche le nouveau
    script `post-create-project-cmd` → `Niang\Core\Console\ComposerHooks::postCreateProject`.
    `./bin/niang new mon-app [--type=<slug>]` appelle la même logique. Dans les deux cas, la question est
    posée par `SiteTypePrompt` et l'installation faite par `ProjectScaffolder`, qui ne lisent ni STDIN ni
    n'écrivent nulle part (lecture et affichage sont injectés), sur le modèle de `HealthCheck`.
  - **Jamais bloquant.** Ordre de priorité : `--type=<slug>`, puis la variable d'environnement
    `NIANG_SITE_TYPE`, puis la question — posée seulement si STDIN est un terminal et que Composer n'est pas
    en `--no-interaction` —, puis `minimal`. Un type explicite inconnu fait échouer `niang new` (code 1,
    avant toute copie, en listant les types valides) ; pour `composer create-project`, où le projet est déjà
    en place, il avertit et garde le squelette minimal plutôt que d'échouer après coup. Une fin de saisie
    (Ctrl+D) garde aussi `minimal`. `niang new` pose la question *avant* de copier et de lancer
    `composer install`, pour ne pas interrompre l'utilisateur après une longue attente.
  - **Remplacement, pas fusion.** L'installation n'a lieu qu'une fois, avant que l'utilisateur ait touché
    à quoi que ce soit : le thème remplace `routes/web.php`, les vues concernées de `resources/views/` et
    les assets, et supprime les tests de fonctionnalité de la démo qui ne s'appliquent plus (`HomeTest`,
    `PostsTest`, `CorsTest`... : les routes de démonstration `/hello`, `/echo`, `/tenant`, `/api/posts`
    disparaissent avec `routes/web.php`). Chaque thème est livré avec **ses propres tests**, donc
    `composer test` reste vert dans le projet créé. Seuls `/up` et `/health` sont conservés partout.
  - **Un thème = un dossier.** `resources/scaffold/themes/<slug>/` reproduit l'arborescence d'un projet
    (`app/`, `config/`, `database/`, `public/`, `resources/views/`, `routes/web.php`, `tests/`) : aucune
    table de correspondance dans le code, et un thème peut aussi livrer contrôleurs, modèles, migrations et
    tests. Ajouter un dossier suffit à l'ajouter au catalogue (extensible par l'utilisateur, sans rien
    enregistrer). Un `theme.json` facultatif donne `label`, `order`, `remove` et `next_steps` (les
    commandes affichées ensuite : une boutique doit migrer et alimenter la base, un site vitrine non).
    `resources/scaffold/shared/` suit la même arborescence et fournit ce que tous les thèmes ont en
    commun. *(Écart assumé avec l'arborescence proposée `views/`, `routes.php`, `assets/`, `seed/`.)*
  - **Design system partagé** (`shared/public/css/niang.css`, `shared/public/js/niang.js`, composants) :
    variables `:root` pour les couleurs et l'espacement, identité teal `#2dd4bf` / doré `#facc15`
    conservée, clair **et** sombre via `prefers-color-scheme` (couleurs de texte distinctes des couleurs
    de fond pour garder le contraste en mode clair), échelle typographique fluide en `clamp()`, police
    système, header sticky avec menu burger en JS vanilla, grilles auto-adaptatives, cartes, FAQ en
    `<details>` natif, focus visible, `prefers-reduced-motion`, icônes en SVG inline. Aucun CDN, aucune
    webfont, aucune police d'icônes. Les assets vont dans `public/css/` et `public/js/` (convention de la
    roadmap §45) plutôt que dans un dossier `public/assets/`. **Tout le JS est en fichiers externes** : la CSP
    par défaut de `config/security.php` interdit les scripts inline, et chaque thème est testé pour n'en
    contenir aucun. Sans JavaScript, les sites restent utilisables (menu affiché, prix mensuels, saisie
    manuelle des quantités).
  - **Contenu de démonstration** fictif en français, centralisé dans `config/site.php` de chaque thème
    (modifiable sans toucher aux vues). Les illustrations sont des SVG générés (`components/art`, avec
    `role="img"` et `aria-label`) à la place de photos : aucun fichier binaire à embarquer.
  - **`vitrine`** : accueil (héros, présentation, points forts, services, réalisations, témoignages, CTA),
    à propos, services, réalisations, FAQ, contact (réhabille `ContactController`), mentions légales,
    politique de confidentialité, 404.
  - **`ecommerce`** : accueil, catalogue (filtre par catégorie, recherche, tri), fiche produit (galerie,
    promotion, stock), panier en session (quantités, retrait, total, livraison offerte au-delà d'un
    seuil), commande et confirmation, compte client (réhabille `AuthController`) et « Mes commandes »,
    à propos, contact, FAQ livraison et retours, CGV, mentions légales, 404. Schéma : `products` (`name`,
    `slug` unique, `category`, `description`, `price_cents`, `old_price_cents`, `image`, `stock`,
    `featured`), `orders` et `order_items`, avec modèles `Product`/`Order`/`OrderItem` et un seeder de neuf
    produits fictifs idempotent. Les montants sont des **entiers en centimes**, jamais des flottants ; une
    ligne de commande copie le nom et le prix à l'achat. **Aucun paiement réel** : la commande est
    enregistrée « pending » et un TODO documenté dans `CheckoutController` décrit où brancher un
    prestataire (session de paiement, retour, webhook qui passe la commande à « paid »). Le checkout
    rejoint la transaction ambiante plutôt que d'en ouvrir une seconde (`DB::transaction()` n'imbrique
    pas), et la confirmation n'est visible que par le client qui vient de commander ou le titulaire du
    compte.
  - **`blog`** : accueil, liste paginée (réutilise `PostController::page`), article (tags, temps de
    lecture, articles proches), catégories et thèmes, à propos, contact, 404. Une migration ajoute `slug`,
    `excerpt`, `category` et `author` (facultatifs) à `posts` ; un seeder de huit articles fictifs. Le corps
    d'un article est du texte simple mis en forme par `App\Support\PostFormat`, entièrement échappé (pas
    de HTML injectable, pas de moteur Markdown embarqué). `TagController` n'est exposé qu'en lecture
    (`GET /api/tags`) : ses routes d'écriture n'ont ni authentification ni CSRF dans la démo. *(Écart avec la
    spécification, qui prévoyait des seeds pour la boutique seule : un blog vide n'a aucun intérêt.)*
  - **`portfolio`** : accueil, projets avec filtre par catégorie, page de détail par projet (galerie,
    défi, démarche, résultats, navigation entre projets), à propos avec compétences et frise du CV,
    contact, 404 — sans base de données, les projets sont dans `config/site.php`.
  - **`landing`** : une page à sections ancrées (héros, fonctionnalités, comment ça marche, témoignages,
    tarifs avec bascule mensuel/annuel, FAQ, CTA final, pied de page), mentions légales, 404. Les liens
    de navigation commencent par `/#` pour fonctionner aussi depuis les mentions légales.
  - **Tests.** 65 nouveaux tests dans le dépôt (`ProjectScaffolderTest`, `SiteTypePromptTest`,
    `ComposerHooksTest`, `CommanderNewTest`, `ThemeInstallationTest`). `tests/Support/StagedProject` installe
    réellement un thème dans une copie du projet et y lance PHPUnit dans un process séparé (`base_path()`
    est figé au chargement de `helpers.php` : un test en process ne pourrait pas exercer un thème installé
    ailleurs) ; `ThemeInstallationTest` en tire, pour chaque thème livré, l'exécution de toute la suite de
    la copie et la vérification que ses routes sont compatibles `route:cache` (aucune closure). Vérifié
    aussi à la main avec un vrai `composer create-project` (non interactif, `NIANG_SITE_TYPE`, faute de
    frappe, et invite réelle pilotée dans un pseudo-terminal) et un vrai `niang new`. PHPStan et
    PHP-CS-Fixer couvrent désormais les classes PHP de `resources/scaffold` (contrôleurs, modèles, tests) ;
    les vues, routes, configs et migrations, comme à la racine du projet, ne sont pas analysées.

- **`niang health` + `GET /health`** (P0 #15, NiangPro 2.0 — neuvième jalon) : nouvelle classe
  `Niang\Core\HealthCheck`, logique partagée entre la commande CLI et l'endpoint HTTP — vérifie
  l'état d'exécution réel (une vraie connexion DB, un aller-retour `Cache::put()`/`get()`,
  `storage/` accessible en écriture, `Queue::pending()`), contrairement à `niang doctor` qui ne
  vérifie que la configuration statique. `app/Controllers/HealthController.php` (déjà présent
  depuis v0.9.0 sur `GET /up`, mais qui ne vérifiait que la base de données) route désormais vers
  `HealthCheck::run()` ; `GET /health` ajouté en alias documenté par la roadmap, `/up` conservé
  pour ne pas casser une supervision déjà branchée dessus. Réponse : `{"status": "ok"|"error",
  "services": {"database": "ok", "cache": "ok", "storage": "ok", "queue": "ok"}}`, HTTP 200/503.
  `niang health` affiche le même détail et sort avec le code 1 si `status !== "ok"`.
  6 nouveaux tests (`tests/Unit/HealthCheckTest.php`, `tests/Feature/HealthTest.php`,
  `tests/Unit/Console/CommanderHealthTest.php`).

### Changed

- `PostController::page` (P0 #15, dixième jalon) : liste désormais les articles les plus récents d'abord,
  et lit le nombre d'articles par page dans `config('site.posts_per_page')` (2 par défaut, comme avant :
  le squelette minimal n'a pas de `config/site.php`).
- `niang new <nom>` accepte `--type=<slug>` et affiche les étapes suivantes propres au thème installé
  (`niang migrate` + `niang serve` pour le type `minimal`, comme avant). Comme il clone le projet courant,
  lancé depuis un projet déjà thématisé il en reprend le thème.

### Fixed

- Isolation des tests (P0 #15) : `tests/bootstrap.php` charge `.env.testing` avant tout. Les tests de
  `Commander` chargeaient le `.env` réel via son constructeur (`Env::load()` ne s'exécute qu'une fois par
  process), ce qui faisait taper le reste de la suite dans `storage/database.sqlite` et échouer huit tests
  dès qu'un `.env` local existait. `CommanderMakeTestTest` laissait en outre un fichier
  `tests/Unit/AlreadyThereTest.php` derrière lui, qui faisait ensuite échouer PHPStan.

## [1.2.0] — 2026-09-17

### Added

- **`niang make:command`** (P0 #15, NiangPro 2.0 — huitième jalon) : contrairement aux autres
  générateurs, ceci complète un vrai mécanisme d'exécution plutôt qu'un fichier mort. Nouvelle
  classe `Niang\Core\Console\Command` (`$signature`, `$description`, `handle(array $arguments)`) —
  `Commander` scanne `app/Console/Commands/*.php` quand une commande tapée ne correspond à aucune
  commande native, et exécute la classe dont `$signature` correspond (`niang mon:nom ...`, les
  arguments suivants passés à `handle()`). Une commande inconnue sans correspondance retombe sur
  l'aide générale, comme avant. `make:command NomCommand` génère le squelette dans
  `app/Console/Commands/NomCommand.php`. 5 nouveaux tests (`tests/Unit/Console/CommanderMakeCommandTest.php`,
  `tests/Unit/Console/CommanderCustomCommandTest.php`).
- **`niang make:event`** (P0 #15, NiangPro 2.0 — septième jalon) : génère `app/Events/NomEvent.php`
  (suffixe `Event` ajouté automatiquement si absent), une classe simple à constructeur — le système
  d'événements de NiangPro reste string-based (`Event::listen()`/`Event::dispatch()`, voir
  `src/Core/Event.php`), donc le stub documente en commentaire le pattern déjà utilisable
  aujourd'hui sans changement du dispatcher : `NomEvent::class` comme identifiant. N'écrase jamais
  un événement existant. 3 nouveaux tests (`tests/Unit/Console/CommanderMakeEventTest.php`).
- **`niang make:test`** (P0 #15, NiangPro 2.0 — sixième jalon) : génère `tests/Unit/NomTest.php`
  (suffixe `Test` ajouté automatiquement si absent) qui étend `PHPUnit\Framework\TestCase` avec une
  méthode d'exemple. N'écrase jamais un test existant. 3 nouveaux tests
  (`tests/Unit/Console/CommanderMakeTestTest.php`).
- **`niang make:job`** (P0 #15, NiangPro 2.0 — cinquième jalon) : génère `app/Jobs/NomJob.php`
  (suffixe `Job` ajouté automatiquement si absent) qui étend `Niang\Core\Job` avec un constructeur
  et un `handle()` vides à compléter — sur le modèle de `app/Jobs/SendWelcomeEmailJob.php`,
  prêt à être poussé en file via `Queue::push(new NomJob(...))`. N'écrase jamais un job existant.
  3 nouveaux tests (`tests/Unit/Console/CommanderMakeJobTest.php`).
- **`niang make:policy`** (P0 #15, NiangPro 2.0 — quatrième jalon) : génère `app/Policies/NomPolicy.php`
  (suffixe `Policy` ajouté automatiquement si absent, sur le modèle de `make:request`), avec un
  rappel en commentaire de la méthode d'enregistrement (`Gate::policy('prefix', NomPolicy::class)`
  dans `routes/web.php`) et deux méthodes d'exemple commentées (`update`/`delete`). N'écrase jamais
  une policy existante. 3 nouveaux tests (`tests/Unit/Console/CommanderMakePolicyTest.php`).
- **`niang doctor`** (P0 #15, NiangPro 2.0 — troisième jalon) : diagnostique l'environnement en
  une commande — version PHP (>= 8.1.0), extension `pdo` et celle du driver configuré
  (`pdo_sqlite`/`pdo_mysql`/`pdo_pgsql` selon `DB_CONNECTION`), extension `json`, présence de
  `.env`, `APP_KEY` configurée, `storage/`, `storage/logs/` et `storage/framework/` accessibles en
  écriture, connexion réelle à la base de données, chargement de `routes/web.php`. Chaque ligne
  est indépendante (une extension manquante n'empêche pas de vérifier le reste) : `✓`/`✗` pour
  chaque vérification, code de sortie non nul si au moins une échoue (utilisable en script de
  déploiement, `niang doctor || exit 1`). `⚠` (sans faire échouer la commande) si `APP_DEBUG=true`
  alors que `APP_ENV=production`. La logique des vérifications est isolée dans
  `Commander::doctorChecks()` (pure, sans `exit()`) pour rester testable en process — `doctor()`
  se contente de l'afficher et de sortir avec le bon code.
  5 nouveaux tests (`tests/Unit/Console/CommanderDoctorTest.php`).
- **Debug toolbar** (P0 #15, NiangPro 2.0 — DX) : avec `APP_DEBUG=true` (défaut en développement),
  `Niang\Core\DebugToolbar` ajoute en bas de chaque page HTML le temps de réponse, le nombre de
  requêtes SQL exécutées (`DB::queryCount()`, désormais remis à zéro automatiquement au début de
  chaque requête par `Application::handle()`, plutôt que seulement via un appel manuel en test),
  le pic mémoire et le code de statut. Jamais injectée sur une réponse JSON, ni sans balise
  `</body>` où s'accrocher, ni si `APP_DEBUG=false` — jamais en production avec la config par
  défaut. Composé avec la compression gzip existante sans la casser (injection avant compression).
  9 nouveaux tests (`tests/Unit/DebugToolbarTest.php`, `tests/Feature/DebugToolbarTest.php`).
- **Authorization Policies** (P0 #15, NiangPro 2.0 — premier jalon) : `Gate::policy($prefix,
  $policyClass)` enregistre une classe pour plusieurs règles autour d'un même modèle
  (`'post.delete'` résout vers `PostPolicy::delete(Auth::user(), $post)`), en complément de
  `Gate::define()` (toujours prioritaire en cas de conflit de nom) qui reste la solution la plus
  simple pour une règle isolée. Les enregistrements restant de simples tableaux (pas d'objets), la
  policy se choisit par le préfixe explicite de l'ability, pas par inspection d'un type PHP qui
  n'existe pas.
  - `app/Policies/PostPolicy.php` remplace l'ancien `Gate::define('delete-post', ...)` dans
    `routes/web.php` — même règle, organisée différemment.
  - 7 nouveaux tests (`tests/Unit/GateTest.php`) et `tests/Feature/AuthorizationTest.php`, qui
    couvre pour la première fois `DELETE /posts/{id}` (invité redirigé, utilisateur connecté
    autorisé) — jusqu'ici sans aucun test malgré `Controller::authorize()` déjà en place depuis
    v0.4.0.

## [1.1.0] — 2026-09-17

### Added

- **Queue 2.0, Storage, Mail** (P0 #13 de la roadmap technique — périmètre volontairement sans
  Redis ni SMTP réel : les deux demanderaient une dépendance ou une extension externe, contraire
  au principe « sans dépendance d'implémentation à l'exécution » du framework, et un client SMTP
  écrit à la main n'aurait pas pu être vérifié contre un vrai serveur dans cet environnement) :
  - `Queue` : les jobs échoués sont désormais retentés jusqu'à `Job::$tries` fois (défaut 1) avec
    un backoff exponentiel (10s, 20s, 40s...), puis déplacés vers les jobs échoués plutôt que
    perdus silencieusement. `Queue::later($delaySeconds, $job)` pour différer un job. Nouveau :
    `Queue::failed()`, `Queue::retry($id)`, `Queue::flush()`, et les commandes
    `queue:failed`/`queue:retry`/`queue:flush`.
  - `Niang\Core\Storage` — disque local (`storage/app/`) : `put()`/`get()`/`exists()`/`delete()`/
    `size()`/`url()`. Les chemins contenant `..` sont rejetés (`InvalidArgumentException`) pour
    empêcher une traversée de répertoire à partir d'une entrée utilisateur.
  - `Niang\Core\Mail`/`Mailable`/`PendingMail` — `Mail::to($email)->send(new WelcomeMailable(...))`.
    Deux drivers pilotés par `MAIL_MAILER` : `log` (défaut, écrit dans `storage/logs/`, pratique en
    dev) et `array`/`Mail::fake()` (garde les emails en mémoire pour `Mail::sent()` dans les tests).
  - 24 nouveaux tests (`tests/Unit/QueueTest.php`, `tests/Unit/StorageTest.php`,
    `tests/Unit/MailTest.php`).
- **API layer : JSON Resources & CORS** (P0 #12 de la roadmap technique) :
  - `Niang\Core\Http\JsonResource` — enveloppe un enregistrement dans `{"data": ...}`, à surcharger
    (`toArray()`) pour choisir les champs exposés plutôt que de renvoyer l'enregistrement brut.
    `Resource::collection($items)` accepte un tableau ou un `Paginator` et ajoute `meta`
    (`current_page`/`last_page`/`per_page`/`total`) et `links` (`prev`/`next`) dans ce second cas.
    Pas de JSON:API complet : juste `data`/`meta`/`links`, la partie utile sans la complexité de
    la spec entière.
  - CORS configurable dans `config/cors.php` (`allowed_origins`, `allowed_methods`,
    `allowed_headers`, `exposed_headers`, `supports_credentials`, `max_age`) et appliqué via
    `App\Middleware\HandleCors`, qui répond directement au préflight `OPTIONS` (204, sans exécuter
    la route) et ajoute les en-têtes `Access-Control-*` à la vraie réponse. Avec
    `supports_credentials: true`, l'origine exacte est toujours reflétée (jamais `*`, que les
    navigateurs rejettent dans ce cas).
  - Démo câblée : `GET /api/posts` (`PostController::apiIndex()` + `App\Resources\PostResource`)
    sous le groupe `/api` avec `HandleCors`.
  - 12 nouveaux tests : logique CORS pure (`tests/Unit/CorsTest.php`), exécution via le middleware
    et le vrai routeur (`tests/Feature/CorsTest.php`), et la forme `data`/`meta`/`links`
    (`tests/Feature/JsonResourceTest.php`).
- **PSR-11 et PSR-3** (P0 #11 de la roadmap technique) :
  - `Container` implémente désormais `Psr\Container\ContainerInterface` (`get()` — alias de
    `make()` qui lève `ContainerNotFoundException` plutôt que la `ContainerException` générique
    quand l'identifiant n'est résoluble d'aucune façon — et `has()`). `ContainerException`
    implémente `ContainerExceptionInterface`.
  - Nouveau `Niang\Core\Logger`, implémentation `Psr\Log\LoggerInterface` qui délègue à
    `Niang\Core\Log` (même fichiers de sortie) — utilisable via injection de dépendances
    (`LoggerInterface $logger` dans un constructeur) plutôt que la façade statique, notamment pour
    interopérer avec du code tiers compatible PSR-3. `Application` enregistre `Container::class`/
    `ContainerInterface::class` et `Logger::class`/`LoggerInterface::class` comme singletons au
    démarrage.
  - Deux dépendances ajoutées à `composer.json` : `psr/container` et `psr/log` — deux paquets
    d'interfaces pures, sans code d'implémentation ni dépendance transitive. Le framework reste
    sans dépendance d'implémentation à l'exécution ; seules des interfaces standard sont ajoutées,
    pour l'interopérabilité.
  - 9 nouveaux tests (`tests/Unit/ContainerTest.php`, nouveau `tests/Unit/LoggerTest.php`).
- **ORM / pagination / relations** (P0 #10 de la roadmap technique) :
  - `QueryBuilder` : `distinct()`, `whereNull()`/`whereNotNull()`, `whereBetween()`/`whereNotBetween()`,
    `whereDate()`, `whereColumn()` (comparaison entre deux colonnes), `having()`/`havingRaw()`,
    `exists()`, `firstOrFail()` (lève `NotFoundException`), et les agrégats `sum()`/`avg()`/`min()`/
    `max()` ainsi que `count(string $column = '*')`.
  - `Model::findOrFail()` — équivalent de `find()` qui lève `NotFoundException` au lieu de renvoyer
    `null`.
  - **Eager loading** : `Model::with('relation')`/`with(['a', 'b'])->get()` (aussi `first()` et
    `paginate()`), qui charge chaque relation déclarée en une seule requête pour toute la collection
    via `whereIn`/jointure, au lieu d'une requête par enregistrement. Les relations disponibles se
    déclarent dans la classe fille via `eagerLoadable()`, en s'appuyant sur trois nouvelles méthodes
    protégées de `Model` : `loadMany()` (hasMany), `loadOne()` (belongsTo), `loadManyToMany()`
    (belongsToMany via pivot) — toutes basées sur des tableaux associatifs, pas d'objets hydratés,
    cohérent avec le choix d'architecture Active-Record-lite du framework.
  - `DB::queryCount()`/`DB::resetQueryCount()` — compteur de requêtes exécutées, ajouté spécifiquement
    pour *prouver* par un test qu'une correction N+1 fonctionne (plutôt que de le supposer).
  - 21 nouveaux tests : génération SQL pure (`tests/Unit/Database/QueryBuilderTest.php`), exécution
    réelle des nouvelles clauses/agrégats (`tests/Database/QueryBuilderExecutionTest.php`), et surtout
    `tests/Database/EagerLoadingTest.php` qui vérifie que `DB::queryCount()` reste constant (2 ou 3
    requêtes) quel que soit le nombre d'enregistrements chargés avec `with()`.
- **Validation 2.0** (P0 #9 de la roadmap technique) :
  - Nouvelles règles : `nullable`, `boolean`, `array`, `url`, `date`, `date_format`, `between`,
    `in`/`not_in`, `same`/`different`, `required_if`/`required_with`/`required_without`, et
    `unique`/`exists` (contre la base, `unique` accepte un id à ignorer pour les mises à jour).
  - Validation d'un tableau élément par élément : `'items.*.name' => 'required|string'`.
  - Messages et noms de champs personnalisables : `Validator::make($data, $rules, $messages,
    $attributes)`, `Controller::validate(..., messages: [...], attributes: [...])`, et
    `FormRequest::messages()`/`attributes()` (prévus par l'architecture FormRequest de la roadmap
    mais pas encore câblés jusqu'ici).
  - `AuthController::register()` simplifié : la vérification manuelle d'email déjà pris est
    remplacée par `'email' => 'unique:users,email'` — moins de code, même comportement (vérifié
    en direct : inscription en double toujours rejetée, avec le message personnalisé attendu).
  - 22 nouveaux tests (règles pures dans `tests/Unit/ValidatorTest.php`, `unique`/`exists` contre
    une vraie table dans `tests/Database/ValidatorDatabaseRulesTest.php`).
- **Router 2.0** (P0 #8 de la roadmap technique) :
  - `$router->match(['GET', 'POST'], $uri, $action)` — une seule action pour plusieurs méthodes ;
    `->name()`/`->where()` s'appliquent à toutes les routes enregistrées.
  - `$router->fallback($action)` — route de secours quand rien ne correspond, prioritaire sur la
    404 par défaut mais pas sur une 405 (une route existe, juste pas pour cette méthode). Survit à
    `route:cache` si l'action n'est pas une closure, comme les routes normales.
  - `$router->options(...)` et `$router->head(...)` pour un enregistrement explicite.
  - **`HEAD` fonctionne désormais automatiquement sur toute route `GET`** (corps vidé, en-têtes et
    statut conservés) sans rien déclarer — une route `HEAD` explicite garde, elle, entièrement la
    main sur sa réponse (testé : les deux comportements coexistent correctement).
  - `route:list` affiche aussi le fallback s'il y en a un.
- **Contrats du Container** (P0 #7 de la roadmap technique) : `Niang\Core\Exceptions\ContainerException`
  remplace les `\RuntimeException` génériques, avec des messages explicites et actionnables :
  - Classe introuvable, ou binding manquant sur une interface/classe abstraite (avec un rappel de
    la syntaxe `$container->bind(...)` à utiliser).
  - **Dépendances circulaires détectées** (`A -> B -> A`) au lieu de provoquer un débordement de
    pile — c'était un vrai trou avant : `make()` maintient maintenant une pile de résolution.
  - Paramètre scalaire manquant : nom, type et méthode/constructeur concerné dans le message.
  - `tests/Unit/ContainerTest.php` couvre les quatre cas (avec des classes de test dédiées pour la
    dépendance circulaire, vérifiant qu'elle ne fait plus planter le process).
- **CI multi-versions et multi-SGBD** (P0 #6 de la roadmap technique) :
  - Job `test` en matrice sur PHP 8.1, 8.2, 8.3 et 8.4.
  - Jobs `mysql` (MySQL 8) et `postgres` (PostgreSQL 16, conteneurs de service GitHub Actions) qui
    font vraiment tourner les migrations (`tests/Database/`) contre ces moteurs, pas seulement SQLite.

### Fixed

- **Les paramètres liés via `DB::select()`/`selectOne()`/`statement()` étaient tous transmis comme
  chaînes** (`PDOStatement::execute($bindings)` lie systématiquement en `PDO::PARAM_STR`, quel que
  soit le type PHP réel). Sans colonne pour absorber la conversion par affinité — typiquement une
  expression agrégée dans une clause `HAVING`, ex. `HAVING SUM(views) > ?` — SQLite compare alors un
  INTEGER à un TEXTE et le classe toujours avant, si bien qu'un `>` numériquement correct ne
  renvoyait aucune ligne. Trouvé en écrivant `QueryBuilderExecutionTest::test_having` (silencieux sur
  MySQL/PostgreSQL, qui font la conversion). Corrigé en liant chaque valeur explicitement avec
  `bindValue()` et le type `PDO::PARAM_*` correspondant à son type PHP, plutôt que via `execute()`.
- **`PostController::index()`/`page()` interrogeaient chaque post individuellement pour ses
  commentaires et ses tags** (`Post::comments($id)`/`Post::tags($id)` dans une boucle), soit 2N+1
  requêtes pour N posts. Remplacé par `Post::with(['comments', 'tags'])->get()`, qui ramène le total
  à 3 requêtes constantes quel que soit N — corrigé et prouvé par `EagerLoadingTest`, pas seulement
  supposé.
- **Bug réel trouvé en testant contre un vrai MySQL local avant de pousser en CI** :
  `Migrator::ensureTable()` créait la table interne `migrations` en SQL SQLite brut
  (`INTEGER PRIMARY KEY AUTOINCREMENT`), en contournant complètement le Grammar — échouait sur
  MySQL/PostgreSQL. Corrigé pour passer par `Schema`/`Blueprint` comme toute migration applicative.
- **`Env::get()` donnait la priorité au fichier `.env*` sur une variable d'environnement réelle** —
  à l'inverse de la convention habituelle (CI, Docker, hébergeurs injectent leurs propres variables,
  qui doivent gagner). Corrigé : une variable déjà présente dans l'environnement n'est plus jamais
  écrasée par `.env`/`.env.testing`. C'est ce qui permet à la CI de piloter `DB_CONNECTION` par job.
- **Le DSN PDO générique incluait `charset=` pour tous les moteurs non-SQLite**, y compris
  PostgreSQL — `PDO_PGSQL` ne reconnaît pas ce paramètre et refusait la connexion
  (`invalid connection option "charset"`). `charset` n'est plus ajouté qu'en MySQL ; le port par
  défaut est aussi désormais correct par moteur (3306 MySQL / 5432 PostgreSQL).
- **`composer.lock` verrouillait des outils dev (PHPUnit 11, PHP-CS-Fixer avec Symfony 8.1)
  nécessitant PHP 8.4+ en interne**, cassant `composer install` sur PHP 8.1/8.2/8.3 en CI — la
  contrainte `"php": ">=8.1"` de `composer.json` n'empêche pas Composer de verrouiller, lors d'un
  `composer update` local, des versions qui ne marchent que sur le PHP effectivement utilisé à ce
  moment-là. Corrigé avec `config.platform.php` fixé à `8.1.0` (force Composer à résoudre pour le
  minimum supporté, pas la version locale du contributeur) et un passage à PHPUnit 10 (dernière
  branche majeure compatible PHP 8.1).

- **Configuration & sécurité renforcée** (P0 #4 et #5 de la roadmap technique) :
  - `config/security.php` : en-têtes HTTP (CSP, HSTS, X-Frame-Options...) appliqués à toutes les
    réponses, désormais éditables sans toucher `Application`.
  - `config/session.php` : durée de vie du cookie de session, `SameSite`, `Secure` (auto-détecté
    selon HTTPS, ou forcé via `SESSION_SECURE_COOKIE`).
  - **Le cookie de session est maintenant réellement sécurisé** : `Session::start()` appelait
    `session_start()` sans configurer `HttpOnly`/`Secure`/`SameSite` — c'était une vraie lacune,
    corrigée via `session_set_cookie_params()`.
  - `Cookie::set()` porte désormais aussi le drapeau `Secure`.
  - `Niang\Core\ConfigCache` + `niang config:cache` / `config:clear` : fige `config/*.php` (et les
    `env()` qu'ils contiennent) dans un seul fichier pour la production — vérifié qu'il est bien
    pris en compte (en modifiant une valeur pendant que le cache est actif) et qu'il se vide
    correctement.
  - `niang optimize` met aussi en cache la configuration (en plus des routes).

- **Gestion des erreurs centralisée** (`Niang\Core\Exceptions`) : `Handler` unique appelé par
  `Application::handle()`, plus aucune logique de rendu d'erreur éparpillée dans le routeur, les
  middlewares ou les contrôleurs.
  - `HttpException` (+ statut, en-têtes personnalisés), `NotFoundException`,
    `AuthenticationException`, `DatabaseException`, `ConfigurationException`.
  - `abort(404)`, `abort(403, 'message personnalisé')`.
  - Réponse JSON automatique (`{"message": "..."}`) si le client l'attend, sinon page HTML dédiée
    (`resources/views/errors/{code}.php`) ou générique (`errors/generic.php`).
  - Page de debug enrichie en développement (exception, fichier:ligne, requête, route,
    utilisateur connecté, durée) ; jamais affichée si `APP_DEBUG=false`.
  - `\PDOException` toujours journalisée, jamais montrée telle quelle en production (SQL et
    chaîne de connexion jamais exposés).
  - `AuthorizationException` étend maintenant `HttpException` (403) ; `Router` lève
    `NotFoundException`/`HttpException(405)` au lieu de construire une réponse lui-même ;
    `VerifyCsrfToken` (419), `ThrottleRequests` (429) et `Authenticate` (401 JSON) font de même.

- `docs/ROADMAP_TECHNIQUE.md` : feuille de route technique complète (P0 à P3, jalons v1.1 → v2.0)
  adoptée comme référence pour la suite du développement.
- **Database Grammar** (`Niang\Core\Database\Grammar`) : couche de traduction SQL par moteur
  (`SQLiteGrammar`, `MySqlGrammar`, `PostgresGrammar`). Les migrations écrites avec `Blueprint`
  génèrent désormais du SQL correct pour SQLite, MySQL et PostgreSQL, sans changement d'API —
  `$table->id()`, `$table->string()`, etc. restent identiques quel que soit `DB_CONNECTION`.
- `Blueprint::decimal()`, `dateTime()`, `json()`.
- `Blueprint::unique()` et `index()` au niveau table (en plus du modificateur `->unique()` par colonne).
- `ColumnDefinition::constrained()` et `Blueprint::foreign()` : contraintes de clé étrangère
  (`references()->on()->cascadeOnDelete()`/`nullOnDelete()`/`restrictOnDelete()`).
- `Blueprint::renameColumn()` et `dropColumn()`, `Schema::rename()` (renommage de table).
- SQLite : activation de `PRAGMA foreign_keys = ON` à la connexion (désactivé par défaut par
  SQLite, contrairement à MySQL/PostgreSQL qui appliquent toujours les clés étrangères déclarées).
- `tests/Unit/Database/GrammarTest.php` (SQL généré par moteur) et `tests/Database/SchemaTest.php`
  (exécution réelle : création de table, contrainte FK appliquée, rename/drop de colonne).
- `np:install` : raccourci CLI global `np` (macOS/Linux) qui retrouve `bin/niang` depuis n'importe
  quel sous-dossier d'un projet NiangPro.
- **Isolation complète des tests** : `.env.testing` (committé) charge une base SQLite `:memory:` au
  lieu de `storage/database.sqlite` dès que `APP_ENV=testing` (positionné par `phpunit.xml`) ;
  `Niang\Core\Testing\TestCase` migre automatiquement cette base ; `tests/bootstrap.php` repart d'un
  `storage/framework/` propre à chaque run (le cache et le rate limiting sur fichier ne fuient plus
  d'une exécution de la suite à l'autre) ; nouveau trait `Niang\Core\Testing\RefreshDatabase`
  (transaction annulée après chaque test) pour les tests qui écrivent en base.
- `tests/Feature/AuthTest.php` démontre `RefreshDatabase` (un test vérifie explicitement qu'aucune
  trace du précédent ne subsiste).

### Changed

- `SchemaTest` étend désormais `Niang\Core\Testing\TestCase` (au lieu de `PHPUnit\Framework\TestCase`)
  pour charger `.env.testing` de façon fiable, indépendamment de l'ordre d'exécution des suites.
- CI : suppression de l'étape « Préparer l'environnement » (`.env` + `migrate`), devenue inutile —
  les tests s'auto-suffisent désormais.
- `phpstan.neon` analyse aussi `tests/` (niveau 6, toujours 0 erreur).

- `ColumnDefinition` et `Blueprint` ne construisent plus de SQL directement : ils décrivent la
  colonne de façon abstraite, et `Grammar::compile*()` traduit vers le SQL du moteur configuré.

**Écosystème (P0 #14)** : `composer create-project niangpro/framework` et `composer require
niangpro/framework` installaient encore le tag `v1.0.0` (2026-09-15), donc PHPUnit 11 (qui exige
PHP 8.3+) et aucune des fonctionnalités livrées depuis — vérifié en exécutant réellement la
commande, pas supposé. Les Service Providers et la publication Packagist existaient déjà et
fonctionnaient ; il manquait juste un nouveau tag. Cette version (1.1.0) comble cet écart.

## [1.0.0] — 2026-09-10

Première version publique. Voir la [release GitHub](https://github.com/NiangPro/niangpro/releases/tag/v1.0.0)
pour le détail complet (routeur, conteneur DI, Query Builder, migrations SQLite, auth, tests,
CI, etc.).
