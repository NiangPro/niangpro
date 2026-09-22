// Outil jetable (voir README.md de ce dossier) : vérifie le rendu de chaque thème de site sur
// Chromium, Firefox et WebKit. Ne fait partie d'aucun `composer test` — un projet NiangPro sans
// Node.js n'a besoin de rien de ce dossier.
//
// Prérequis : un serveur PHP par thème, déjà démarré (voir README.md pour les commandes exactes),
// sur les ports ci-dessous.
import { chromium, firefox, webkit } from 'playwright';

const SITES = [
    { slug: 'minimal', port: 8101 },
    { slug: 'vitrine', port: 8102 },
    { slug: 'ecommerce', port: 8103 },
    { slug: 'blog', port: 8104 },
    { slug: 'portfolio', port: 8105 },
    { slug: 'landing', port: 8106 },
];

const ENGINES = [
    ['chromium', chromium],
    ['firefox', firefox],
    ['webkit', webkit], // seul moyen réaliste de tester le rendu Safari sans Mac
];

let failures = 0;
let checks = 0;

function report(label, ok, detail = '') {
    checks++;
    if (ok) {
        console.log(`  ✓ ${label}`);
    } else {
        failures++;
        console.error(`  ✗ ${label}${detail ? ` — ${detail}` : ''}`);
    }
}

async function checkPageResponds(page, baseUrl, path) {
    const response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded' });
    report(`${path} répond 200`, response && response.status() === 200, `statut ${response?.status()}`);
}

async function checkBurgerMenu(page, baseUrl) {
    await page.setViewportSize({ width: 390, height: 844 }); // largeur mobile : le menu burger n'apparaît qu'en dessous de 55.99em
    await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded' });

    const toggle = page.locator('[data-nav-toggle]');
    await toggle.waitFor({ state: 'visible' });
    await toggle.click();

    const header = page.locator('.site-header');
    const isOpen = await header.evaluate((el) => el.classList.contains('is-open'));
    report('le menu burger ouvre la navigation (.site-header.is-open)', isOpen);

    await toggle.click();
    const isClosedAgain = await header.evaluate((el) => !el.classList.contains('is-open'));
    report('le menu burger se referme', isClosedAgain);

    await page.setViewportSize({ width: 1280, height: 800 });
}

async function checkContactForm(page, baseUrl) {
    await page.goto(`${baseUrl}/contact`, { waitUntil: 'domcontentloaded' });

    await page.locator('#name').fill('Awa Diop');
    await page.locator('#email').fill('awa@example.test');
    await page.locator('#message').fill('Bonjour, ceci est un test de bout en bout Playwright.');
    await page.locator('form.card button[type="submit"]').click();

    await page.waitForLoadState('domcontentloaded');
    const success = page.locator('.alert--success');
    report('la soumission du formulaire de contact affiche le message de succès', await success.isVisible().catch(() => false));
}

async function checkFaqDisclosure(page, baseUrl) {
    await page.goto(`${baseUrl}/faq`, { waitUntil: 'domcontentloaded' });

    // La première question est ouverte par défaut (openFirst) : on cible la deuxième (fermée) par
    // un index FIXE — un sélecteur dynamique comme :not([open]) se ré-évaluerait après le clic
    // (l'élément visé n'étant alors plus :not([open])) et la vérification porterait sur un tout
    // autre <details>, silencieusement.
    const details = page.locator('.faq details').nth(1);
    await details.locator('summary').click();

    const isOpen = await details.evaluate((el) => el.hasAttribute('open'));
    report('un clic sur une question FAQ ouvre le <details> (open)', isOpen);
}

async function checkDarkModeRendering(page, baseUrl) {
    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded' });
    const lightBg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);

    await page.emulateMedia({ colorScheme: 'dark' });
    await page.reload({ waitUntil: 'domcontentloaded' });
    const darkBg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);

    report('le fond change bien entre prefers-color-scheme light et dark', lightBg !== darkBg, `${lightBg} vs ${darkBg}`);
}

async function run() {
    for (const [engineName, engine] of ENGINES) {
        console.log(`\n=== ${engineName} ===`);
        const browser = await engine.launch();

        for (const { slug, port } of SITES) {
            const baseUrl = `http://127.0.0.1:${port}`;
            console.log(`-- ${slug} (${baseUrl}) --`);

            const context = await browser.newContext();
            const page = await context.newPage();

            try {
                await checkPageResponds(page, baseUrl, '/');
                await checkPageResponds(page, baseUrl, '/up');
                await checkPageResponds(page, baseUrl, '/health');

                // Interactions détaillées sur un seul thème représentatif (vitrine : menu + FAQ +
                // formulaire de contact, tous fournis par le design system partagé) — suffisant
                // pour prouver que niang.js/niang.css fonctionnent cross-browser, sans répéter
                // 6 fois la même vérification d'un même mécanisme commun à tous les thèmes.
                if (slug === 'vitrine') {
                    await checkBurgerMenu(page, baseUrl);
                    await checkContactForm(page, baseUrl);
                    await checkFaqDisclosure(page, baseUrl);
                    await checkDarkModeRendering(page, baseUrl);
                }
            } catch (error) {
                failures++;
                console.error(`  ✗ erreur inattendue sur ${slug}/${engineName} : ${error.message}`);
            } finally {
                await context.close();
            }
        }

        await browser.close();
    }

    console.log(`\n${checks - failures}/${checks} vérifications réussies.`);

    if (failures > 0) {
        process.exitCode = 1;
    }
}

await run();
