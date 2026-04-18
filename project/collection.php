<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

$collectionId = (string) ($_GET['id'] ?? '');
$collection = findCollection($collectionId);

if ($collection === null) {
    flash('error', 'Подборка не найдена.');
    redirect('collections.php');
}

$items = collectionMovies($collection);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(pageTitle($collection['title'])) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-shell">
        <?php renderHeader('collections'); ?>
        <?php renderFlash(); ?>

        <section class="hero-card">
            <div>
                <p class="eyebrow">Подборка</p>
                <h1><?= h($collection['title']) ?></h1>
                <p><?= h($collection['description']) ?></p>
                <div class="hero-stats" style="margin-top: 18px;">
                    <span class="stat-chip"><?= h((string) count($items)) ?> фильмов</span>
                    <span class="stat-chip"><?= h($collection['owner']) ?></span>
                </div>
                <div class="hero-actions">
                    <a class="pill-button" href="collections.php">Назад к подборкам</a>
                    <a class="action-link secondary" href="index.php">Каталог</a>
                </div>
            </div>
            <div class="hero-poster">
                <?php $firstMovie = reset($items); ?>
                <?php if ($firstMovie !== false && $firstMovie['poster'] !== ''): ?>
                    <img class="poster-image" src="<?= h($firstMovie['poster']) ?>" alt="<?= h($collection['title']) ?>">
                <?php endif; ?>
                <div class="thumb-overlay hero-overlay">
                    <p class="eyebrow">Коллекция</p>
                    <div class="poster-title"><?= h($collection['title']) ?></div>
                </div>
            </div>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Фильмы в подборке</h2>
                    <p>Каждая карточка ведёт на страницу фильма и показывает уже добавленный постер.</p>
                </div>
            </div>
            <div class="movie-grid" style="margin-top: 22px;">
                <?php if ($items === []): ?>
                    <div class="empty-state">В этой подборке пока нет фильмов.</div>
                <?php else: ?>
                    <?php foreach ($items as $movie): ?>
                        <?php renderMovieCard($movie); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php renderFooter(); ?>
    </div>
</body>
</html>
