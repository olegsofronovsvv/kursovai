<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Strict',
    ]);
    session_start();
}

const SITE_NAME = 'Letterboxis';
const ALL_GENRES = 'Все жанры';

define('DB_HOST', getenv('DB_HOST') ?: '127.0.1.30');
define('DB_NAME', getenv('DB_NAME') ?: 'SOFRONOV DB');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function siteName(): string
{
    return SITE_NAME;
}

function pageTitle(string $title): string
{
    return $title === '' ? SITE_NAME : $title . ' - ' . SITE_NAME;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}

function dbRows(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_NUM);
}

function dbExecute(string $sql, array $params = []): void
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
}

function textLower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function textPosition(string $haystack, string $needle)
{
    return function_exists('mb_strpos')
        ? mb_strpos($haystack, $needle, 0, 'UTF-8')
        : strpos($haystack, $needle);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function storedUsers(): array
{
    try {
        $users = [];
        foreach (dbRows('SELECT * FROM `users` ORDER BY 1') as $row) {
            $users[] = [
                'id' => (string) $row[0],
                'login' => (string) $row[1],
                'password' => (string) $row[2],
                'email' => (string) $row[3],
                'name' => (string) ($row[4] ?? $row[1]),
                'role' => ((int) $row[0] === 1) ? 'admin' : 'user',
            ];
        }

        return $users;
    } catch (PDOException $exception) {
        return [];
    }
}

function saveUsers(array $users): void
{
    // Users are stored in MySQL. This function is kept for old calls.
}

function currentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    foreach (storedUsers() as $user) {
        if ($user['id'] === (string) $_SESSION['user_id']) {
            return $user;
        }
    }

    unset($_SESSION['user_id']);
    return null;
}

function requireGuest(): void
{
    if (currentUser() !== null) {
        redirect('index.php');
    }
}

function logoutUser(): void
{
    unset($_SESSION['user_id']);
    unset($_SESSION['role']);
}

function loginUser(string $login, string $password): array
{
    $login = trim(textLower($login));

    foreach (storedUsers() as $user) {
        $email = textLower($user['email']);
        $name = textLower($user['name']);

        if (($login === $email || $login === $name) && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            return [true, 'Вы вошли в аккаунт.'];
        }
    }

    return [false, 'Неверный email или пароль.'];
}

function registerUser(string $name, string $email, string $password): array
{
    $name = trim($name);
    $email = trim(textLower($email));

    if ($name === '' || $email === '' || $password === '') {
        return [false, 'Заполните все поля.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Введите корректный email.'];
    }

    if (textLength($password) < 6) {
        return [false, 'Пароль должен быть не короче 6 символов.'];
    }

    try {
        foreach (storedUsers() as $user) {
            if (textLower($user['email']) === $email) {
                return [false, 'Пользователь с таким email уже существует.'];
            }

            if (textLower($user['login']) === textLower($name)) {
                return [false, 'Пользователь с таким логином уже существует.'];
            }
        }

        dbExecute(
            'INSERT INTO `users` VALUES (NULL, ?, ?, ?, ?, NOW())',
            [$name, password_hash($password, PASSWORD_DEFAULT), $email, $name]
        );
    } catch (PDOException $exception) {
        return [false, 'Не удалось сохранить аккаунт в базе данных. Проверьте, что MySQL запущен и база `' . DB_NAME . '` импортирована.'];
    }

    return [true, 'Аккаунт создан. Теперь войдите.'];
}

function requireAdmin(): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        redirect('login.php');
    }
}

function movieLocalAssets(string $id): array
{
    $assets = [
        '1' => ['poster' => 'assets/posters/1-inception.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Начало.mp4'],
        '2' => ['poster' => 'assets/posters/2-green-book.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Зеленая книга.mp4'],
        '3' => ['poster' => 'assets/posters/3-it.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Оно.mp4'],
        '4' => ['poster' => 'assets/posters/4-kholop.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Холоп.mp4'],
        '5' => ['poster' => 'assets/posters/5-gentlemen.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Джентельмены.mp4'],
        '6' => ['poster' => 'assets/posters/6-parasite.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Паразиты.mp4'],
        '7' => ['poster' => 'assets/posters/7-intouchables.svg', 'trailer' => 'ТРЕЙЛЕРЫ/1+1.mp4'],
        '8' => ['poster' => 'assets/posters/8-interstellar.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Интерстеллар.mp4'],
        '9' => ['poster' => 'assets/posters/9-coco.svg', 'trailer' => 'ТРЕЙЛЕРЫ/Тайна коко.mp4'],
    ];

    return $assets[$id] ?? ['poster' => '', 'trailer' => ''];
}

function movies(): array
{
    try {
        $genres = [];
        foreach (dbRows('SELECT * FROM `genres` ORDER BY 1') as $row) {
            $genres[(string) $row[0]] = (string) $row[1];
        }

        $movies = [];
        foreach (dbRows('SELECT * FROM `movies` ORDER BY 1') as $row) {
            $id = (string) $row[0];
            $genreId = (string) $row[3];
            $assets = movieLocalAssets($id);
            $rating = (float) ($row[7] ?? 0);

            $movies[$id] = [
                'id' => $id,
                'title' => (string) $row[1],
                'year' => (int) $row[2],
                'genre_id' => (int) $genreId,
                'genre' => $genres[$genreId] ?? 'Без жанра',
                'country' => (string) ($row[4] ?? ''),
                'description' => (string) ($row[5] ?? ''),
                'poster' => $assets['poster'],
                'trailer' => $assets['trailer'],
                'rating' => $rating > 5 ? round($rating / 2, 1) : $rating,
                'tags' => array_values(array_filter([$genres[$genreId] ?? '', (string) ($row[4] ?? '')])),
            ];
        }

        if ($movies !== []) {
            return $movies;
        }
    } catch (PDOException $exception) {
        // Fall back to local data if the database is not available.
    }

    return [
        '1' => [
            'id' => '1',
            'title' => 'Начало',
            'year' => 2010,
            'genre_id' => 5,
            'genre' => 'Фантастика',
            'country' => 'США',
            'description' => 'Профессиональный вор проникает в сны людей и получает шанс изменить прошлое.',
            'poster' => 'assets/posters/1-inception.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Начало.mp4',
            'rating' => 4.8,
            'tags' => ['сны', 'триллер', 'Нолан'],
        ],
        '2' => [
            'id' => '2',
            'title' => 'Зеленая книга',
            'year' => 2018,
            'genre_id' => 3,
            'genre' => 'Драма',
            'country' => 'США',
            'description' => 'История дорожного путешествия музыканта и водителя, которые становятся друзьями.',
            'poster' => 'assets/posters/2-green-book.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Зеленая книга.mp4',
            'rating' => 4.6,
            'tags' => ['дружба', 'путешествие', 'биография'],
        ],
        '3' => [
            'id' => '3',
            'title' => 'Оно',
            'year' => 2017,
            'genre_id' => 4,
            'genre' => 'Ужасы',
            'country' => 'США',
            'description' => 'Дети из маленького города сталкиваются с древним злом.',
            'poster' => 'assets/posters/3-it.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Оно.mp4',
            'rating' => 4.1,
            'tags' => ['хоррор', 'Стивен Кинг', 'мистика'],
        ],
        '4' => [
            'id' => '4',
            'title' => 'Холоп',
            'year' => 2019,
            'genre_id' => 2,
            'genre' => 'Комедия',
            'country' => 'Россия',
            'description' => 'Избалованный мажор попадает в необычный эксперимент по перевоспитанию.',
            'poster' => 'assets/posters/4-kholop.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Холоп.mp4',
            'rating' => 4.0,
            'tags' => ['комедия', 'Россия', 'перевоспитание'],
        ],
        '5' => [
            'id' => '5',
            'title' => 'Джентльмены',
            'year' => 2019,
            'genre_id' => 1,
            'genre' => 'Боевик',
            'country' => 'Великобритания',
            'description' => 'Криминальная комедия о сделке, вокруг которой начинается большая игра.',
            'poster' => 'assets/posters/5-gentlemen.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Джентельмены.mp4',
            'rating' => 4.5,
            'tags' => ['криминал', 'Гай Ричи', 'юмор'],
        ],
        '6' => [
            'id' => '6',
            'title' => 'Паразиты',
            'year' => 2019,
            'genre_id' => 3,
            'genre' => 'Драма',
            'country' => 'Южная Корея',
            'description' => 'Семья безработных постепенно внедряется в жизнь богатого дома.',
            'poster' => 'assets/posters/6-parasite.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Паразиты.mp4',
            'rating' => 4.7,
            'tags' => ['драма', 'сатира', 'Оскар'],
        ],
        '7' => [
            'id' => '7',
            'title' => '1+1',
            'year' => 2011,
            'genre_id' => 3,
            'genre' => 'Драма',
            'country' => 'Франция',
            'description' => 'Аристократ и его помощник из разных миров находят настоящую дружбу.',
            'poster' => 'assets/posters/7-intouchables.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/1+1.mp4',
            'rating' => 4.9,
            'tags' => ['дружба', 'Франция', 'комедия'],
        ],
        '8' => [
            'id' => '8',
            'title' => 'Интерстеллар',
            'year' => 2014,
            'genre_id' => 5,
            'genre' => 'Фантастика',
            'country' => 'США',
            'description' => 'Группа исследователей отправляется через космический туннель ради будущего человечества.',
            'poster' => 'assets/posters/8-interstellar.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Интерстеллар.mp4',
            'rating' => 4.9,
            'tags' => ['космос', 'Нолан', 'семья'],
        ],
        '9' => [
            'id' => '9',
            'title' => 'Тайна Коко',
            'year' => 2017,
            'genre_id' => 6,
            'genre' => 'Мультфильм',
            'country' => 'США',
            'description' => 'Мальчик Мигель отправляется в мир мертвых, чтобы узнать историю своей семьи.',
            'poster' => 'assets/posters/9-coco.svg',
            'trailer' => 'ТРЕЙЛЕРЫ/Тайна коко.mp4',
            'rating' => 4.8,
            'tags' => ['семья', 'музыка', 'Pixar'],
        ],
    ];
}

function genreOptions(): array
{
    $genres = [ALL_GENRES];
    foreach (movies() as $movie) {
        if (!in_array($movie['genre'], $genres, true)) {
            $genres[] = $movie['genre'];
        }
    }

    return $genres;
}

function filterMovies(array $movies, array $filters): array
{
    return array_filter($movies, function (array $movie) use ($filters): bool {
        $query = trim(textLower((string) ($filters['q'] ?? '')));
        if ($query !== '' && textPosition(textLower($movie['title']), $query) === false) {
            return false;
        }

        $genre = (string) ($filters['genre'] ?? ALL_GENRES);
        if ($genre !== ALL_GENRES && $movie['genre'] !== $genre) {
            return false;
        }

        $year = trim((string) ($filters['year'] ?? ''));
        if ($year !== '' && (int) $movie['year'] !== (int) $year) {
            return false;
        }

        $country = trim(textLower((string) ($filters['country'] ?? '')));
        if ($country !== '' && textPosition(textLower($movie['country']), $country) === false) {
            return false;
        }

        $rating = trim((string) ($filters['rating'] ?? ''));
        if ($rating !== '' && (float) $movie['rating'] < (float) $rating) {
            return false;
        }

        return true;
    });
}

function movieById(string $id): ?array
{
    $movies = movies();
    return $movies[$id] ?? null;
}

function similarMovies(string $movieId, int $genreId, int $limit): array
{
    $similar = [];

    foreach (movies() as $movie) {
        if ($movie['id'] !== $movieId && (int) $movie['genre_id'] === $genreId) {
            $similar[] = $movie;
        }
    }

    if (count($similar) < $limit) {
        foreach (movies() as $movie) {
            if ($movie['id'] !== $movieId && !in_array($movie, $similar, true)) {
                $similar[] = $movie;
            }
            if (count($similar) >= $limit) {
                break;
            }
        }
    }

    return array_slice($similar, 0, $limit);
}

function collectionMovieIdsFromDb(string $collectionId): array
{
    $items = [];
    foreach (dbRows('SELECT * FROM `movie_collections` ORDER BY 5, 1') as $row) {
        if ((string) $row[1] === $collectionId) {
            $items[] = (string) $row[2];
        }
    }

    return $items;
}

function collectionFromDbRow(array $row, array $usersById): array
{
    $ownerId = (string) $row[2];
    $owner = $usersById[$ownerId]['name'] ?? 'Пользователь';

    return [
        'id' => (string) $row[0],
        'title' => (string) $row[1],
        'description' => (string) ($row[4] ?? ''),
        'owner' => $owner,
        'movie_ids' => collectionMovieIdsFromDb((string) $row[0]),
        'public' => (int) ($row[5] ?? 1),
    ];
}

function fallbackCollections(): array
{
    return [
        [
            'id' => 'popular',
            'title' => 'Лучшие фильмы для вечера',
            'description' => 'Подборка заметных фильмов разных жанров.',
            'owner' => 'Редакция',
            'movie_ids' => ['1', '5', '8', '9'],
        ],
        [
            'id' => 'drama',
            'title' => 'Сильные драмы',
            'description' => 'Истории, которые держатся на персонажах и эмоциях.',
            'owner' => 'Редакция',
            'movie_ids' => ['2', '6', '7'],
        ],
    ];
}

function defaultCollections(): array
{
    try {
        $usersById = [];
        foreach (storedUsers() as $user) {
            $usersById[$user['id']] = $user;
        }

        $collections = [];
        foreach (dbRows('SELECT * FROM `collections` ORDER BY 1') as $row) {
            if ((int) ($row[5] ?? 1) === 1) {
                $collections[] = collectionFromDbRow($row, $usersById);
            }
        }

        return $collections !== [] ? $collections : fallbackCollections();
    } catch (PDOException $exception) {
        return fallbackCollections();
    }
}

function userCollections(): array
{
    $user = currentUser();
    if ($user === null) {
        return [];
    }

    try {
        $collections = [];
        $usersById = [$user['id'] => $user];
        foreach (dbRows('SELECT * FROM `collections` ORDER BY 1') as $row) {
            if ((string) $row[2] === $user['id']) {
                $collections[] = collectionFromDbRow($row, $usersById);
            }
        }

        return $collections;
    } catch (PDOException $exception) {
        return [];
    }
}

function saveUserCollections(array $collections): void
{
    // Collections are stored in MySQL. This function is kept for old calls.
}

function createCollection(string $title, string $description): array
{
    $user = currentUser();
    if ($user === null) {
        return [false, 'Войдите, чтобы создать подборку.'];
    }

    $title = trim($title);
    $description = trim($description);

    if ($title === '') {
        return [false, 'Введите название подборки.'];
    }

    try {
        dbExecute(
            'INSERT INTO `collections` VALUES (NULL, ?, ?, NOW(), ?, 1)',
            [$title, (int) $user['id'], $description === '' ? 'Личная подборка фильмов.' : $description]
        );
    } catch (PDOException $exception) {
        return [false, 'Не удалось создать подборку в базе данных.'];
    }

    return [true, 'Подборка создана.'];
}

function findCollection(string $id): ?array
{
    foreach (array_merge(defaultCollections(), userCollections()) as $collection) {
        if ($collection['id'] === $id) {
            return $collection;
        }
    }

    return null;
}

function collectionMovies(array $collection): array
{
    $movies = movies();
    $items = [];

    foreach ($collection['movie_ids'] ?? [] as $movieId) {
        if (isset($movies[$movieId])) {
            $items[] = $movies[$movieId];
        }
    }

    return $items;
}

function addMovieToUserCollection(string $collectionId, string $movieId): array
{
    if (currentUser() === null) {
        return [false, 'Войдите, чтобы добавлять фильмы.'];
    }

    if (movieById($movieId) === null) {
        return [false, 'Фильм не найден.'];
    }

    foreach (userCollections() as $collection) {
        if ($collection['id'] === $collectionId) {
            if (!in_array($movieId, $collection['movie_ids'], true)) {
                try {
                    dbExecute(
                        'INSERT INTO `movie_collections` VALUES (NULL, ?, ?, NOW(), ?)',
                        [(int) $collectionId, (int) $movieId, count($collection['movie_ids']) + 1]
                    );
                } catch (PDOException $exception) {
                    return [false, 'Не удалось добавить фильм в подборку.'];
                }
            }

            return [true, 'Фильм добавлен в подборку.'];
        }
    }

    return [false, 'Подборка не найдена.'];
}

function movieReviews(string $movieId): array
{
    try {
        $usersById = [];
        foreach (storedUsers() as $user) {
            $usersById[$user['id']] = $user;
        }

        $reviews = [];
        foreach (dbRows('SELECT * FROM `reviews` ORDER BY 6 DESC, 1 DESC') as $row) {
            if ((string) $row[2] !== $movieId) {
                continue;
            }

            $authorId = (string) $row[1];
            $reviews[] = [
                'author' => $usersById[$authorId]['name'] ?? 'Пользователь',
                'date' => substr((string) $row[5], 0, 10),
                'rating' => (int) $row[3],
                'likes' => (int) ($row[6] ?? 0),
                'text' => (string) $row[4],
            ];
        }

        if ($reviews !== []) {
            return $reviews;
        }
    } catch (PDOException $exception) {
        // Fall back to built-in examples if the database is temporarily unavailable.
    }

    $defaults = [
        '1' => [
            [
                'author' => 'Петр Иванов',
                'date' => '2024-04-01',
                'rating' => 5,
                'likes' => 15,
                'text' => 'Гениальный фильм, который хочется пересматривать.',
            ],
        ],
        '8' => [
            [
                'author' => 'Елена Петрова',
                'date' => '2024-04-02',
                'rating' => 5,
                'likes' => 18,
                'text' => 'Красивое и масштабное кино о семье, времени и космосе.',
            ],
        ],
    ];

    return $defaults[$movieId] ?? [];
}

function createReview(string $movieId, int $rating, string $text): array
{
    $user = currentUser();
    if ($user === null) {
        return [false, 'Войдите, чтобы оставить отзыв.'];
    }

    if (movieById($movieId) === null) {
        return [false, 'Фильм не найден.'];
    }

    $text = trim($text);
    if ($text === '') {
        return [false, 'Введите текст отзыва.'];
    }

    $rating = max(1, min(5, $rating));
    try {
        dbExecute(
            'INSERT INTO `reviews` VALUES (NULL, ?, ?, ?, ?, NOW(), 0)',
            [(int) $user['id'], (int) $movieId, $rating, $text]
        );
    } catch (PDOException $exception) {
        return [false, 'Не удалось сохранить отзыв в базе данных.'];
    }

    return [true, 'Отзыв опубликован.'];
}

function renderStars(float $rating): string
{
    $fullStars = (int) round($rating);
    $fullStars = max(0, min(5, $fullStars));

    return str_repeat('★', $fullStars) . str_repeat('☆', 5 - $fullStars);
}
