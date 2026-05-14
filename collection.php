<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

$collection = findCollection((string) ($_GET['id'] ?? ''));
if ($collection === null) {
    flash('error', 'Подборка не найдена.');
    redirect('collections.php');
}

$items = collectionMovies($collection);

renderPageStart($collection['title'], 'collections');
?>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Подборка</p>
            <h1 class="page-title"><?= h($collection['title']) ?></h1>
            <p><?= h($collection['description']) ?></p>
        </div>
        <div class="section-head-actions">
            <a class="action-link secondary" href="collections.php">Назад</a>
            <a class="action-link secondary" href="index.php">Каталог</a>
        </div>
    </div>

    <div class="hero-stats top-space">
        <span class="stat-chip"><?= h((string) count($items)) ?> фильмов</span>
        <span class="stat-chip"><?= h($collection['owner']) ?></span>
    </div>
</section>

<section class="panel detail-section">
    <div class="section-head">
        <div>
            <h2>Фильмы</h2>
            <p>Все фильмы из этой подборки.</p>
        </div>
    </div>

    <div class="movie-grid top-space">
        <?php if ($items === []): ?>
            <div class="empty-state">В этой подборке пока нет фильмов.</div>
        <?php else: ?>
            <?php foreach ($items as $movie): ?>
                <?php renderMovieCard($movie); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php renderPageEnd(); ?>
