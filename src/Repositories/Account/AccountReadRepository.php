<?php
// espace de noms pour organiser le code et éviter les conflits de noms
namespace App\Repositories\Account;

// importer la classe PDO pour interagir avec la base de données
use PDO;

class AccountReadRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getAccountById(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM accounts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch();

        return $account ?: null;
    }

    public function getAllAccounts(): array
    {
        $stmt = $this->connection->query('SELECT * FROM accounts ORDER BY id DESC');
        return $stmt->fetchAll();
    }
}