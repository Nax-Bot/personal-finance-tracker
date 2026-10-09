<?php
// Commande pour créer un compte 
namespace App\Commands\Account;

use App\Repositories\Account\AccountWriteRepository;

class CreateAccount
{

    private AccountWriteRepository $repository;

    public function __construct(AccountWriteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(string $name, string $type, float $balance): int
    {

        // regles de validation pour le nom du compte et le solde initial
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Account name is required');
        }

        if ($balance < 0) {
            throw new \InvalidArgumentException('Initial balance cannot be negative');
        }

        return $this->repository->create(
            $name,
            $type,
            $balance
        );
    }
}
