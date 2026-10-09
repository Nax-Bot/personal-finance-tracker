<?php

namespace App\Controllers;

use App\Commands\Transaction\CreateTransaction;
use App\Commands\Transaction\UpdateTransaction;
use App\Commands\Transaction\DeleteTransaction;

use App\Queries\GetMonthlyTransactions;

use App\Queries\GetTransactions;

use App\Repositories\Account\AccountReadRepository;
use App\Repositories\Categories\CategoryReadRepository;

use App\Repositories\Transaction\TransactionReadRepository;
use App\Repositories\Transaction\TransactionWriteRepository;

use PDO;
use InvalidArgumentException;
use RuntimeException;

use App\Controllers\JsonResponse;

class TransactionController
{
    public function __construct(
        private PDO $connection
    ) {}

    public function store(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {
            JsonResponse::send([
                'error' => 'Invalid JSON body.'
            ], 400);

            return;
        }

        try {
            $command = new CreateTransaction(
                new TransactionWriteRepository($this->connection),
                new TransactionReadRepository($this->connection),
                new CategoryReadRepository($this->connection),
                new AccountReadRepository($this->connection)

            );

            $result = $command->execute([
                'account_id' => (int) ($data['account_id'] ?? 0),
                'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null,
                'type' => $data['type'] ?? '',
                'amount' => (float) ($data['amount'] ?? 0),
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? ''
            ]);

            JsonResponse::send([
                'message' => 'Transaction created successfully.',
                'transaction_id' => $result['transaction_create'],
                'warning' => $result['warning']
            ], 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send([
                'error' => $e->getMessage()
            ], 422);
        } catch (RuntimeException $e) {
            $status = $e->getMessage() === 'Account not found.'
                || $e->getMessage() === 'Category not found.'
                ? 404
                : 400;

            JsonResponse::send([
                'error' => $e->getMessage()
            ], $status);
        }
    }

    // Affiche les transactions
    public function index(): void
    {
        $repository = new TransactionReadRepository($this->connection);
        $query = new GetTransactions($repository);

        $transactions = $query->execute();

        JsonResponse::send($transactions);
    }

    public function show(int $id): void
    {
        $repository = new TransactionReadRepository($this->connection);
        $query = new GetTransactions($repository);

        $transactions = $query->executeById($id);

        if ($transactions === null) {
            JsonResponse::send([
                'error' => 'Transaction not found'
            ], 404);
            return;
        }

        JsonResponse::send($transactions);
    }

    public function accountTransaction(int $account_id): void
    {
        $repository = new TransactionReadRepository($this->connection);
        $query = new GetTransactions($repository);

        $transactions = $query->executeByAccount($account_id);

        if (empty($transactions)) {
            JsonResponse::send([
                'error' => 'Transaction not found'
            ], 404);
            return;
        }

        JsonResponse::send($transactions);
    }

    public function delete(int $id): void
    {
        try {
            $repository = new TransactionWriteRepository($this->connection);
            $command = new DeleteTransaction($repository);

            $command->execute($id);

            JsonResponse::send([
                'message' => 'Transaction deleted successfully.'
            ]);
        } catch (\InvalidArgumentException $e) {
            JsonResponse::send([
                'error' => $e->getMessage()
            ], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(int $id): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {
            JsonResponse::send([
                'error' => 'Invalid JSON body.'
            ], 400);

            return;
        }

        try {
            $command = new UpdateTransaction(
                new AccountReadRepository($this->connection),
                new CategoryReadRepository($this->connection),
                new TransactionReadRepository($this->connection),
                new TransactionWriteRepository($this->connection)
            );

            $result = $command->execute([
                'id' => $id,
                'account_id' => (int) ($data['account_id'] ?? 0),
                'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null,
                'type' => $data['type'] ?? '',
                'amount' => (float) ($data['amount'] ?? 0),
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? ''
            ]);

            JsonResponse::send([
                'message' => 'Transaction updated successfully.',
                'transaction_id' => $result['transaction_update'],
                'warning' => $result['warning']
            ]);
        } catch (InvalidArgumentException $e) {

            JsonResponse::send([
                'error' => $e->getMessage()
            ], 422);
        } catch (RuntimeException $e) {
            $status = in_array(
                $e->getMessage(),
                [
                    'Transaction not found.',
                    'Account not found.',
                    'Category not found.'
                ],
                true
            ) ? 404 : 400;

            JsonResponse::send([
                'error' => $e->getMessage()
            ], $status);
        }
    }

    public function monthly(string $month): void
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            JsonResponse::send([
                'error' => 'Invalid month format. Use YYYY-MM.'
            ], 422);

            return;
        }

        try {
            $query = new GetMonthlyTransactions(
                new TransactionReadRepository(
                    $this->connection
                )
            );

            $transactions = $query->execute($month);

            JsonResponse::send([
                'month' => $month,
                'transactions' => $transactions
            ]);
        } catch (\Throwable $e) {
            JsonResponse::send([
                'error' => 'Unable to retrieve monthly transactions.'
            ], 500);
        }
    }
}
