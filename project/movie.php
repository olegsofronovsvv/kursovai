<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

$movieId = (string) ($_GET['id'] ?? '');
$movie = movieById($movieId);

if ($movie === null) {
    flash('error', 'Фильм не найден.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_to_collection') {
        $targetCollection = (string) ($_POST['collection_id'] ?? '');
        [$ok, $message] = addMovieToUserCollection($targetCollection, $movieId);
        flash($ok ? 'success' : 'error', $message);
    }

    if ($action === 'add_review') {
        $rating = (int) ($_POST['rating'] ?? 0);
        $text = (string) ($_POST['review_text'] ?? '');
        [$ok, $message] = createReview($movieId, $rating, $text);
        flash($ok ? 'success' : 'error', $message);
    }

    redirect('movie.php?id=' . urlencode($movieId));
}

$userChoices = userCollections();
$similarMovies = similarMovies($movie['id'], (int) $movie['genre_id'], 6);
$reviews = movieReviews($movieId);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(pageTitle($movie['title'])) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-shell">
        <?php renderHeader(); ?>
        <?php renderFlash(); ?>

        <section class="detail-card">
            <div class="detail-poster" style="--poster-gradient: <?= h($movie['gradient']) ?>;">
                <?php if ($movie['poster'] !== ''): ?>
                    <img class="poster-image" src="<?= h($movie['poster']) ?>" alt="<?= h($movie['title']) ?>">
                <?php endif; ?>
                <div class="thumb-overlay hero-overlay">
                    <p class="eyebrow">Страница фильма</p>
                    <div class="poster-title"><?= h($movie['title']) ?></div>
                </div>
            </div>
            <div class="detail-body">
                <div class="meta-row">
                    <a class="action-link secondary" href="index.php">Назад</a>
                    <a class="action-link secondary" href="collections.php">Подборки</a>
                </div>
                <h1><?= h($movie['title']) ?></h1>
                <div class="chips">
                    <span class="rating-tag"><?= h(renderStars((float) $movie['rating'])) ?> · <?= h(number_format((float) $movie['rating'], 1)) ?></span>
                    <span class="tag"><?= h($movie['genre']) ?></span>
                    <span class="tag"><?= h((string) $movie['year']) ?></span>
                    <span class="tag"><?= h($movie['country']) ?></span>
                </div>
                <p class="detail-copy"><?= h($movie['long_description']) ?></p>
                <div class="meta-list">
                    <span>Жанры и теги: <?= h(implode(', ', $movie['tags'])) ?></span>
                    <span>Источник данных: MySQL база `SOFRONOV DB`</span>
                </div>

                <div class="detail-actions">
                    <?php if (currentUser() !== null && $userChoices !== []): ?>
                        <form class="stack-form" method="post" style="width: min(420px, 100%);">
                            <input type="hidden" name="action" value="add_to_collection">
                            <div class="field">
                                <label for="collection_id">Добавить в подборку</label>
                                <select id="collection_id" name="collection_id">
                                    <?php foreach ($userChoices as $collection): ?>
                                        <option value="<?= h($collection['id']) ?>"><?= h($collection['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="button primary" type="submit">Добавить в подборку</button>
                        </form>
                    <?php elseif (currentUser() !== null): ?>
                        <div class="mini-panel">
                            <p class="auth-note">Сначала создайте свою подборку, а затем сможете добавлять туда фильмы.</p>
                            <div class="header-actions">
                                <a class="button primary" href="collections.php">Создать подборку</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mini-panel">
                            <p class="auth-note">Чтобы сохранять фильмы и писать отзывы, войдите в аккаунт.</p>
                            <div class="header-actions">
                                <a class="button primary" href="login.php">Войти</a>
                                <a class="button secondary" href="register.php">Регистрация</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Отзывы</h2>
                    <p>Отзывы хранятся в таблице `reviews` и сразу меняются в вашей базе данных.</p>
                </div>
            </div>

            <?php if (currentUser() !== null): ?>
                <form class="stack-form review-form" method="post" style="margin-top: 22px;">
                    <input type="hidden" name="action" value="add_review">
                    <div class="field">
                        <label for="rating">Ваша оценка</label>
                        <select id="rating" name="rating">
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="review_text">Ваш отзыв</label>
                        <textarea id="review_text" name="review_text" rows="4" placeholder="Что вам понравилось в фильме?"></textarea>
                    </div>
                    <button class="button primary" type="submit">Опубликовать отзыв</button>
                </form>
            <?php endif; ?>

            <div class="review-list" style="margin-top: 22px;">
                <?php if ($reviews === []): ?>
                    <div class="empty-state">Отзывов пока нет. Станьте первым, кто оценит этот фильм.</div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <div class="movie-meta">
                                <strong><?= h($review['author']) ?></strong>
                                <span><?= h($review['date']) ?></span>
                            </div>
                            <div class="movie-meta">
                                <span class="stars"><?= h(renderStars((float) $review['rating'])) ?></span>
                                <span><?= h((string) $review['likes']) ?> лайков</span>
                            </div>
                            <p class="collection-subtitle"><?= h($review['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Похожие фильмы</h2>
                    <p>Рекомендации подбираются по жанру и рейтингу из вашей базы.</p>
                </div>
            </div>
            <div class="similar-grid" style="margin-top: 22px;">
                <?php foreach ($similarMovies as $similarMovie): ?>
                    <?php renderMovieCard($similarMovie); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <?php renderFooter(); ?>
    </div>
</body>
</html>
