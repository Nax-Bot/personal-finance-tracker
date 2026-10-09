<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Commands\Account\CreateAccount;
use App\Commands\Account\DeleteAccount;
use App\Commands\Account\UpdateAccount;
use App\Repositories\Account\AccountWriteRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class AccountCommandsTest extends TestCase
{
    public function testCreateAccountSendsValidDataToRepository(): void
    {
        // Le dépôt est simulé : ce test vérifie la règle métier sans toucher MariaDB.
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with('Compte courant', 'checking', 125.50)
            ->willReturn(7);

        $command = new CreateAccount($repository);

        // L'identifiant retourné est celui que le dépôt a simulé.
        self::assertSame(7, $command->execute('Compte courant', 'checking', 125.50));
    }

    public function testCreateAccountRejectsAnEmptyName(): void
    {
        // Un nom composé d'espaces est vide pour l'application.
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->never())->method('create');
        $command = new CreateAccount($repository);

        $this->expectException(InvalidArgumentException::class);
        $command->execute('   ', 'checking', 10.00);
    }

    public function testCreateAccountRejectsANegativeStartingBalance(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->never())->method('create');
        $command = new CreateAccount($repository);

        // Un compte ne peut pas commencer avec un solde négatif.
        $this->expectException(InvalidArgumentException::class);
        $command->execute('Compte courant', 'checking', -1.00);
    }

    public function testUpdateAccountRejectsAnEmptyUpdate(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->never())->method('update');
        $command = new UpdateAccount($repository);

        $this->expectException(InvalidArgumentException::class);
        $command->execute(1, []);
    }

    public function testUpdateAccountRejectsANegativeBalance(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->never())->method('update');
        $command = new UpdateAccount($repository);

        $this->expectException(InvalidArgumentException::class);
        $command->execute(1, ['balance' => -5]);
    }

    public function testUpdateAccountRejectsNonNumericBalance(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->never())->method('update');
        $command = new UpdateAccount($repository);

        // Le montant doit pouvoir être converti en nombre.
        $this->expectException(InvalidArgumentException::class);
        $command->execute(1, ['balance' => 'abc']);
    }

    public function testUpdateAccountForwardsAValidUpdate(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->once())
            ->method('update')
            ->with(3, ['name' => 'Épargne'])
            ->willReturn(true);

        (new UpdateAccount($repository))->execute(3, ['name' => 'Épargne']);
    }

    public function testUpdateAccountReportsRepositoryFailure(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->method('update')->willReturn(false);
        $command = new UpdateAccount($repository);

        $this->expectException(RuntimeException::class);
        $command->execute(3, ['name' => 'Épargne']);
    }

    public function testDeleteAccountReportsRepositoryFailure(): void
    {
        $repository = $this->createMock(AccountWriteRepository::class);
        $repository->expects($this->once())->method('delete')->with(99)->willReturn(false);

        $this->expectException(RuntimeException::class);
        (new DeleteAccount($repository))->execute(99);
    }
}
