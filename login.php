<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

requireGuest();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    [$ok, $message] = loginUser((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
    flash($ok ? 'success' : 'error', $message);
    redirect($ok ? 'index.php' : 'login.php');
}

renderAuthStart('Вход', 'Вход в аккаунт', 'Введите email или логин и пароль.');
?>

<form class="auth-form" method="post">
    <div class="field">
        <label for="email">Email или логин</label>
        <input id="email" name="email" type="text" required placeholder="you@example.com">
    </div>
    <div class="field">
        <label for="password">Пароль</label>
        <input id="password" name="password" type="password" required placeholder="Минимум 6 символов">
    </div>
    <button class="button primary" type="submit">Войти</button>
</form>

<div class="auth-switch">
    <a class="action-link secondary" href="index.php">На главную</a>
    <a class="action-link primary" href="register.php">Регистрация</a>
</div>

<?php renderAuthEnd(); ?>
