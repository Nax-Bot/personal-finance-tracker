<?php

declare(strict_types=1);

// Petit routeur pour le serveur PHP de développement. Chaque requête charge
// uniquement la configuration .env.testing et traverse les vraies routes API.
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/vendor/autoload.php';

\Dotenv\Dotenv::createImmutable($projectRoot, '.env.testing')->load();

// Route::get se sert de SCRIPT_NAME pour retrouver le chemin de /public.
$testBasePath = parse_url($_ENV['TEST_BASE_URL'] ?? '', PHP_URL_PATH) ?: '';
$_SERVER['SCRIPT_NAME'] = rtrim($testBasePath, '/') . '/index.php';

require $projectRoot . '/routes/api.php';
