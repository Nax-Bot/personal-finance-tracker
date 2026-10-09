<?php

declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/IntegrationTestCase.php';

final class HttpRoutesTest extends IntegrationTestCase
{
    private const PREFIX = '/accounts';

    public function testAccountHttpRoutesCreateReadUpdateAndDeleteAnAccount(): void
    {
        // On passe par HTTP comme le ferait l'interface ou un client API.
        $name = $this->uniqueName('http_account');
        $created = $this->httpRequest('POST', self::PREFIX, [
            'name' => $name,
            'type' => 'checking',
            'balance' => 35.00,
        ]);

        self::assertSame(200, $created['status']);
        self::assertTrue($created['json']['success']);
        $accountId = (int) $created['json']['account_id'];

        try {
            $read = $this->httpRequest('GET', self::PREFIX . '/' . $accountId);
            self::assertSame(200, $read['status']);
            self::assertSame($name, $read['json']['name']);

            $updated = $this->httpRequest('PATCH', self::PREFIX . '/' . $accountId, ['name' => $name . '_updated']);
            self::assertSame(200, $updated['status']);
            self::assertTrue($updated['json']['success']);

            $deleted = $this->httpRequest('DELETE', self::PREFIX . '/' . $accountId);
            self::assertSame(200, $deleted['status']);

            self::assertSame(404, $this->httpRequest('GET', self::PREFIX . '/' . $accountId)['status']);
        } finally {
            // Nettoyage de sécurité si une vérification HTTP échoue avant DELETE.
            $statement = $this->pdo->prepare('DELETE FROM accounts WHERE id = :id');
            $statement->execute(['id' => $accountId]);
        }
    }

    public function testTransactionHttpRoutePersistsDataAndUpdatesTheBalance(): void
    {
        // Ce scénario traverse la route, le contrôleur, les commandes et MariaDB.
        $accountName = $this->uniqueName('http_account');
        $categoryName = $this->uniqueName('http_category');
        $accountId = null;
        $categoryId = null;
        $transactionId = null;

        try {
            $account = $this->httpRequest('POST', self::PREFIX, [
                'name' => $accountName,
                'type' => 'cash',
                'balance' => 50.00,
            ]);
            self::assertSame(200, $account['status']);
            $accountId = (int) $account['json']['account_id'];

            $category = $this->httpRequest('POST', '/categories', [
                'name' => $categoryName,
                'monthly_limit' => 200.00,
            ]);
            self::assertSame(200, $category['status']);
            $categoryId = (int) $category['json']['category_id'];

            $transaction = $this->httpRequest('POST', '/transaction', [
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'type' => 'expense',
                'amount' => 12.00,
                'transaction_date' => '2026-01-20',
                'note' => 'Test HTTP',
            ]);
            self::assertSame(201, $transaction['status']);
            $transactionId = (int) $transaction['json']['transaction_id'];

            $balance = $this->pdo->prepare('SELECT balance FROM accounts WHERE id = :id');
            $balance->execute(['id' => $accountId]);
            self::assertSame('38.00', $balance->fetchColumn());

            $deleted = $this->httpRequest('DELETE', '/transaction/' . $transactionId);
            self::assertSame(200, $deleted['status']);
        } finally {
            if ($transactionId !== null) {
                $deleteTransaction = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id');
                $deleteTransaction->execute(['id' => $transactionId]);
            }
            if ($categoryId !== null) {
                $deleteCategory = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
                $deleteCategory->execute(['id' => $categoryId]);
            }
            if ($accountId !== null) {
                $deleteAccount = $this->pdo->prepare('DELETE FROM accounts WHERE id = :id');
                $deleteAccount->execute(['id' => $accountId]);
            }
        }
    }

    public function testUnknownHttpRouteReturnsJsonNotFound(): void
    {
        // Une URL qui ne correspond à aucune route doit donner un 404 JSON.
        $response = $this->httpRequest('GET', '/route-qui-nexiste-pas');

        self::assertSame(404, $response['status']);
        self::assertFalse($response['json']['success']);
        self::assertSame('Route not found', $response['json']['error']);
    }
}
