<?php

declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/IntegrationTestCase.php';

use App\Repositories\Account\AccountReadRepository;
use App\Repositories\Account\AccountWriteRepository;
use App\Repositories\Categories\CategoryWriteRepository;
use App\Repositories\Transaction\TransactionWriteRepository;
use PDOException;

final class MariaDbIntegrityTest extends IntegrationTestCase
{
    public function testAccountCanBeCreatedReadUpdatedAndDeletedInMariaDb(): void
    {
        // Ce test prouve que le dépôt et le schéma MariaDB fonctionnent ensemble.
        $name = $this->uniqueName('test_account');
        $writer = new AccountWriteRepository($this->pdo);
        $reader = new AccountReadRepository($this->pdo);

        $accountId = $writer->create($name, 'checking', 45.25);
        try {
            self::assertGreaterThan(0, $accountId);
            self::assertSame($name, $reader->getAccountById($accountId)['name']);

            self::assertTrue($writer->update($accountId, ['balance' => 60.00]));
            self::assertSame('60.00', $reader->getAccountById($accountId)['balance']);
        } finally {
            $writer->delete($accountId);
        }

        self::assertNull($reader->getAccountById($accountId));
    }

    public function testTransactionChangesAccountBalanceAndDeletingItRestoresBalance(): void
    {
        // L'INSERT de transaction et la mise à jour du solde doivent rester cohérents.
        $accountName = $this->uniqueName('test_account');
        $categoryName = $this->uniqueName('test_category');
        $accountId = null;
        $categoryId = null;
        $transactionId = null;
        $transactions = new TransactionWriteRepository($this->pdo);

        try {
            $accountId = (new AccountWriteRepository($this->pdo))->create($accountName, 'cash', 100.00);
            $categoryId = (new CategoryWriteRepository($this->pdo))->createCategory([
                'name' => $categoryName,
                'monthly_limit' => null,
            ]);

            $transactionId = $transactions->create([
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'type' => 'expense',
                'amount' => 25.50,
                'transaction_date' => '2026-01-15',
                'note' => 'Dépense de test',
            ]);

            $balance = $this->pdo->query("SELECT balance FROM accounts WHERE id = {$accountId}")->fetchColumn();
            self::assertSame('74.50', $balance);

            self::assertTrue($transactions->delete($transactionId));
            $restoredBalance = $this->pdo->query("SELECT balance FROM accounts WHERE id = {$accountId}")->fetchColumn();
            self::assertSame('100.00', $restoredBalance);
        } finally {
            // Nettoie les données créées même si une assertion échoue.
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

    public function testMariaDbRejectsATransactionLinkedToAnUnknownAccount(): void
    {
        // La clé étrangère empêche d'enregistrer une opération sur un compte inexistant.
        $categoryId = (new CategoryWriteRepository($this->pdo))->createCategory([
            'name' => $this->uniqueName('test_category'),
            'monthly_limit' => null,
        ]);

        try {
            $this->expectException(PDOException::class);
            $statement = $this->pdo->prepare(
                "INSERT INTO transactions (account_id, category_id, type, amount, transaction_date, note)
                 VALUES (4294967295, :category_id, 'expense', 1.00, '2026-01-15', 'référence invalide')"
            );
            $statement->execute(['category_id' => $categoryId]);
        } finally {
            $deleteCategory = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
            $deleteCategory->execute(['id' => $categoryId]);
        }
    }
}
