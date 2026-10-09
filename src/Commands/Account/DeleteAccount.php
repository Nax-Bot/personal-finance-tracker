<?php

namespace App\Commands\Account;

use App\Repositories\Account\AccountWriteRepository;

final class DeleteAccount
{
    private AccountWriteRepository $repository;

    public function __construct(AccountWriteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $id): void
    {
        $deleteData = $this->repository->delete($id);

        if (!$deleteData) {
            throw new \RuntimeException('Failed to delete account');
        }
    }
}
