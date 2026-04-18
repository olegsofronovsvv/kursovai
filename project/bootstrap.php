<?php

declare(strict_types=1);

session_start();

const DB_HOST = 'MySQL-8.0';
const DB_PORT = 3306;
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'SOFRONOV DB';
const DB_SQL_FILE = __DIR__ . '/SOFRONOV_DB.sql';

function siteName(): string
{
    return 'Letterboxis';
}

function lower(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }

    return strtolower($value);
}

function containsText(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    return strpos($haystack, $needle) !== false;
}

function dbServer(): mysqli
{
    static $server = null;

    if ($server instanceof mysqli) {
        return $server;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $server = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($server->connect_errno) {
        throw new RuntimeException('Не удалось подключиться к MySQL Open Server.');
    }

    $server->set_charset('utf8mb4');

    return $server;
}

function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    ensureDatabaseReady();

    mysqli_report(MYSQLI_REPORT_OFF);
    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($connection->connect_errno) {
        throw new RuntimeException('Не удалось подключиться к базе данных сайта.');
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

function ensureDatabaseReady(): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    $server = dbServer();
    $server->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

    if (!tableExists('movies') && file_exists(DB_SQL_FILE)) {
        importSqlDump(DB_SQL_FILE);
    }

    if (!tableExists('movies')) {
        throw new RuntimeException('В базе нет таблиц сайта и не найден SOFRONOV_DB.sql для импорта.');
    }

    $ready = true;
    syncMoviePosters();
}

function tableExists(string $table): bool
{
    $server = dbServer();
    $safeTable = $server->real_escape_string($table);
    $safeDb = $server->real_escape_string(DB_NAME);
    $sql = "SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = '{$safeDb}' AND table_name = '{$safeTable}'";
    $result = $server->query($sql);
    if (!$result) {
        return false;
    }

    $row = $result->fetch_assoc();
    $result->free();

    return isset($row['total']) && (int) $row['total'] > 0;
}

function importSqlDump(string $path): void
{
    $server = dbServer();
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        return;
    }

    if (!$server->multi_query($sql)) {
        throw new RuntimeException('Не удалось импортировать SOFRONOV_DB.sql в MySQL.');
    }

    do {
        $result = $server->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($server->more_results() && $server->next_result());
}

function posterCatalog(): array
{
    return [
        1 => 'assets/posters/1-inception.svg',
        2 => 'assets/posters/2-green-book.svg',
        3 => 'assets/posters/3-it.svg',
        4 => 'assets/posters/4-kholop.svg',
        5 => 'assets/posters/5-gentlemen.svg',
        6 => 'assets/posters/6-parasite.svg',
        7 => 'assets/posters/7-intouchables.svg',
        8 => 'assets/posters/8-interstellar.svg',
    ];
}

function posterPathForMovie(int $movieId, string $storedPoster = ''): string
{
    $storedPoster = trim($storedPoster);
    if ($storedPoster !== '' && file_exists(__DIR__ . '/' . $storedPoster)) {
        return $storedPoster;
    }

    $catalog = posterCatalog();

    return isset($catalog[$movieId]) ? $catalog[$movieId] : '';
}

function syncMoviePosters(): void
{
    static $synced = false;

    if ($synced) {
        return;
    }

    $catalog = posterCatalog();
    $statement = db()->prepare("UPDATE `movies` SET `Постер` = ? WHERE `Код_фильма` = ?");

    foreach ($catalog as $movieId => $posterPath) {
        if (!file_exists(__DIR__ . '/' . $posterPath)) {
            continue;
        }

        $statement->bind_param('si', $posterPath, $movieId);
        $statement->execute();
    }

    $statement->close();
    $synced = true;
}

function movieGradient(int $movieId): string
{
    $gradients = [
        'linear-gradient(135deg, #6c4028, #1d203e)',
        'linear-gradient(135deg, #253255, #0a0d18)',
        'linear-gradient(135deg, #3d4d61, #12151f)',
        'linear-gradient(135deg, #35503b, #141b1a)',
        'linear-gradient(135deg, #7a1d4d, #20103a)',
        'linear-gradient(135deg, #355a68, #132027)',
        'linear-gradient(135deg, #5e3d2f, #14151d)',
        'linear-gradient(135deg, #2c6576, #1a2532)',
        'linear-gradient(135deg, #a55d3d, #402b2f)',
        'linear-gradient(135deg, #55413f, #17141a)',
    ];

    return $gradients[$movieId % count($gradients)];
}

function normalizeRating($value): float
{
    $rating = (float) $value;
    if ($rating > 5) {
        $rating = $rating / 2;
    }

    if ($rating < 0) {
        $rating = 0;
    }

    if ($rating > 5) {
        $rating = 5;
    }

    return round($rating, 1);
}

function buildMovie(array $row): array
{
    $movieId = (int) $row['Код_фильма'];
    $rating = normalizeRating($row['Рейтинг'] ?? 0);
    $description = trim((string) ($row['Описание'] ?? ''));

    if ($description === '') {
        $description = 'Описание фильма пока не заполнено в базе данных.';
    }

    return [
        'id' => (string) $movieId,
        'title' => (string) $row['Название'],
        'year' => (int) $row['Год'],
        'genre' => (string) ($row['Жанр'] ?? 'Без жанра'),
        'genre_id' => isset($row['Код_жанра']) ? (int) $row['Код_жанра'] : 0,
        'country' => (string) ($row['Страна'] ?? 'Не указана'),
        'description' => $description,
        'long_description' => $description,
        'poster' => posterPathForMovie($movieId, (string) ($row['Постер'] ?? '')),
        'rating' => $rating,
        'duration' => 'Длительность не указана',
        'gradient' => movieGradient($movieId),
        'tags' => [(string) ($row['Жанр'] ?? 'Без жанра'), (string) ($row['Страна'] ?? 'Не указана'), (string) $row['Год']],
    ];
}

function movies(): array
{
    $sql = "
        SELECT
            m.`Код_фильма`,
            m.`Название`,
            m.`Год`,
            m.`Код_жанра`,
            m.`Страна`,
            m.`Описание`,
            m.`Постер`,
            COALESCE(AVG(r.`Оценка`), m.`Рейтинг_средний`, 0) AS `Рейтинг`,
            g.`Название` AS `Жанр`
        FROM `movies` m
        LEFT JOIN `genres` g ON g.`Код_жанра` = m.`Код_жанра`
        LEFT JOIN `reviews` r ON r.`Код_фильма` = m.`Код_фильма`
        GROUP BY
            m.`Код_фильма`, m.`Название`, m.`Год`, m.`Код_жанра`, m.`Страна`,
            m.`Описание`, m.`Постер`, m.`Рейтинг_средний`, g.`Название`
        ORDER BY m.`Код_фильма`
    ";

    $result = db()->query($sql);
    $movies = [];

    while ($row = $result->fetch_assoc()) {
        $movie = buildMovie($row);
        $movies[$movie['id']] = $movie;
    }

    $result->free();

    return $movies;
}

function movieById(string $movieId): ?array
{
    $movieIdInt = (int) $movieId;
    $statement = db()->prepare("
        SELECT
            m.`Код_фильма`,
            m.`Название`,
            m.`Год`,
            m.`Код_жанра`,
            m.`Страна`,
            m.`Описание`,
            m.`Постер`,
            COALESCE(AVG(r.`Оценка`), m.`Рейтинг_средний`, 0) AS `Рейтинг`,
            g.`Название` AS `Жанр`
        FROM `movies` m
        LEFT JOIN `genres` g ON g.`Код_жанра` = m.`Код_жанра`
        LEFT JOIN `reviews` r ON r.`Код_фильма` = m.`Код_фильма`
        WHERE m.`Код_фильма` = ?
        GROUP BY
            m.`Код_фильма`, m.`Название`, m.`Год`, m.`Код_жанра`, m.`Страна`,
            m.`Описание`, m.`Постер`, m.`Рейтинг_средний`, g.`Название`
        LIMIT 1
    ");
    $statement->bind_param('i', $movieIdInt);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $statement->close();

    return $row ? buildMovie($row) : null;
}

function similarMovies(string $movieId, int $genreId, int $limit = 6): array
{
    $movieIdInt = (int) $movieId;
    $statement = db()->prepare("
        SELECT
            m.`Код_фильма`,
            m.`Название`,
            m.`Год`,
            m.`Код_жанра`,
            m.`Страна`,
            m.`Описание`,
            m.`Постер`,
            COALESCE(AVG(r.`Оценка`), m.`Рейтинг_средний`, 0) AS `Рейтинг`,
            g.`Название` AS `Жанр`
        FROM `movies` m
        LEFT JOIN `genres` g ON g.`Код_жанра` = m.`Код_жанра`
        LEFT JOIN `reviews` r ON r.`Код_фильма` = m.`Код_фильма`
        WHERE m.`Код_фильма` <> ? AND (? = 0 OR m.`Код_жанра` = ?)
        GROUP BY
            m.`Код_фильма`, m.`Название`, m.`Год`, m.`Код_жанра`, m.`Страна`,
            m.`Описание`, m.`Постер`, m.`Рейтинг_средний`, g.`Название`
        ORDER BY `Рейтинг` DESC, m.`Год` DESC
        LIMIT ?
    ");
    $statement->bind_param('iiii', $movieIdInt, $genreId, $genreId, $limit);
    $statement->execute();
    $result = $statement->get_result();

    $movies = [];
    while ($row = $result->fetch_assoc()) {
        $movie = buildMovie($row);
        $movies[$movie['id']] = $movie;
    }

    $result->free();
    $statement->close();

    return $movies;
}

function buildCollection(array $row): array
{
    $movieIds = [];
    if (!empty($row['movie_ids'])) {
        $movieIds = array_values(array_filter(array_map('trim', explode(',', (string) $row['movie_ids']))));
    }

    $description = trim((string) ($row['Описание'] ?? ''));
    if ($description === '') {
        $description = 'Описание подборки пока не заполнено.';
    }

    return [
        'id' => (string) $row['Код_подборки'],
        'title' => (string) $row['Название'],
        'description' => $description,
        'owner' => (string) ($row['Имя'] ?? 'Пользователь'),
        'is_public' => isset($row['Публичная']) ? (int) $row['Публичная'] === 1 : true,
        'movie_ids' => $movieIds,
    ];
}

function loadCollectionsFromQuery(string $sql): array
{
    $result = db()->query($sql);
    $collections = [];

    while ($row = $result->fetch_assoc()) {
        $collection = buildCollection($row);
        $collections[$collection['id']] = $collection;
    }

    $result->free();

    return $collections;
}

function defaultCollections(): array
{
    $sql = "
        SELECT
            c.`Код_подборки`,
            c.`Название`,
            c.`Описание`,
            c.`Публичная`,
            u.`Имя`,
            GROUP_CONCAT(mc.`Код_фильма` ORDER BY mc.`Порядок`, mc.`Дата_добавления`, mc.`Код_записи`) AS movie_ids
        FROM `collections` c
        INNER JOIN `users` u ON u.`Код_пользователя` = c.`Код_пользователя`
        LEFT JOIN `movie_collections` mc ON mc.`Код_подборки` = c.`Код_подборки`
        WHERE c.`Публичная` = 1
        GROUP BY c.`Код_подборки`, c.`Название`, c.`Описание`, c.`Публичная`, u.`Имя`
        ORDER BY c.`Дата_создания` DESC, c.`Код_подборки` DESC
    ";

    return loadCollectionsFromQuery($sql);
}

function userCollections(): array
{
    $user = currentUser();
    if ($user === null) {
        return [];
    }

    $statement = db()->prepare("
        SELECT
            c.`Код_подборки`,
            c.`Название`,
            c.`Описание`,
            c.`Публичная`,
            u.`Имя`,
            GROUP_CONCAT(mc.`Код_фильма` ORDER BY mc.`Порядок`, mc.`Дата_добавления`, mc.`Код_записи`) AS movie_ids
        FROM `collections` c
        INNER JOIN `users` u ON u.`Код_пользователя` = c.`Код_пользователя`
        LEFT JOIN `movie_collections` mc ON mc.`Код_подборки` = c.`Код_подборки`
        WHERE c.`Код_пользователя` = ?
        GROUP BY c.`Код_подборки`, c.`Название`, c.`Описание`, c.`Публичная`, u.`Имя`
        ORDER BY c.`Дата_создания` DESC, c.`Код_подборки` DESC
    ");
    $statement->bind_param('i', $user['id']);
    $statement->execute();
    $result = $statement->get_result();

    $collections = [];
    while ($row = $result->fetch_assoc()) {
        $collection = buildCollection($row);
        $collections[$collection['id']] = $collection;
    }

    $result->free();
    $statement->close();

    return $collections;
}

function collectionMovies(array $collection): array
{
    $allMovies = movies();
    $items = [];

    foreach ($collection['movie_ids'] as $movieId) {
        if (isset($allMovies[$movieId])) {
            $items[$movieId] = $allMovies[$movieId];
        }
    }

    return $items;
}

function genreOptions(): array
{
    $options = ['Все жанры'];
    $result = db()->query("SELECT `Название` FROM `genres` ORDER BY `Название`");
    while ($row = $result->fetch_assoc()) {
        $options[] = (string) $row['Название'];
    }
    $result->free();

    return $options;
}

function currentUser(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function requireGuest(): void
{
    if (currentUser() !== null) {
        redirect('index.php');
    }
}

function requireAuth(): void
{
    if (currentUser() === null) {
        flash('error', 'Сначала войдите в аккаунт, чтобы пользоваться подборками.');
        redirect('login.php');
    }
}

function findUserByEmail(string $email): ?array
{
    $identifier = lower(trim($email));
    $statement = db()->prepare("SELECT * FROM `users` WHERE LOWER(`Email`) = ? LIMIT 1");
    $statement->bind_param('s', $identifier);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $statement->close();

    return $row ?: null;
}

function findUserByEmailOrLogin(string $identifier): ?array
{
    $value = lower(trim($identifier));
    $statement = db()->prepare("SELECT * FROM `users` WHERE LOWER(`Email`) = ? OR LOWER(`Логин`) = ? LIMIT 1");
    $statement->bind_param('ss', $value, $value);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $statement->close();

    return $row ?: null;
}

function uniqueLoginFromEmail(string $email): string
{
    $base = lower((string) preg_replace('/[^a-z0-9_]+/i', '_', strstr($email, '@', true) ?: $email));
    $base = trim($base, '_');
    if ($base === '') {
        $base = 'user';
    }

    $login = $base;
    $index = 1;

    while (findUserByEmailOrLogin($login) !== null) {
        ++$index;
        $login = $base . '_' . $index;
    }

    return $login;
}

function registerUser(string $name, string $email, string $password): array
{
    $name = trim($name);
    $email = lower(trim($email));

    if ($name === '' || $email === '' || $password === '') {
        return [false, 'Заполните все поля.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Укажите корректный email.'];
    }

    $passwordLength = function_exists('mb_strlen') ? mb_strlen($password) : strlen($password);
    if ($passwordLength < 6) {
        return [false, 'Пароль должен содержать минимум 6 символов.'];
    }

    if (findUserByEmail($email) !== null) {
        return [false, 'Пользователь с таким email уже существует.'];
    }

    $login = uniqueLoginFromEmail($email);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $registeredAt = date('Y-m-d H:i:s');

    $statement = db()->prepare("
        INSERT INTO `users` (`Логин`, `Пароль`, `Email`, `Имя`, `Дата_регистрации`)
        VALUES (?, ?, ?, ?, ?)
    ");
    $statement->bind_param('sssss', $login, $passwordHash, $email, $name, $registeredAt);
    $ok = $statement->execute();
    $statement->close();

    if (!$ok) {
        return [false, 'Не удалось зарегистрировать пользователя в базе данных.'];
    }

    return [true, 'Регистрация прошла успешно. Теперь войдите в аккаунт.'];
}

function loginUser(string $identifier, string $password): array
{
    $user = findUserByEmailOrLogin($identifier);
    if ($user === null) {
        return [false, 'Неверный email или пароль.'];
    }

    $storedPassword = (string) ($user['Пароль'] ?? '');
    $passwordMatches = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

    if (!$passwordMatches) {
        return [false, 'Неверный email или пароль.'];
    }

    $_SESSION['user'] = [
        'id' => (int) $user['Код_пользователя'],
        'name' => (string) ($user['Имя'] ?: $user['Логин']),
        'email' => (string) $user['Email'],
        'login' => (string) $user['Логин'],
    ];

    return [true, 'Добро пожаловать, ' . $_SESSION['user']['name'] . '!'];
}

function logoutUser(): void
{
    unset($_SESSION['user']);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function consumeFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function renderStars(float $rating): string
{
    $filled = (int) round($rating);

    return str_repeat('★', $filled) . str_repeat('☆', max(0, 5 - $filled));
}

function topMovies(array $movies, int $limit = 6): array
{
    uasort($movies, function (array $left, array $right): int {
        if ($left['rating'] === $right['rating']) {
            return 0;
        }

        return ($left['rating'] < $right['rating']) ? 1 : -1;
    });

    return array_slice($movies, 0, $limit, true);
}

function filterMovies(array $movies, array $filters): array
{
    return array_filter($movies, function (array $movie) use ($filters): bool {
        $query = trim((string) ($filters['q'] ?? ''));
        $genre = (string) ($filters['genre'] ?? 'Все жанры');
        $year = trim((string) ($filters['year'] ?? ''));
        $country = trim((string) ($filters['country'] ?? ''));
        $rating = trim((string) ($filters['rating'] ?? ''));

        if ($query !== '') {
            $haystack = lower($movie['title'] . ' ' . $movie['description'] . ' ' . implode(' ', $movie['tags']));
            if (!containsText($haystack, lower($query))) {
                return false;
            }
        }

        if ($genre !== '' && $genre !== 'Все жанры' && $movie['genre'] !== $genre) {
            return false;
        }

        if ($year !== '' && (string) $movie['year'] !== $year) {
            return false;
        }

        if ($country !== '' && !containsText(lower($movie['country']), lower($country))) {
            return false;
        }

        if ($rating !== '' && $movie['rating'] < (float) $rating) {
            return false;
        }

        return true;
    });
}

function createCollection(string $title, string $description = ''): array
{
    requireAuth();

    $title = trim($title);
    $description = trim($description);
    if ($title === '') {
        return [false, 'Название подборки не может быть пустым.'];
    }

    $user = currentUser();
    $createdAt = date('Y-m-d H:i:s');
    $isPublic = 1;

    $statement = db()->prepare("
        INSERT INTO `collections` (`Название`, `Код_пользователя`, `Дата_создания`, `Описание`, `Публичная`)
        VALUES (?, ?, ?, ?, ?)
    ");
    $statement->bind_param('sissi', $title, $user['id'], $createdAt, $description, $isPublic);
    $ok = $statement->execute();
    $statement->close();

    if (!$ok) {
        return [false, 'Не удалось создать подборку в базе данных.'];
    }

    return [true, 'Подборка "' . $title . '" создана.'];
}

function addMovieToUserCollection(string $collectionId, string $movieId): array
{
    requireAuth();

    $user = currentUser();
    $collectionIdInt = (int) $collectionId;
    $movieIdInt = (int) $movieId;

    $statement = db()->prepare("
        SELECT `Код_подборки`, `Название`
        FROM `collections`
        WHERE `Код_подборки` = ? AND `Код_пользователя` = ?
        LIMIT 1
    ");
    $statement->bind_param('ii', $collectionIdInt, $user['id']);
    $statement->execute();
    $result = $statement->get_result();
    $collection = $result->fetch_assoc();
    $result->free();
    $statement->close();

    if (!$collection) {
        return [false, 'Подборка не найдена или не принадлежит вам.'];
    }

    $check = db()->prepare("
        SELECT `Код_записи`
        FROM `movie_collections`
        WHERE `Код_подборки` = ? AND `Код_фильма` = ?
        LIMIT 1
    ");
    $check->bind_param('ii', $collectionIdInt, $movieIdInt);
    $check->execute();
    $checkResult = $check->get_result();
    $exists = $checkResult->fetch_assoc();
    $checkResult->free();
    $check->close();

    if ($exists) {
        return [false, 'Этот фильм уже есть в выбранной подборке.'];
    }

    $createdAt = date('Y-m-d H:i:s');
    $insert = db()->prepare("
        INSERT INTO `movie_collections` (`Код_подборки`, `Код_фильма`, `Дата_добавления`)
        VALUES (?, ?, ?)
    ");
    $insert->bind_param('iis', $collectionIdInt, $movieIdInt, $createdAt);
    $ok = $insert->execute();
    $insert->close();

    if (!$ok) {
        return [false, 'Не удалось добавить фильм в подборку.'];
    }

    return [true, 'Фильм добавлен в подборку "' . $collection['Название'] . '".'];
}

function movieReviews(string $movieId): array
{
    $movieIdInt = (int) $movieId;
    $statement = db()->prepare("
        SELECT
            r.`Код_рецензии`,
            r.`Оценка`,
            r.`Текст`,
            r.`Дата`,
            r.`Лайков`,
            u.`Имя`,
            u.`Логин`
        FROM `reviews` r
        INNER JOIN `users` u ON u.`Код_пользователя` = r.`Код_пользователя`
        WHERE r.`Код_фильма` = ?
        ORDER BY r.`Дата` DESC, r.`Код_рецензии` DESC
    ");
    $statement->bind_param('i', $movieIdInt);
    $statement->execute();
    $result = $statement->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = [
            'id' => (int) $row['Код_рецензии'],
            'rating' => (int) $row['Оценка'],
            'text' => (string) $row['Текст'],
            'date' => (string) $row['Дата'],
            'likes' => (int) $row['Лайков'],
            'author' => (string) ($row['Имя'] ?: $row['Логин']),
        ];
    }

    $result->free();
    $statement->close();

    return $reviews;
}

function createReview(string $movieId, int $rating, string $text): array
{
    requireAuth();

    $movieIdInt = (int) $movieId;
    $text = trim($text);

    if ($rating < 1 || $rating > 5) {
        return [false, 'Оценка должна быть от 1 до 5.'];
    }

    if ($text === '') {
        return [false, 'Напишите короткий отзыв.'];
    }

    $user = currentUser();
    $createdAt = date('Y-m-d H:i:s');
    $likes = 0;

    $statement = db()->prepare("
        INSERT INTO `reviews` (`Код_пользователя`, `Код_фильма`, `Оценка`, `Текст`, `Дата`, `Лайков`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $statement->bind_param('iiissi', $user['id'], $movieIdInt, $rating, $text, $createdAt, $likes);
    $ok = $statement->execute();
    $statement->close();

    if (!$ok) {
        return [false, 'Не удалось сохранить отзыв в базе данных.'];
    }

    return [true, 'Отзыв опубликован.'];
}

function findCollection(string $id): ?array
{
    $collectionId = (int) $id;
    if ($collectionId <= 0) {
        return null;
    }

    $sql = "
        SELECT
            c.`Код_подборки`,
            c.`Название`,
            c.`Описание`,
            c.`Публичная`,
            u.`Имя`,
            u.`Код_пользователя`,
            GROUP_CONCAT(mc.`Код_фильма` ORDER BY mc.`Порядок`, mc.`Дата_добавления`, mc.`Код_записи`) AS movie_ids
        FROM `collections` c
        INNER JOIN `users` u ON u.`Код_пользователя` = c.`Код_пользователя`
        LEFT JOIN `movie_collections` mc ON mc.`Код_подборки` = c.`Код_подборки`
        WHERE c.`Код_подборки` = ?
        GROUP BY c.`Код_подборки`, c.`Название`, c.`Описание`, c.`Публичная`, u.`Имя`, u.`Код_пользователя`
        LIMIT 1
    ";

    $statement = db()->prepare($sql);
    $statement->bind_param('i', $collectionId);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $statement->close();

    if (!$row) {
        return null;
    }

    if ((int) $row['Публичная'] !== 1) {
        $user = currentUser();
        if ($user === null || (int) $row['Код_пользователя'] !== (int) $user['id']) {
            return null;
        }
    }

    return buildCollection($row);
}

function pageTitle(string $title): string
{
    return $title . ' | ' . siteName();
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
