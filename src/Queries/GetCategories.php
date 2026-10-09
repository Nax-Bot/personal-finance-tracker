<?php

namespace App\Queries;

use App\Repositories\Categories\CategoryReadRepository;

class GetCategories
{
    private CategoryReadRepository $repository;

    public function __construct(CategoryReadRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        return $this->repository->getAllCategories();
    }

    public function executeById(int $id): ?array
    {
        return $this->repository->getCategoryById($id);
    }

    public function executeByName(string $name): ?array
    {
        return $this->repository->findByName($name);
    }
}