<?php

namespace App\Commands\Transaction;

use App\Repositories\Transaction\TransactionReadRepository;
use App\Repositories\Transaction\TransactionWriteRepository;
use Exception;
use RuntimeException;
use InvalidArgumentException;

class DeleteTransaction
{
    public function __construct(
        private TransactionWriteRepository $repository
    ) {}

    public function execute(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid transaction ID');
        }

        $deleted = $this->repository->delete($id);

        if(!$deleted){
            throw new RuntimeException('Transaction not found');
        }

    }
}
