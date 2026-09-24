<?php layout('layouts.app', ['title' => '404']); ?>

<div style="text-align:center;">
    <h1 style="font-size:4rem;">404</h1>
    <p><?= e(__('http.page_not_found')) ?></p>
    <p><a href="/"><?= e(__('http.back_home')) ?></a></p>
</div>
