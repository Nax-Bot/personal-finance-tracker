<?php

namespace App\Repositories\Categories;

use PDO;
use PDOException;
use RuntimeException;

class CategoryReadRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getAllCategories(): array
    {
        try {
            $request ='SELECT * FROM categories';

            $stmt = $this->connection->query($request);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch categories: ' . $e->getMessage());
        }
    }

    public function getCategoryById(int $id): ?array
    {
        try {
            $request = 'SELECT * FROM categories WHERE id = :id';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch category: ' . $e->getMessage());
        }
    }

    public function findByName(string $name): ?array
    {
        try {
            $request = 'SELECT * FROM categories WHERE name = :name';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch category by name: ' . $e->getMessage());
        }
    }
}