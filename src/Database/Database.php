<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct()
    {
        // récupérer les informations de connexion à partir des variables d'environnement
        // $_ENV est un tableau associatif contenant les variables d'environnement chargées par Dotenv
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $database = $_ENV['DB_DATABASE'];
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];

        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        try {
            $this->connection = new PDO(
                $dsn,
                $username, 
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
                );
        } catch (PDOException $e) {
            throw new PDOException(
                "Database connection failed: " . $e->getMessage(), 
                (int)$e->getCode()
                );
        }
    }

    // fonction pour récupérer la connexion PDO
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
