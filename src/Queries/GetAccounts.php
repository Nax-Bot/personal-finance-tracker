<?php
// commande pour récupérer tous les comptes
namespace App\Queries;

use App\Repositories\Account\AccountReadRepository;

class GetAccounts
{
    private AccountReadRepository $repository;

    public function __construct(AccountReadRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        return $this->repository->getAllAccounts();
    }

    public function executeById(int $id): ?array
    {
        return $this->repository->getAccountById($id);
    }
}