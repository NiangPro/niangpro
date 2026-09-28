<?php layout('layouts.app', ['title' => '403']); ?>

<div style="text-align:center;">
    <h1 style="font-size:4rem;color:#facc15;">403</h1>
    <p><?= e(__('http.page_forbidden')) ?></p>
    <p><a href="/"><?= e(__('http.back_home')) ?></a></p>
</div>
