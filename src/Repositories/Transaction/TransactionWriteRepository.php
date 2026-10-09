<?php

namespace App\Repositories\Transaction;

use PDO;
use PDOException;
use RuntimeException;

use Throwable;

class TransactionWriteRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function create(array $data): int
    {
        $this->connection->beginTransaction();

        try {
            // On cree la transaction
            $sql = 'INSERT INTO transactions (account_id, category_id,type,amount, transaction_date,note) VALUES (:account_id, :category_id, :type, :amount, :transaction_date, :note)';

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                ':account_id' => $data['account_id'],
                ':category_id' => $data['category_id'],
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':transaction_date' => $data['transaction_date'],
                ':note' => $data['note'],
            ]);

            // On recupere l'ID de la transaction creee
            $transactionId = (int)$this->connection->lastInsertId();

            // Modification du Solde - Account lié à la transaction
            $amount = $data['amount'];
            /*$balanceChange = $data['type'] === 'income' ? $amount : -$amount;*/

            if ($data['type'] === 'income') {
                $updateAccount = 'UPDATE accounts SET balance = balance + :balanceChange WHERE id = :account_id';
            } else {
                $updateAccount = 'UPDATE accounts SET balance = balance - :balanceChange WHERE id = :account_id';
            }

            $stmt = $this->connection->prepare($updateAccount);
            $stmt->execute([
                ':balanceChange' => $amount, //$balanceChange
                ':account_id' => $data['account_id']
            ]);

            // OK => Commit
            $this->connection->commit();

            return $transactionId;
        } catch (PDOException $e) {
            // On annule tout
            $this->connection->rollBack();
            throw new RuntimeException('Failed to create transaction: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        $this->connection->beginTransaction();

        try {
            // Récupérer la transaction avant de la supprimer
            $sql = 'SELECT account_id, type, amount
                    FROM transactions
                    WHERE id = :id';

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                'id' => $id
            ]);

            $transaction = $stmt->fetch();

            if (!$transaction) {
                $this->connection->rollBack();

                return false;
            }

            // Inverser l'effet de la transaction sur le solde
            // $balanceChange = $transaction['type'] === 'income' ? - $transaction['amount'] : $transaction['amount'];

            if ($transaction['type'] === 'income') {
                $request = 'UPDATE accounts
                            SET balance = balance - :balance_change
                            WHERE id = :account_id';
            } else {
                $request = 'UPDATE accounts
                            SET balance = balance + :balance_change
                            WHERE id = :account_id';
            }

            $stmt = $this->connection->prepare($request);

            $stmt->execute([
                'balance_change' => $transaction['amount'],
                'account_id' => $transaction['account_id']
            ]);

            // Supprimer la transaction
            $stmt = $this->connection->prepare(
                'DELETE FROM transactions
                 WHERE id = :id'
            );

            $stmt->execute(['id' => $id]);

            $this->connection->commit();

            return true;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
    /**
     * int $id,
     * int $accountId,
     * ?int $categoryId,
     * string $type,
     * float $amount,
     * ?string $note,
     * string $transactionDate
     */
    public function update(array $data): bool
    {
        $this->connection->beginTransaction();

        try {
            // Récupérer l'ancienne transaction
            $sql = 'SELECT account_id, type, amount
                    FROM transactions
                    WHERE id = :id';

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([':id' => $data['id']]);

            $oldTransaction = $stmt->fetch();

            // S'il n'y a pas de transaction
            if (!$oldTransaction) {

                $this->connection->rollBack();

                return false;
            }

            // Annuler l'ancienne transaction
            // $oldBalanceChange = $oldTransaction['type'] === 'income' ? -$oldTransaction['amount']: $oldTransaction['amount'];

            if ($oldTransaction['type'] === 'income') {
                $sql = 'UPDATE accounts
                        SET balance = balance - :balance_change
                        WHERE id = :account_id';
            } else {
                $sql = 'UPDATE accounts
                        SET balance = balance + :balance_change
                        WHERE id = :account_id';
            }
            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                ':balance_change' => $oldTransaction['amount'],
                ':account_id' => $oldTransaction['account_id']
            ]);

            // Modifier la transaction
            $sql = 'UPDATE transactions
                    SET account_id = :account_id,
                    category_id = :category_id,
                    type = :type,
                    amount = :amount,
                    note = :note,
                    transaction_date = :transaction_date
                    WHERE id = :id';
            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                ':account_id' => $data['account_id'],
                ':category_id' => $data['category_id'],
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':note' => $data['note'],
                ':transaction_date' => $data['transaction_date'],
                ':id' => $data['id']
            ]);

            // Appliquer le nouvel effet
            // $newBalanceChange = $data['type'] === 'income'? $data['amount'] : -$data['amount'];

            if($data['type'] === 'income'){
                $sql = 'UPDATE accounts
                SET balance = balance + :balance_change
                WHERE id = :account_id';
            }else{
                $sql = 'UPDATE accounts
                SET balance = balance - :balance_change
                WHERE id = :account_id';
            }

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                'balance_change' => $data['amount'], //$newBalanceChange
                'account_id' => $data['account_id']
            ]);

            $this->connection->commit();

            return true;

        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}
