<?php
// On doit encore ajouter la logique metier pour gerer une doublon de nom de categorie, et la logique pour gerer les categories avec des limites mensuelles negatives

namespace App\Controllers;

// Commandes
use App\Commands\Categories\CreateCategory;
use App\Commands\Categories\DeleteCategory;
use App\Commands\Categories\UpdateCategory;

use App\Queries\GetCategories;

use App\Repositories\Categories\CategoryReadRepository;
use App\Repositories\Categories\CategoryWriteRepository;

use App\Controllers\JsonResponse;

use PDO;

use InvalidArgumentException;
use RuntimeException;

class CategoryController
{
    public function __construct(
        private PDO $connection
    ) {}

    public function index(): void
    {
        $repository = new CategoryReadRepository($this->connection);
        $query = new GetCategories($repository);
        $categories = $query->execute();

        JsonResponse::send([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function show(int $id): void
    {
        $repository = new CategoryReadRepository($this->connection);
        $query = new GetCategories($repository);
        $category = $query->executeById($id);

        if (!$category) {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => 'Category not found'
                ],
                404
            );
            return;
        }

        JsonResponse::send([
            'success' => true,
            'data' => $category
        ]);
    }

    public function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            
            $repositoryRead = new CategoryReadRepository($this->connection);
            $commandRead = new GetCategories($repositoryRead);
            $categoryName = $commandRead->executeByName($data['name'] ?? '');

            if($categoryName) {
                JsonResponse::send(
                    [
                        'success' => false,
                        'error' => 'Category name already exists'
                    ],
                    400
                );
                return;
            }


            $repository = new CategoryWriteRepository($this->connection);
            $command = new CreateCategory($repository);
            $categoryId = $command->execute($data);


            JsonResponse::send([
                'success' => true,
                'message' => 'Category created successfully',
                'category_id' => $categoryId
            ]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                400
            );
        }
    }

    public function update(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // validation des données d'entrée en tant que tableau associatif
        if (!is_array($data)) {
            // Bad Request (400) si les données d'entrée ne sont pas valides
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => 'Invalid JSON input'
                ],
                400
            );
            return;
        }
        try {
            $repository = new CategoryWriteRepository($this->connection);
            $command = new UpdateCategory($repository);
            $command->execute($id, $data);

            JsonResponse::send([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                400
            );
        } catch (RuntimeException $e) {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    public function delete(int $id): void
    {
        try {
            $repository = new CategoryWriteRepository($this->connection);
            $command = new DeleteCategory($repository);
            $command->execute($id);

            JsonResponse::send([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (RuntimeException $e) {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }
}
