<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Commands\Transaction\CreateTransaction;
use App\Commands\Transaction\DeleteTransaction;
use App\Repositories\Account\AccountReadRepository;
use App\Repositories\Categories\CategoryReadRepository;
use App\Repositories\Transaction\TransactionReadRepository;
use App\Repositories\Transaction\TransactionWriteRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class TransactionCommandsTest extends TestCase
{
    public function testCreateIncomeStoresTransactionAndReturnsNoWarning(): void
    {
        $writer = $this->createMock(TransactionWriteRepository::class);
        $transactionReader = $this->createMock(TransactionReadRepository::class);
        $categoryReader = $this->createMock(CategoryReadRepository::class);
        $accountReader = $this->createMock(AccountReadRepository::class);
        $data = [
            'account_id' => 5,
            'category_id' => null,
            'type' => 'income',
            'amount' => 100.00,
            'transaction_date' => '2026-01-10',
            'note' => 'Salaire',
        ];

        $accountReader->expects($this->once())->method('getAccountById')->with(5)
            ->willReturn(['id' => 5, 'balance' => '200.00']);
        $categoryReader->expects($this->never())->method('getCategoryById');
        $transactionReader->expects($this->never())->method('getMonthlyExpenseForCategory');
        $writer->expects($this->once())->method('create')->with($data)->willReturn(12);

        $result = (new CreateTransaction($writer, $transactionReader, $categoryReader, $accountReader))
            ->execute($data);

        // Une recette valide renvoie l'ID et aucune alerte de plafond.
        self::assertSame(['transaction_create' => 12, 'warning' => null], $result);
    }

    public function testCreateTransactionRejectsZeroAmountBeforeCallingRepositories(): void
    {
        $writer = $this->createMock(TransactionWriteRepository::class);
        $transactionReader = $this->createMock(TransactionReadRepository::class);
        $categoryReader = $this->createMock(CategoryReadRepository::class);
        $accountReader = $this->createMock(AccountReadRepository::class);
        $writer->expects($this->never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        (new CreateTransaction($writer, $transactionReader, $categoryReader, $accountReader))->execute([
            'amount' => 0,
            'type' => 'income',
        ]);
    }

    public function testCreateTransactionRejectsUnknownType(): void
    {
        $writer = $this->createMock(TransactionWriteRepository::class);
        $transactionReader = $this->createMock(TransactionReadRepository::class);
        $categoryReader = $this->createMock(CategoryReadRepository::class);
        $accountReader = $this->createMock(AccountReadRepository::class);
        $writer->expects($this->never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        (new CreateTransaction($writer, $transactionReader, $categoryReader, $accountReader))->execute([
            'amount' => 10,
            'type' => 'transfer',
        ]);
    }

    public function testCreateExpenseRejectsInsufficientBalance(): void
    {
        $writer = $this->createMock(TransactionWriteRepository::class);
        $transactionReader = $this->createMock(TransactionReadRepository::class);
        $categoryReader = $this->createMock(CategoryReadRepository::class);
        $accountReader = $this->createMock(AccountReadRepository::class);
        $accountReader->method('getAccountById')->willReturn(['id' => 5, 'balance' => '20.00']);
        $writer->expects($this->never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        (new CreateTransaction($writer, $transactionReader, $categoryReader, $accountReader))->execute([
            'account_id' => 5,
            'category_id' => null,
            'type' => 'expense',
            'amount' => 21,
            'transaction_date' => '2026-01-10',
            'note' => null,
        ]);
    }

    public function testCreateExpenseWarnsWhenItExceedsCategoryMonthlyLimit(): void
    {
        $writer = $this->createMock(TransactionWriteRepository::class);
        $transactionReader = $this->createMock(TransactionReadRepository::class);
        $categoryReader = $this->createMock(CategoryReadRepository::class);
        $accountReader = $this->createMock(AccountReadRepository::class);
        $data = [
            'account_id' => 5, 'category_id' => 8, 'type' => 'expense', 'amount' => 30.0,
            'transaction_date' => '2026-01-10', 'note' => null,
        ];

        $accountReader->method('getAccountById')->willReturn(['id' => 5, 'balance' => '100.00']);
        $categoryReader->method('getCategoryById')->with(8)
            ->willReturn(['id' => 8, 'monthly_limit' => '100.00']);
        $transactionReader->expects($this->once())->method('getMonthlyExpenseForCategory')->with(8, '2026-01')
            ->willReturn(80.0);
        $writer->method('create')->with($data)->willReturn(13);

        $result = (new CreateTransaction($writer, $transactionReader, $categoryReader, $accountReader))
            ->execute($data);

        // Les 80 déjà dépensés + 30 dépassent le plafond de 100 de 10.
        self::assertSame(13, $result['transaction_create']);
        self::assertSame(110.0, $result['warning']['projected_expenses']);
        self::assertSame(10.0, $result['warning']['exceeded_by']);
    }

    public function testCreateTransactionRejectsAnUnknownAccount(): void
    {
        $writer = $this->createMock(TransactionWriteRepository::class);
        $transactionReader = $this->createMock(TransactionReadRepository::class);
        $categoryReader = $this->createMock(CategoryReadRepository::class);
        $accountReader = $this->createMock(AccountReadRepository::class);
        $accountReader->method('getAccountById')->willReturn(null);
        $writer->expects($this->never())->method('create');

        $this->expectException(RuntimeException::class);
        (new CreateTransaction($writer, $transactionReader, $categoryReader, $accountReader))->execute([
            'account_id' => 77, 'category_id' => null, 'type' => 'income', 'amount' => 5,
            'transaction_date' => '2026-01-10', 'note' => null,
        ]);
    }

    public function testDeleteTransactionRejectsAnInvalidId(): void
    {
        $repository = $this->createMock(TransactionWriteRepository::class);
        $repository->expects($this->never())->method('delete');

        $this->expectException(InvalidArgumentException::class);
        (new DeleteTransaction($repository))->execute(0);
    }

    public function testDeleteTransactionReportsWhenTheRowDoesNotExist(): void
    {
        $repository = $this->createMock(TransactionWriteRepository::class);
        $repository->expects($this->once())->method('delete')->with(999)->willReturn(false);

        $this->expectException(RuntimeException::class);
        (new DeleteTransaction($repository))->execute(999);
    }
}
