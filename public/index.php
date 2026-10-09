<?php

declare(strict_types=1);

// Ce point d'entrée appartient au backend : il charge les routes de l'API.
require_once dirname(__DIR__) . '/vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

require dirname(__DIR__) . '/routes/api.php';
