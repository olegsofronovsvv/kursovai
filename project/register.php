<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

requireGuest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $message] = registerUser(
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );
    flash($ok ? 'success' : 'error', $message);
    redirect($ok ? 'login.php' : 'register.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(pageTitle('Регистрация')) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-wrap">
        <section class="auth-card">
            <p class="eyebrow">Аккаунт</p>
            <h1>Создание аккаунта</h1>
            <p class="auth-note">Регистрация записывает пользователя в MySQL-базу `SOFRONOV DB`, поэтому новые аккаунты сразу видны в phpMyAdmin Open Server.</p>
            <?php renderFlash(); ?>
            <form class="auth-form" method="post">
                <div class="field">
                    <label for="name">Имя</label>
                    <input id="name" name="name" type="text" required placeholder="Ваше имя">
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required placeholder="you@example.com">
                </div>
                <div class="field">
                    <label for="password">Пароль</label>
                    <input id="password" name="password" type="password" required placeholder="Минимум 6 символов">
                </div>
                <button class="button primary" type="submit">Зарегистрироваться</button>
            </form>
            <div class="auth-switch">
                <a class="action-link secondary" href="index.php">На главную</a>
                <a class="action-link primary" href="login.php">Уже есть аккаунт</a>
            </div>
        </section>
    </div>
</body>
</html>
