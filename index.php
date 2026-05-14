<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

$movies = movies();
$filters = [
    'q' => $_GET['q'] ?? '',
    'genre' => $_GET['genre'] ?? ALL_GENRES,
    'year' => $_GET['year'] ?? '',
    'country' => $_GET['country'] ?? '',
    'rating' => $_GET['rating'] ?? '',
];
$filteredMovies = filterMovies($movies, $filters);
$user = currentUser();

renderPageStart('Каталог', 'catalog');
?>

<div class="page-grid">
    <aside class="sidebar-stack">
        <section class="sidebar-card">
            <h3>Фильтр</h3>
            <form class="filter-form" method="get">
                <div class="field">
                    <label for="q">Название</label>
                    <input id="q" name="q" type="text" value="<?= h((string) $filters['q']) ?>" placeholder="Название фильма">
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
                    <label for="rating">Рейтинг от</label>
                    <input id="rating" name="rating" type="number" step="0.1" min="0" max="5" value="<?= h((string) $filters['rating']) ?>" placeholder="4.0">
                </div>
                <button class="button primary" type="submit">Применить</button>
            </form>
        </section>

        <section class="sidebar-card">
            <h3>Аккаунт</h3>
            <?php if ($user !== null): ?>
                <p class="auth-note">Вы вошли как <strong><?= h($user['name']) ?></strong>.</p>
                <div class="header-actions">
                    <a class="button secondary" href="collections.php">Мои подборки</a>
                    <a class="button secondary" href="logout.php">Выйти</a>
                </div>
            <?php else: ?>
                <p class="auth-note">Войдите, чтобы сохранять фильмы и писать отзывы.</p>
                <div class="header-actions">
                    <a class="button secondary" href="login.php">Вход</a>
                    <a class="button primary" href="register.php">Регистрация</a>
                </div>
            <?php endif; ?>
        </section>
    </aside>

    <main class="content-stack">
        <section class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow"><?= h(siteName()) ?></p>
                    <h1 class="page-title">Каталог фильмов</h1>
                    <p>Откройте фильм, чтобы посмотреть описание, трейлер и отзывы.</p>
                </div>
                <div class="section-head-actions">
                    <a class="action-link secondary" href="collections.php">Подборки</a>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="section-head">
                <div>
                    <h2>Фильмы</h2>
                    <p>Найдено: <?= h((string) count($filteredMovies)) ?></p>
                </div>
            </div>

            <div class="movie-grid top-space">
                <?php if ($filteredMovies === []): ?>
                    <div class="empty-state">Ничего не найдено. Измените фильтр.</div>
                <?php else: ?>
                    <?php foreach ($filteredMovies as $movie): ?>
                        <?php renderMovieCard($movie); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php renderPageEnd(); ?>
