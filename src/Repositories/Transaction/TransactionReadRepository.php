<?php

namespace App\Repositories\Transaction;

use PDO;
use PDOException;
use RuntimeException;

class TransactionReadRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getAllTransactions(): array
    {
        try {
            $request = 'SELECT * FROM transactions ORDER BY transaction_date DESC';

            $stmt = $this->connection->query($request);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch transactions: ' . $e->getMessage());
        }
    }

    public function getTransactionById(int $id): ?array
    {
        try {
            $request = 'SELECT * FROM transactions WHERE id = :id';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch transaction: ' . $e->getMessage());
        }
    }

    public function getTransactionsByCategoryId(int $categoryId): array
    {
        try {
            $request = 'SELECT * FROM transactions WHERE category_id = :category_id ORDER BY transaction_date DESC';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch transactions by category: ' . $e->getMessage());
        }
    }

    public function getTransactionsByAccountId(int $accountId): array
    {
        try {
            $request = 'SELECT * FROM transactions WHERE account_id = :account_id ORDER BY transaction_date DESC';

            $stmt = $this->connection->prepare($request);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch transactions by account: ' . $e->getMessage());
        }
    }

    public function getMonthlyExpenseForCategory(int $categoryId, string $month): float
    {
        // si sum renvoye null => coalesce renvoye 0
        $sql = 'SELECT COALESCE(SUM(amount),0) 
                FROM transactions
                WHERE category_id = :category_id
                AND type = "expense"
                AND transaction_date >= :start_date
                AND transaction_date < :end_date';

        $stmt = $this->connection->prepare($sql);

        $startDate = $month . '-01';

        $endDate = date(
            'Y-m-d',
            strtotime($startDate . '+1 month')
        );

        $stmt->execute([
            ':category_id' => $categoryId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        return (float)$stmt->fetchColumn();
    }

    public function findForUpdate(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, account_id, category_id, type, amount, note, transaction_date
            FROM transactions
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id
        ]);

        $transaction = $statement->fetch();

        return $transaction ?: null;
    }

    // Transaction par mois
    public function findByMonth(string $month): array
    {
        $startDate = $month . '-01';

        $endDate = date(
            'Y-m-d',
            strtotime($startDate . ' +1 month')
        );

        $statement = $this->connection->prepare(
            'SELECT
            id,
            account_id,
            category_id,
            type,
            amount,
            note,
            transaction_date,
            created_at
         FROM transactions
         WHERE transaction_date >= :start_date
         AND transaction_date < :end_date
         ORDER BY transaction_date DESC, id DESC'
        );

        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $statement->fetchAll();
    }
}
