<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

requireGuest();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    [$ok, $message] = registerUser(
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );
    flash($ok ? 'success' : 'error', $message);
    redirect($ok ? 'login.php' : 'register.php');
}

renderAuthStart('Регистрация', 'Создать аккаунт', 'Заполните форму и войдите в систему.');
?>

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

<?php renderAuthEnd(); ?>
