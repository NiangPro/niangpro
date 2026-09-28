<?php layout('layouts.app', ['title' => (string) $status]); ?>

<div style="text-align:center;">
    <h1 style="font-size:4rem;"><?= e((string) $status) ?></h1>
    <p><?= e($message) ?></p>
    <p><a href="/"><?= e(__('http.back_home')) ?></a></p>
</div>
