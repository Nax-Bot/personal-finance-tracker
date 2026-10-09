<?php
// espace de noms pour organiser le code et éviter les conflits de noms
namespace App\Repositories\Account;

// importer la classe PDO pour interagir avec la base de données
use PDO;

class AccountWriteRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function create(string $name, string $type, float $balance): int
    {
        $request = 'INSERT INTO accounts (name, type, balance) VALUES (:name, :type, :balance)';

        $stmt = $this->connection->prepare($request);
        $stmt->execute(['name' => $name, 'type' => $type, 'balance' => $balance]);

        // lastInsertId() retourne l'ID de la dernière ligne insérée dans la base de données, ce qui est utile pour récupérer l'ID du compte nouvellement créé.
        return (int)$this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params['name'] = $data['name'];
        }
        if (isset($data['type'])) {
            $fields[] = 'type = :type';
            $params['type'] = $data['type'];
        }
        if (isset($data['balance'])) {
            $fields[] = 'balance = :balance';
            $params['balance'] = (float)$data['balance'];
        }

        if (empty($fields)) {
            return false; // Aucun champ à mettre à jour
        }

        // implode() est utilisé pour créer une chaîne de caractères à partir d'un tableau, en utilisant une virgule comme séparateur. Cela permet de construire dynamiquement la requête SQL en fonction des champs fournis dans $data.
        $request = 'UPDATE accounts SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->connection->prepare($request);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $request = 'DELETE FROM accounts WHERE id = :id';

        $stmt = $this->connection->prepare($request);

        return $stmt->execute(['id' => $id]);
    }
}
