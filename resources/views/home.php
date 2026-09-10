<?php layout('layouts.app', ['title' => $title]); ?>

<h1><?= e($framework) ?></h1>
<p>Votre framework tourne. Bienvenue !</p>
<p>Modifiez <code>routes/web.php</code> et <code>app/Controllers/HomeController.php</code> pour commencer.</p>
<p>Essayez aussi <a href="/hello/monde">/hello/monde</a>, <a href="/posts">/posts</a> (JSON) ou <a href="/blog">/blog</a> (pagination).</p>

<?php if (\Niang\Core\Auth::check()): ?>
    <?php $user = \Niang\Core\Auth::user(); ?>
    <p>Connecté en tant que <strong><?= e($user['name']) ?></strong>.
        <form method="POST" action="/logout" style="display:inline">
            <?= csrf_field() ?>
            <button type="submit" style="background:none;border:none;color:var(--accent);cursor:pointer;font:inherit;padding:0;margin:0;">Se déconnecter</button>
        </form>
    </p>
<?php else: ?>
    <p><a href="<?= e(route('login')) ?>">Se connecter</a> ou <a href="<?= e(route('register')) ?>">créer un compte</a>.</p>
<?php endif; ?>
