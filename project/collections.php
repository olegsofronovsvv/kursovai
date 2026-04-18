<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $message] = createCollection((string) ($_POST['title'] ?? ''), (string) ($_POST['description'] ?? ''));
    flash($ok ? 'success' : 'error', $message);
    redirect('collections.php');
}

$movies = movies();
$featuredCollections = defaultCollections();
$myCollections = userCollections();
$favorites = topMovies($movies, 3);
$quickCollections = array_slice($featuredCollections, 0, 3);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(pageTitle('Подборки')) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-shell">
        <?php renderHeader('collections'); ?>
        <?php renderFlash(); ?>

        <section class="collection-hero">
            <div class="hero-card" style="margin-top: 0;">
                <div>
                    <p class="eyebrow">Letterboxis · подборки</p>
                    <h1>Собирайте свои коллекции и переходите к фильмам одним кликом.</h1>
                    <p>Этот раздел читает реальные подборки из базы `SOFRONOV DB`, а новые списки сразу попадают в MySQL Open Server.</p>
                    <div class="hero-actions">
                        <a class="pill-button" href="index.php">На главную</a>
                        <?php if (currentUser() !== null): ?>
                            <span class="action-link secondary">Вы вошли как <?= h(currentUser()['name']) ?></span>
                        <?php else: ?>
                            <a class="action-link secondary" href="login.php">Войти, чтобы создавать свои</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-poster">
                    <div class="thumb-overlay hero-overlay">
                        <p class="eyebrow">Популярные темы</p>
                        <div class="poster-title">Списки из вашей базы данных</div>
                    </div>
                </div>
            </div>

            <aside class="mini-panel">
                <h2>Быстрые переходы</h2>
                <div class="list-links">
                    <?php foreach ($quickCollections as $collection): ?>
                        <a class="list-link" href="collection.php?id=<?= h($collection['id']) ?>">Подборка: "<?= h($collection['title']) ?>"</a>
                    <?php endforeach; ?>
                </div>
            </aside>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Создать подборку</h2>
                    <p>Форма записывает новую подборку прямо в таблицу `collections`.</p>
                </div>
            </div>
            <?php if (currentUser() !== null): ?>
                <form class="stack-form" method="post" style="margin-top: 22px; max-width: 620px;">
                    <div class="field">
                        <label for="title">Название подборки</label>
                        <input id="title" name="title" type="text" placeholder="Например, Кино на вечер">
                    </div>
                    <div class="field">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="4" placeholder="Коротко опишите настроение или тематику подборки"></textarea>
                    </div>
                    <button class="button primary" type="submit">Создать подборку</button>
                </form>
            <?php else: ?>
                <div class="empty-state" style="margin-top: 22px;">
                    Чтобы создавать свои подборки, сначала <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>.
                </div>
            <?php endif; ?>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Ваши подборки</h2>
                    <p>Этот блок показывает коллекции текущего пользователя из MySQL.</p>
                </div>
            </div>
            <div class="collection-grid" style="margin-top: 22px;">
                <?php if ($myCollections === []): ?>
                    <div class="empty-state">Личных подборок пока нет. Создайте первую, и она сразу появится в базе и на сайте.</div>
                <?php else: ?>
                    <?php foreach ($myCollections as $collection): ?>
                        <?php renderCollectionCard($collection, $movies); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Публичные подборки</h2>
                    <p>Эти коллекции загружаются из таблицы `collections`, где `Публичная = 1`.</p>
                </div>
            </div>
            <div class="collection-grid" style="margin-top: 22px;">
                <?php foreach ($featuredCollections as $collection): ?>
                    <?php renderCollectionCard($collection, $movies); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel detail-section" style="padding: 26px; border-radius: 28px;">
            <div class="section-head">
                <div>
                    <h2>Топ по рейтингу</h2>
                    <p>Топ фильмов берётся из вашей базы и сортируется по рейтингу.</p>
                </div>
            </div>
            <div class="favorites-grid" style="margin-top: 22px;">
                <?php foreach ($favorites as $movie): ?>
                    <?php renderMovieCard($movie); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <?php renderFooter(); ?>
    </div>
</body>
</html>
