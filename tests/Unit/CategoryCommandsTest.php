<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Commands\Categories\CreateCategory;
use App\Commands\Categories\UpdateCategory;
use App\Repositories\Categories\CategoryWriteRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CategoryCommandsTest extends TestCase
{
    public function testCreateCategoryPassesNameAndMonthlyLimitToRepository(): void
    {
        $repository = $this->createMock(CategoryWriteRepository::class);
        $repository->expects($this->once())
            ->method('createCategory')
            ->with(['name' => 'Alimentation', 'monthly_limit' => 300])
            ->willReturn(4);

        // Le dépôt renvoie l'identifiant de la catégorie créée.
        self::assertSame(4, (new CreateCategory($repository))->execute([
            'name' => 'Alimentation',
            'monthly_limit' => 300,
        ]));
    }

    public function testCreateCategoryRejectsANegativeMonthlyLimit(): void
    {
        $repository = $this->createMock(CategoryWriteRepository::class);
        $repository->expects($this->never())->method('createCategory');

        $this->expectException(InvalidArgumentException::class);
        (new CreateCategory($repository))->execute([
            'name' => 'Alimentation',
            'monthly_limit' => -1,
        ]);
    }

    public function testCreateCategoryRejectsANonNumericMonthlyLimit(): void
    {
        $repository = $this->createMock(CategoryWriteRepository::class);
        $repository->expects($this->never())->method('createCategory');

        $this->expectException(InvalidArgumentException::class);
        (new CreateCategory($repository))->execute([
            'name' => 'Alimentation',
            'monthly_limit' => 'abc',
        ]);
    }

    public function testUpdateCategoryRejectsAnEmptyUpdate(): void
    {
        $repository = $this->createMock(CategoryWriteRepository::class);
        $repository->expects($this->never())->method('updateCategory');

        $this->expectException(InvalidArgumentException::class);
        (new UpdateCategory($repository))->execute(2, []);
    }

    public function testUpdateCategoryForwardsValidData(): void
    {
        $repository = $this->createMock(CategoryWriteRepository::class);
        $repository->expects($this->once())
            ->method('updateCategory')
            ->with(2, ['monthly_limit' => 500]);

        // Vérifie qu'une limite valide passe les contrôles de la commande.
        (new UpdateCategory($repository))->execute(2, ['monthly_limit' => 500]);
        self::assertTrue(true);
    }
}
