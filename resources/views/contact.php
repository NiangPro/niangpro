<?php layout('layouts.app', ['title' => 'Contact']); ?>

<h1>Contact</h1>

<?php if ($message = flashed('success')): ?>
    <p class="success"><?= e($message) ?></p>
<?php endif; ?>

<form method="POST" action="<?= e(route('contact')) ?>">
    <?= csrf_field() ?>

    <label for="name">Nom</label>
    <input id="name" name="name" value="<?= e(old('name', '')) ?>">
    <?= component('components/field-errors', ['field' => 'name']) ?>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e(old('email', '')) ?>">
    <?= component('components/field-errors', ['field' => 'email']) ?>

    <label for="message">Message</label>
    <textarea id="message" name="message"><?= e(old('message', '')) ?></textarea>
    <?= component('components/field-errors', ['field' => 'message']) ?>

    <button type="submit">Envoyer</button>
</form>
