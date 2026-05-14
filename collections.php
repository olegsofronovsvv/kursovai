<?php

declare(strict_types=1);

require_once __DIR__ . '/partials.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    [$ok, $message] = createCollection((string) ($_POST['title'] ?? ''), (string) ($_POST['description'] ?? ''));
    flash($ok ? 'success' : 'error', $message);
    redirect('collections.php');
}

$movies = movies();
$publicCollections = defaultCollections();
$userCollections = userCollections();

renderPageStart('Подборки', 'collections');
?>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow"><?= h(siteName()) ?></p>
            <h1 class="page-title">Подборки</h1>
            <p>Создавайте свои списки фильмов и открывайте готовые подборки.</p>
        </div>
        <div class="section-head-actions">
            <a class="action-link secondary" href="index.php">Каталог</a>
        </div>
    </div>
</section>

<section class="panel detail-section">
    <div class="section-head">
        <div>
            <h2>Новая подборка</h2>
            <p>Короткое название и описание.</p>
        </div>
    </div>

    <?php if (currentUser() !== null): ?>
        <form class="stack-form top-space form-limit" method="post">
            <div class="field">
                <label for="title">Название</label>
                <input id="title" name="title" type="text" placeholder="Кино на вечер">
            </div>
            <div class="field">
                <label for="description">Описание</label>
                <textarea id="description" name="description" rows="4" placeholder="О чем эта подборка"></textarea>
            </div>
            <button class="button primary" type="submit">Создать</button>
        </form>
    <?php else: ?>
        <div class="empty-state top-space">Чтобы создать подборку, войдите или зарегистрируйтесь.</div>
    <?php endif; ?>
</section>

<section class="panel detail-section">
    <div class="section-head">
        <div>
            <h2>Мои подборки</h2>
            <p>Личные списки текущего пользователя.</p>
        </div>
    </div>

    <div class="collection-grid top-space">
        <?php if ($userCollections === []): ?>
            <div class="empty-state">У вас пока нет подборок.</div>
        <?php else: ?>
            <?php foreach ($userCollections as $collection): ?>
                <?php renderCollectionCard($collection, $movies); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="panel detail-section">
    <div class="section-head">
        <div>
            <h2>Публичные подборки</h2>
            <p>Доступны всем пользователям.</p>
        </div>
    </div>

    <div class="collection-grid top-space">
        <?php foreach ($publicCollections as $collection): ?>
            <?php renderCollectionCard($collection, $movies); ?>
        <?php endforeach; ?>
    </div>
</section>

<?php renderPageEnd(); ?>
