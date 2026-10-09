<?php

namespace App\Commands\Categories;

use App\Repositories\Categories\CategoryWriteRepository;

class UpdateCategory
{
    public function __construct(
        private CategoryWriteRepository $repository
    ) {}

    public function execute(int $id, array $data): void
    {
        if (isset($data['name']) && trim($data['name']) === '') {
            throw new \InvalidArgumentException('Category name is required');
        }

        if (isset($data['monthly_limit'])) {
            if ((float)$data['monthly_limit'] < 0) {
                throw new \InvalidArgumentException('Monthly limit cannot be negative');
            }
            if (!is_numeric($data['monthly_limit'])) {
                throw new \InvalidArgumentException('Monthly limit must be a number');
            }
        }

        if (empty($data)) {
            throw new \InvalidArgumentException('No data provided for update');
        }


        $this->repository->updateCategory($id, $data);
    }
}
