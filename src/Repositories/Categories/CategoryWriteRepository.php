<?php

namespace App\Repositories\Categories;

use PDO;
use PDOException;
use RuntimeException;

class CategoryWriteRepository
{

    public function __construct(
        private PDO $connection
    ) {}

    public function createCategory(array $data): int
    {
        try {
            $request = 'INSERT INTO categories (name, monthly_limit) VALUES (:name, :monthly_limit)';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':monthly_limit', $data['monthly_limit'], PDO::PARAM_STR);
            $stmt->execute();

            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to create category: ' . $e->getMessage());
        }
    }

    public function updateCategory(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        try {
            if (isset($data['name'])) {
                $fields[] = 'name = :name';
                $params['name'] = $data['name'];
            }
            if (isset($data['monthly_limit'])) {
                $fields[] = 'monthly_limit = :monthly_limit';
                $params['monthly_limit'] = $data['monthly_limit'];
            }

            $request = 'UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = :id';

            $stmt = $this->connection->prepare($request);

            // Lier les paramètres dynamiquement en fonction des champs fournis dans $data
            // PDO::PARAM_INT est utilisé pour les entiers et PDO::PARAM_STR pour les chaînes de caractères.
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to update category: ' . $e->getMessage());
        }
    }

    public function deleteCategory(int $id): bool
    {
        try {
            $request = 'DELETE FROM categories WHERE id = :id';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to delete category: ' . $e->getMessage());
        }
    }
}
