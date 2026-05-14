<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

logoutUser();
flash('success', 'Вы вышли из аккаунта.');
redirect('index.php');
