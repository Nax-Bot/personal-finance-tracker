<?php

namespace App\Commands\Transaction;

use App\Repositories\Account\AccountReadRepository;
use App\Repositories\Categories\CategoryReadRepository;
use App\Repositories\Transaction\TransactionReadRepository;
use App\Repositories\Transaction\TransactionWriteRepository;

use InvalidArgumentException;
use RuntimeException;

class UpdateTransaction
{
    private AccountReadRepository $accountRepository;
    private CategoryReadRepository $categoryRepository;
    private TransactionReadRepository $transactionReadRepository;
    private TransactionWriteRepository $transactionWriteRepository;

    public function __construct(
        AccountReadRepository $accountRepo,
        CategoryReadRepository $categoryRepo,
        TransactionReadRepository $transactionReadRepo,
        TransactionWriteRepository $transactionWriteRepo
    ) {
        $this->accountRepository = $accountRepo;
        $this->categoryRepository = $categoryRepo;
        $this->transactionReadRepository = $transactionReadRepo;
        $this->transactionWriteRepository = $transactionWriteRepo;
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
    public function execute(array $data): array
    {
        // Vérifier que la transaction existe
        $oldTransaction = $this->transactionReadRepository->findForUpdate($data['id']);

        if (!isset($oldTransaction)) {
            throw new RuntimeException(
                'Transaction not found.'
            );
        }

        // Vérifier le montant
        if (!isset($data['amount']) && $data['amount'] <= 0) {
            throw new InvalidArgumentException(
                'Transaction amount must be greater than zero.'
            );
        }

        // Vérifier le type
        if (!in_array($data['type'], ['income', 'expense'], true)) {
            throw new InvalidArgumentException(
                'Transaction type must be income or expense.'
            );
        }

        // Vérifier le nouveau compte
        $account = $this->accountRepository->getAccountById($data['account_id']);

        if (!isset($account)) {
            throw new RuntimeException(
                'Account not found.'
            );
        }

        // Vérifier la catégorie
        $category = null;

        if ($data['category_id'] !== null) {

            $category = $this->categoryRepository->getCategoryById($data['category_id']);

            if ($category === null) {
                throw new RuntimeException(
                    'Category not found.'
                );
            }
        }

        /*
         * Vérifier le solde disponible.
         *
         * Si on garde le même compte, son solde contient encore
         * l'ancien effet de la transaction.
         *
         * On commence donc par calculer le solde "réel"
         * après annulation de l'ancienne transaction.
         */
        $availableBalance = (float) $account['balance'];

        if ($oldTransaction['account_id'] == $data['account_id']) {
            if ($oldTransaction['type'] === 'expense') {
                $availableBalance += (float) $oldTransaction['amount'];
            } else {
                $availableBalance -= (float) $oldTransaction['amount'];
            }
        }

        if ($data['type'] === 'expense' && $data['amount'] > $availableBalance) {
            throw new InvalidArgumentException(
                'Insufficient account balance.'
            );
        }

        // Vérifier la limite mensuelle
        $warning = null;
        if (
            $data['type'] === 'expense'
            && $category !== null
            && $category['monthly_limit'] !== null
        ) {
            $month = substr($data['transaction_date'], 0, 7);

            $currentExpenses =
                $this->transactionReadRepository
                ->getMonthlyExpenseForCategory(
                    $data['category_id'],
                    $month
                );

            /*
             * Si l'ancienne transaction appartient
             * à cette même catégorie et au même mois,
             * on doit l'exclure du calcul.
             */
            if (
                $oldTransaction['category_id'] == $data['category_id']
                && $oldTransaction['type'] === 'expense'
                && substr(
                    $oldTransaction['transaction_date'],
                    0,
                    7
                ) === $month
            ) {
                $currentExpenses -= (float) $oldTransaction['amount'];
            }

            $monthlyLimit = (float) $category['monthly_limit'];

            $projectedExpenses = $currentExpenses + $data['amount'];

            if ($projectedExpenses > $monthlyLimit) {
                $warning = [
                    'message' => 'Monthly category limit exceeded',
                    'monthly_limit' => $monthlyLimit,
                    'current_expenses' => $currentExpenses,
                    'projected_expenses' => $projectedExpenses,
                    'exceeded_by' => $projectedExpenses - $monthlyLimit
                ];
            }
        }

        // Toutes les règles sont respectées
        $updated = $this->transactionWriteRepository->update($data);

        if (!$updated) {
            throw new RuntimeException(
                'Transaction could not be updated.'
            );
        }

        return [
            'transaction_update' => $updated,
            'warning' => $warning
        ];
    }
}
