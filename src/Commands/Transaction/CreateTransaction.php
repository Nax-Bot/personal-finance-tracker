<?php

namespace App\Commands\Transaction;

use App\Repositories\Transaction\TransactionReadRepository;
use App\Repositories\Transaction\TransactionWriteRepository;

use App\Repositories\Categories\CategoryReadRepository;
use App\Repositories\Account\AccountReadRepository;

use InvalidArgumentException;
use RuntimeException;

class CreateTransaction
{

    private TransactionWriteRepository $transactionWriteRepo;
    private TransactionReadRepository $transactionRepo;

    private CategoryReadRepository $categoryRepo;
    private AccountReadRepository $accountRepo;

    public function __construct(
        TransactionWriteRepository $transactionWriteRepository,
        TransactionReadRepository $transactionRepository,
        CategoryReadRepository $categoryRepository,
        AccountReadRepository $accountRepository
    ) {
        $this->transactionRepo = $transactionRepository;
        $this->transactionWriteRepo = $transactionWriteRepository;

        $this->accountRepo = $accountRepository;
        $this->categoryRepo = $categoryRepository;
    }

    /**
     * int $accountId,
     * ?int $categoryId,
     * string $type,
     * float $amount,
     * ?string $note,
     * string $transactionDate
     * 
     */
    public function execute(array $data): array
    {
        // 1. Vérifier le montant
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            throw new InvalidArgumentException(
                'Transaction amount must be greater than zero.'
            );
        }

        // 2. Vérifier le type
        if (!isset($data['type']) || !in_array($data['type'], ['income', 'expense'], true)) {
            throw new InvalidArgumentException(
                'Transaction type must be income or expense.'
            );
        }

        // 3. Vérifier que le compte existe
        $account = $this->accountRepo->getAccountById($data['account_id']);

        if ($account === null) {
            throw new RuntimeException(
                'Account not found.'
            );
        }

        // 4. Vérifier la catégorie si elle est fournie
        $category = null;

        if ($data['category_id'] !== null) {
            $category = $this->categoryRepo->getCategoryById($data['category_id']);

            if ($category === null) {
                throw new RuntimeException(
                    'Category not found.'
                );
            }
        }

        // 5. Une dépense ne doit pas dépasser le solde
        if ($data['type'] === 'expense') {
            $balance = (float) $account['balance'];

            if ($data['amount'] > $balance) {
                throw new InvalidArgumentException(
                    'Insufficient account balance.'
                );
            }
        }

        // 6. Vérifier la limite mensuelle de la catégorie
        $warning = null;

        if (
            $data['type'] === 'expense'
            && $category !== null
            && $category['monthly_limit'] !== null
        ) {
            // decoupe une chaine de caractère + [0-7]
            $month = substr($data['transaction_date'], 0, 7);

            $currentExpenses =
                $this->transactionRepo
                ->getMonthlyExpenseForCategory(
                    $data['category_id'],
                    $month
                );

            $monthlyLimit = (float) $category['monthly_limit'];

            $projectedExpenses = $currentExpenses + $data['amount'];

            if ($projectedExpenses > $monthlyLimit) {
                $warning = [
                    'message' => 'Monthly category limit exceeded',
                    'monthy_limit' => $monthlyLimit,
                    'current_expenses' => $currentExpenses,
                    'projected_expenses' => $projectedExpenses,
                    'exceeded_by' => $projectedExpenses - $monthlyLimit
                ];
            }
        }

        $transactionData = $this->transactionWriteRepo->create($data);
        
        // 7. Toutes les règles sont respectées
        return [
            'transaction_create' => $transactionData,
            'warning' => $warning
        ];
    }
}
