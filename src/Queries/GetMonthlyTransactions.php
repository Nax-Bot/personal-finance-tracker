<?php

namespace App\Queries;

use App\Repositories\Transaction\TransactionReadRepository;

class GetMonthlyTransactions
{
    public function __construct(
        private TransactionReadRepository $repository
    ) {
    }

    public function execute(string $month): array
    {
        return $this->repository->findByMonth($month);
    }
}
