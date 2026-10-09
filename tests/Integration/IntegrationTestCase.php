<?php

declare(strict_types=1);

namespace Tests\Integration;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Classe de base : toutes les intégrations utilisent une base explicitement
 * réservée aux tests pour éviter de toucher aux données de l'application.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $projectRoot = dirname(__DIR__, 2);
        $envFile = $projectRoot . DIRECTORY_SEPARATOR . '.env.testing';

        if (!is_file($envFile)) {
            self::markTestSkipped('Crée .env.testing à partir de .env.testing.example pour activer les tests d’intégration.');
        }

        Dotenv::createImmutable($projectRoot, '.env.testing')->safeLoad();

        $databaseName = $_ENV['DB_DATABASE'] ?? '';
        if (!str_ends_with(strtolower($databaseName), '_test')) {
            self::fail('Sécurité : DB_DATABASE doit se terminer par _test. Utilise finance_tracker_test.');
        }

        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $username = $_ENV['DB_USERNAME'] ?? '';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        try {
            $this->pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$databaseName};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            self::markTestSkipped('Connexion à la base de test impossible : ' . $exception->getMessage());
        }
    }

    /** Génère un nom différent à chaque exécution pour éviter les collisions. */
    protected function uniqueName(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(5));
    }

    /** Envoie une requête HTTP réelle au serveur PHP de test et décode son JSON. */
    protected function httpRequest(string $method, string $path, ?array $body = null): array
    {
        $baseUrl = rtrim($_ENV['TEST_BASE_URL'] ?? '', '/');
        if ($baseUrl === '') {
            self::markTestSkipped('Définis TEST_BASE_URL dans .env.testing et démarre le serveur HTTP de test.');
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\n",
                'ignore_errors' => true,
                'timeout' => 5,
            ],
        ];
        if ($body !== null) {
            $options['http']['content'] = json_encode($body, JSON_THROW_ON_ERROR);
        }

        $response = @file_get_contents($baseUrl . $path, false, stream_context_create($options));
        if ($response === false) {
            self::markTestSkipped('Serveur HTTP inaccessible. Démarre php -S dans une autre fenêtre PowerShell.');
        }

        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);

        return [
            'status' => isset($matches[1]) ? (int) $matches[1] : 0,
            'json' => json_decode($response, true),
            'raw' => $response,
        ];
    }
}
