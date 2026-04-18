<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

requireGuest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $message] = loginUser((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
    flash($ok ? 'success' : 'error', $message);
    redirect($ok ? 'index.php' : 'login.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(pageTitle('Вход')) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-wrap">
        <section class="auth-card">
            <p class="eyebrow">Аккаунт</p>
            <h1>Вход в <?= h(siteName()) ?></h1>
            <p class="auth-note">Логин работает через таблицу `users` в базе `SOFRONOV DB`. Можно входить по email или логину.</p>
            <?php renderFlash(); ?>
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
        </section>
    </div>
</body>
</html>
