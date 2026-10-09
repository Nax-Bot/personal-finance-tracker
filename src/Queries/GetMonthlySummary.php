<?php

namespace App\Queries;

use PDO;

class GetMonthlySummary
{
    public function __construct(
        private PDO $connection
    ) {}

    public function execute(string $month): array
    {
        $startDate = $month . '-01';

        $endDate = date(
            'Y-m-d',
            strtotime($startDate . ' +1 month')
        );

        // Total des revenus
        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM transactions
             WHERE type = "income"
             AND transaction_date >= :start_date
             AND transaction_date < :end_date'
        );

        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $totalIncome = (float) $statement->fetchColumn();

        // Total des dépenses
        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM transactions
             WHERE type = "expense"
             AND transaction_date >= :start_date
             AND transaction_date < :end_date'
        );

        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $totalExpense = (float) $statement->fetchColumn();

        // Dépenses par catégorie
        $statement = $this->connection->prepare(
            'SELECT
                c.id,
                c.name,
                COALESCE(SUM(t.amount), 0) AS amount
             FROM transactions t
             INNER JOIN categories c
                ON c.id = t.category_id
             WHERE t.type = "expense"
             AND t.transaction_date >= :start_date
             AND t.transaction_date < :end_date
             GROUP BY c.id, c.name
             ORDER BY amount DESC'
        );

        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $expensesByCategory = $statement->fetchAll();

        return [
            'month' => $month,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance' => $totalIncome - $totalExpense,
            'expenses_by_category' => $expensesByCategory
        ];
    }
}
