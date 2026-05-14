<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

$movieId = (string) ($_GET['id'] ?? '');
$movie = movieById($movieId);

if ($movie === null) {
    flash('error', 'Фильм не найден.');
    redirect('index.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_to_collection') {
        [$ok, $message] = addMovieToUserCollection((string) ($_POST['collection_id'] ?? ''), $movieId);
        flash($ok ? 'success' : 'error', $message);
    }

    if ($action === 'add_review') {
        [$ok, $message] = createReview(
            $movieId,
            (int) ($_POST['rating'] ?? 0),
            (string) ($_POST['review_text'] ?? '')
        );
        flash($ok ? 'success' : 'error', $message);
    }

    redirect('movie.php?id=' . urlencode($movieId));
}

$collections = userCollections();
$similar = similarMovies($movie['id'], (int) $movie['genre_id'], 6);
$reviews = movieReviews($movieId);

renderPageStart($movie['title']);
?>

<section class="detail-card">
    <div class="detail-poster">
        <?php if ($movie['poster'] !== ''): ?>
            <img class="poster-image" src="<?= h($movie['poster']) ?>" alt="<?= h($movie['title']) ?>">
        <?php endif; ?>
        <div class="thumb-overlay hero-overlay">
            <p class="eyebrow">Фильм</p>
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

        <p class="detail-copy"><?= h($movie['description']) ?></p>

        <div class="meta-list">
            <span>Теги: <?= h(implode(', ', $movie['tags'])) ?></span>
        </div>

        <div class="detail-actions">
            <?php if (($movie['trailer'] ?? '') !== ''): ?>
                <a class="button primary" href="<?= h($movie['trailer']) ?>" target="_blank" rel="noopener noreferrer">Смотреть трейлер</a>
            <?php else: ?>
                <span class="button secondary disabled">Трейлер скоро</span>
            <?php endif; ?>

            <?php if (currentUser() !== null && $collections !== []): ?>
                <form class="stack-form form-limit-small" method="post">
                    <input type="hidden" name="action" value="add_to_collection">
                    <div class="field">
                        <label for="collection_id">Добавить в подборку</label>
                        <select id="collection_id" name="collection_id">
                            <?php foreach ($collections as $collection): ?>
                                <option value="<?= h($collection['id']) ?>"><?= h($collection['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="button primary" type="submit">Добавить</button>
                </form>
            <?php elseif (currentUser() !== null): ?>
                <div class="mini-panel">
                    <p class="auth-note">Сначала создайте подборку.</p>
                    <div class="header-actions">
                        <a class="button primary" href="collections.php">Создать подборку</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mini-panel">
                    <p class="auth-note">Войдите, чтобы сохранять фильмы и писать отзывы.</p>
                    <div class="header-actions">
                        <a class="button primary" href="login.php">Войти</a>
                        <a class="button secondary" href="register.php">Регистрация</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (($movie['trailer'] ?? '') !== ''): ?>
<section class="panel detail-section trailer-section">
    <div class="section-head">
        <div>
            <h2>Трейлер</h2>
            <p>Можно посмотреть прямо на странице фильма.</p>
        </div>
    </div>
    <div class="trailer-frame">
        <video controls preload="metadata" poster="<?= h($movie['poster']) ?>">
            <source src="<?= h($movie['trailer']) ?>" type="video/mp4">
        </video>
    </div>
</section>
<?php endif; ?>

<section class="panel detail-section">
    <div class="section-head">
        <div>
            <h2>Отзывы</h2>
            <p>Комментарии пользователей к фильму.</p>
        </div>
    </div>

    <?php if (currentUser() !== null): ?>
        <form class="stack-form review-form top-space" method="post">
            <input type="hidden" name="action" value="add_review">
            <div class="field">
                <label for="rating">Оценка</label>
                <select id="rating" name="rating">
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
            </div>
            <div class="field">
                <label for="review_text">Отзыв</label>
                <textarea id="review_text" name="review_text" rows="4" placeholder="Ваше мнение о фильме"></textarea>
            </div>
            <button class="button primary" type="submit">Опубликовать</button>
        </form>
    <?php endif; ?>

    <div class="review-list top-space">
        <?php if ($reviews === []): ?>
            <div class="empty-state">Отзывов пока нет.</div>
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

<section class="panel detail-section">
    <div class="section-head">
        <div>
            <h2>Похожие фильмы</h2>
            <p>Несколько фильмов того же жанра.</p>
        </div>
    </div>
    <div class="similar-grid top-space">
        <?php foreach ($similar as $similarMovie): ?>
            <?php renderMovieCard($similarMovie); ?>
        <?php endforeach; ?>
    </div>
</section>

<?php renderPageEnd(); ?>
