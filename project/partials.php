<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function renderHeader(string $active = ''): void
{
    $user = currentUser();
    ?>
    <header class="site-header">
        <a class="brand" href="index.php">
            <span class="brand-mark">LB</span>
            <span><?= h(siteName()) ?></span>
        </a>
        <nav class="top-nav">
            <a class="nav-link <?= $active === 'catalog' ? 'active' : 'subtle' ?>" href="index.php">Каталог</a>
            <a class="nav-link <?= $active === 'collections' ? 'active' : 'subtle' ?>" href="collections.php">Подборки</a>
            <?php if ($user !== null): ?>
                <span class="nav-link subtle"><?= h($user['name']) ?></span>
                <a class="nav-link subtle" href="logout.php">Выйти</a>
            <?php else: ?>
                <a class="nav-link subtle" href="login.php">Вход</a>
                <a class="nav-link active" href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>
    <?php
}

function renderFooter(): void
{
    ?>
    <footer class="site-footer">
        <div class="footer-links">
            <a href="index.php">О сервисе</a>
            <a href="collections.php">Подборки</a>
            <a href="register.php">Аккаунт</a>
        </div>
        <p class="footer-copy"><?= h(siteName()) ?>. Фильмы, подборки и отзывы в одном месте.</p>
    </footer>
    <?php
}

function renderFlash(): void
{
    $flash = consumeFlash();
    if ($flash === null) {
        return;
    }
    ?>
    <div class="notice <?= h($flash['type']) ?>">
        <?= h($flash['message']) ?>
    </div>
    <?php
}

function renderMovieCard(array $movie): void
{
    ?>
    <article class="movie-card">
        <a class="movie-thumb" href="movie.php?id=<?= h($movie['id']) ?>" style="--poster-gradient: <?= h($movie['gradient']) ?>;">
            <?php if ($movie['poster'] !== ''): ?>
                <img class="poster-image" src="<?= h($movie['poster']) ?>" alt="<?= h($movie['title']) ?>">
            <?php endif; ?>
            <div class="thumb-overlay">
                <strong class="thumb-title"><?= h($movie['title']) ?></strong>
            </div>
        </a>
        <div class="movie-meta">
            <span><?= h($movie['genre']) ?></span>
            <span><?= h((string) $movie['year']) ?></span>
        </div>
        <h3><a href="movie.php?id=<?= h($movie['id']) ?>"><?= h($movie['title']) ?></a></h3>
        <p class="collection-subtitle"><?= h($movie['description']) ?></p>
        <div class="movie-meta">
            <span class="stars"><?= h(renderStars((float) $movie['rating'])) ?></span>
            <span><?= h(number_format((float) $movie['rating'], 1)) ?></span>
        </div>
    </article>
    <?php
}

function renderCollectionCard(array $collection, array $movies): void
{
    $count = count($collection['movie_ids'] ?? []);
    $firstMovie = $count > 0 ? ($movies[$collection['movie_ids'][0]] ?? null) : null;
    $gradient = $firstMovie['gradient'] ?? 'linear-gradient(135deg, #3f3047, #15121b)';
    ?>
    <article class="collection-card">
        <a class="collection-thumb" href="collection.php?id=<?= h($collection['id']) ?>" style="--poster-gradient: <?= h($gradient) ?>;">
            <?php if ($firstMovie !== null && $firstMovie['poster'] !== ''): ?>
                <img class="poster-image" src="<?= h($firstMovie['poster']) ?>" alt="<?= h($collection['title']) ?>">
            <?php endif; ?>
            <div class="thumb-overlay">
                <strong class="thumb-title"><?= h($collection['title']) ?></strong>
            </div>
        </a>
        <div class="collection-meta">
            <span><?= h((string) $count) ?> фильмов</span>
            <span><?= h($collection['owner'] ?? 'Редакция') ?></span>
        </div>
        <h3><a href="collection.php?id=<?= h($collection['id']) ?>"><?= h($collection['title']) ?></a></h3>
        <p class="collection-subtitle"><?= h($collection['description']) ?></p>
    </article>
    <?php
}
