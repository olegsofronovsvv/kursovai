<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

$movies = movies();
$filters = [
    'q' => $_GET['q'] ?? '',
    'genre' => $_GET['genre'] ?? 'Все жанры',
    'year' => $_GET['year'] ?? '',
    'country' => $_GET['country'] ?? '',
    'rating' => $_GET['rating'] ?? '',
];

$filteredMovies = filterMovies($movies, $filters);
$featured = reset($movies);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(pageTitle('Каталог')) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-shell">
        <?php renderHeader('catalog'); ?>
        <?php renderFlash(); ?>

        <section class="hero-card">
            <div>
                <p class="eyebrow">Letterboxis · каталог</p>
                <h1>Собирайте подборки, оценивайте фильмы и находите кино под любое настроение.</h1>
                <p>Сайт уже работает поверх вашей базы `SOFRONOV DB`: фильмы, подборки, пользователи и отзывы берутся прямо из Open Server.</p>
                <div class="hero-actions">
                    <a class="pill-button" href="movie.php?id=<?= h($featured['id']) ?>">Открыть фильм</a>
                    <a class="action-link secondary" href="collections.php">Перейти в подборки</a>
                </div>
                <div class="hero-stats" style="margin-top: 18px;">
                    <span class="stat-chip"><?= h((string) count($movies)) ?> фильмов</span>
                    <span class="stat-chip">Постеры и карточки</span>
                    <span class="stat-chip">Отзывы и аккаунты</span>
                </div>
            </div>
            <div class="hero-poster" style="--poster-gradient: <?= h($featured['gradient']) ?>;">
                <?php if ($featured['poster'] !== ''): ?>
                    <img class="poster-image" src="<?= h($featured['poster']) ?>" alt="<?= h($featured['title']) ?>">
                <?php endif; ?>
                <div class="thumb-overlay hero-overlay">
                    <p class="eyebrow">Фильм недели</p>
                    <div class="poster-title"><?= h($featured['title']) ?></div>
                </div>
            </div>
        </section>

        <div class="page-grid">
            <aside class="sidebar-stack">
                <section class="sidebar-card">
                    <h3>Поиск и фильтр</h3>
                    <form class="filter-form" method="get">
                        <div class="field">
                            <label for="q">Поиск</label>
                            <input id="q" name="q" type="text" value="<?= h((string) $filters['q']) ?>" placeholder="Название фильма или жанр">
                        </div>
                        <div class="field">
                            <label for="genre">Жанр</label>
                            <select id="genre" name="genre">
                                <?php foreach (genreOptions() as $genre): ?>
                                    <option value="<?= h($genre) ?>" <?= $filters['genre'] === $genre ? 'selected' : '' ?>><?= h($genre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="year">Год</label>
                            <input id="year" name="year" type="number" min="1900" max="2030" value="<?= h((string) $filters['year']) ?>" placeholder="2019">
                        </div>
                        <div class="field">
                            <label for="country">Страна</label>
                            <input id="country" name="country" type="text" value="<?= h((string) $filters['country']) ?>" placeholder="США">
                        </div>
                        <div class="field">
                            <label for="rating">Оценка от</label>
                            <input id="rating" name="rating" type="number" step="0.1" min="0" max="5" value="<?= h((string) $filters['rating']) ?>" placeholder="4.0">
                        </div>
                        <button class="button primary" type="submit">Применить</button>
                    </form>
                </section>

                <section class="sidebar-card">
                    <h3>Аккаунт</h3>
                    <?php if (currentUser() !== null): ?>
                        <p class="auth-note">Вы вошли как <strong><?= h(currentUser()['name']) ?></strong>. Можно оставлять отзывы и собирать свои списки фильмов.</p>
                        <div class="header-actions">
                            <a class="button secondary" href="collections.php">Мои подборки</a>
                            <a class="button secondary" href="logout.php">Выйти</a>
                        </div>
                    <?php else: ?>
                        <p class="auth-note">Войдите, чтобы создавать подборки, публиковать отзывы и хранить всё в вашей MySQL-базе.</p>
                        <div class="header-actions">
                            <a class="button secondary" href="login.php">Вход</a>
                            <a class="button primary" href="register.php">Регистрация</a>
                        </div>
                    <?php endif; ?>
                </section>
            </aside>

            <main class="content-stack">
                <section class="panel" style="padding: 26px; border-radius: 28px;">
                    <div class="section-head">
                        <div>
                            <h2>Каталог фильмов</h2>
                            <p>Карточки открывают отдельную страницу фильма с постером, рейтингом, подборками и отзывами.</p>
                        </div>
                        <span class="tag">Найдено: <?= h((string) count($filteredMovies)) ?></span>
                    </div>
                    <div class="movie-grid" style="margin-top: 22px;">
                        <?php if ($filteredMovies === []): ?>
                            <div class="empty-state">По этим параметрам ничего не найдено. Попробуйте убрать часть фильтров.</div>
                        <?php else: ?>
                            <?php foreach ($filteredMovies as $movie): ?>
                                <?php renderMovieCard($movie); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>

        <?php renderFooter(); ?>
    </div>
</body>
</html>
