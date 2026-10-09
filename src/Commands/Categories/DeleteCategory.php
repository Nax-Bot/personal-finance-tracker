<?php

namespace App\Commands\Categories;

use App\Repositories\Categories\CategoryWriteRepository;

class DeleteCategory
{

    public function __construct(
        private CategoryWriteRepository $categoryWriteRepository
    ) {}

    public function execute(int $id): void
    {
        $this->categoryWriteRepository->deleteCategory($id);
    }
}
