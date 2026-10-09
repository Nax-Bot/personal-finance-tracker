<?php

namespace App\Controllers;

// Commandes et requêtes pour gérer les comptes
use App\Commands\Account\CreateAccount;
use App\Commands\Account\UpdateAccount;
use App\Commands\Account\DeleteAccount;
use App\Queries\GetAccounts;

// Repositories pour interagir avec la base de données
use App\Repositories\Account\AccountWriteRepository;
use App\Repositories\Account\AccountReadRepository;

// Importer la classe jsonResponse pour gérer les réponses JSON
use App\Controllers\jsonResponse;

// Importer la classe PDO pour interagir avec la base de données
use PDO;

use InvalidArgumentException;
use RuntimeException;


class AccountController
{
    // promotion
    public function __construct(
        private PDO $connection // Declaration + Assignation automatique
    ) {}

    public function index(): void
    {
        $repository = new AccountReadRepository($this->connection);
        $query = new GetAccounts($repository);
        $accounts = $query->execute();

        JsonResponse::send($accounts);
    }

    public function show(int $id): void
    {
        $repository = new AccountReadRepository($this->connection);
        $query = new GetAccounts($repository);
        $account = $query->executeById($id);

        if ($account) {
            JsonResponse::send($account);
        } else {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => 'Account not found'
                ],
                404
            );
        }
    }

    public function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // validation des données d'entrée en tant que tableau associatif
        if (!is_array($data)) {
            // Bad Request (400) si les données d'entrée ne sont pas valides
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => 'Invalid JSON input'
                ],
                400
            );
            return;
        }

        try {
            $repository = new AccountWriteRepository($this->connection);
            $command = new CreateAccount($repository);

            $accountId = $command->execute(
                $data['name'],
                $data['type'],
                $data['balance']
            );

            JsonResponse::send([
                'success' => true,
                'message' => 'Account created successfully',
                'account_id' => $accountId
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            JsonResponse::send([
                'success' => false,
                'error' => $e->getMessage(),
                422
            ]);
        }
    }

    public function update(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // validation des données d'entrée en tant que tableau associatif
        if (!is_array($data)) {
            // Bad Request (400) si les données d'entrée ne sont pas valides
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => 'Invalid JSON input'
                ],
                400
            );
            return;
        }

        try {
            $repository = new AccountWriteRepository($this->connection);
            $command = new UpdateAccount($repository);

            $command->execute($id, $data);

            JsonResponse::send([
                'success' => true,
                'message' => 'Account updated successfully'
            ]);

        } catch (InvalidArgumentException $e) {

            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                400
            );
        } catch (RuntimeException $e) {

            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    public function delete(int $id): void
    {
        try {
            $repository = new AccountWriteRepository($this->connection);
            $command = new DeleteAccount($repository);

            $command->execute($id);

            JsonResponse::send([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } catch (RuntimeException $e) {
            JsonResponse::send(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

}
