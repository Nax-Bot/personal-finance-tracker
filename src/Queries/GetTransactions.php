<?php

namespace App\Queries;

use App\Repositories\Transaction\TransactionReadRepository;

class GetTransactions
{

    public function __construct(
        private TransactionReadRepository $repository
    ) {}

    public function execute(): array
    {
        return $this->repository->getAllTransactions();
    }

    public function executeById(int $id): ?array
    {
        return $this->repository->getTransactionById($id);
    }

    public function executeByCategory(int $category_id) : array
    {
        return $this->repository->getTransactionsByCategoryId($category_id);
    }

    public function executeByAccount(int $account_id) : array
    {
        return $this->repository->getTransactionsByAccountId($account_id);
    }
}
