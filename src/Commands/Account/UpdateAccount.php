<?php

namespace App\Commands\Account;

use App\Repositories\Account\AccountWriteRepository;

class UpdateAccount
{
    // Promotion
    public function __construct(
        private AccountWriteRepository $repository
    ) {}

    public function execute(int $id, array $data): void
    {
        if (isset($data['name']) && trim($data['name']) === '') {
            throw new \InvalidArgumentException('Account name is required');
        }

        if (isset($data['balance'])) {
            if ((float)$data['balance'] < 0) {
                throw new \InvalidArgumentException('Balance cannot be negative');
            }
            if (!is_numeric($data['balance'])) {
                throw new \InvalidArgumentException('Balance must be a number');
            }
        }

        if (isset($data['type']) && trim($data['type']) === '') {
            throw new \InvalidArgumentException('Account type is required');
        }

        if (empty($data)) {
            throw new \InvalidArgumentException('No data provided for update');
        }

        $updateData = $this->repository->update(
            $id,
            $data
        );

        if (!$updateData) {
            throw new \RuntimeException('Failed to update account');
        }
    }
}
