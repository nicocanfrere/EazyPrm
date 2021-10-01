<?php

namespace EasyPrm\Tests\ProductCatalog\Command;

use EasyPrm\Core\Contract\IdentifierFactoryInterface;
use EasyPrm\Core\Factory\IdentifierFactory;
use EasyPrm\Core\ValueObject\Identifier;
use EasyPrm\ProductCatalog\Command\Price\CreateCommand;
use EasyPrm\ProductCatalog\Contract\PriceInterface;
use EasyPrm\ProductCatalog\Exception\PriceAlreadyExistsException;
use EasyPrm\ProductCatalog\Factory\PriceFactory;
use EasyPrm\ProductCatalog\Price;
use EasyPrm\ProductCatalog\ValueObject\Amount;
use EasyPrm\ProductCatalog\ValueObject\Currency;
use EasyPrm\Tests\ProductCatalog\Repository\InMemoryPriceRepository;
use EasyPrm\Tests\Transliterator;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Class CreateCommand
 */
class CreateCommandTest extends TestCase
{
    /**
     * @test
     */
    public function handle()
    {
        $transliterator = new Transliterator();
        $identifierFactory = $this->createMock(IdentifierFactoryInterface::class);
        $identifierFactory->method('create')->willReturn(Identifier::create('abcd-efgh'));
        $repository = new InMemoryPriceRepository($transliterator);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');
        $factory = new PriceFactory(
            $identifierFactory,
            $transliterator
        );
        $command = new CreateCommand(
            $factory,
            $repository,
            $eventDispatcher
        );
        $command->handle(['label' => 'label', 'amount' => 100, 'currency' => 'EUR']);
        $this->assertInstanceOf(PriceInterface::class, $repository->oneByLabel('label'));
    }

    /**
     * @test
     */
    public function throwProductAlreadyExistsException()
    {
        $sameLabel = 'label';
        $transliterator = new Transliterator();
        $repository = new InMemoryPriceRepository($transliterator);
        $price = new Price(
            Identifier::create('a'),
            $sameLabel,
            $sameLabel,
            Amount::create(100),
            Currency::create('EUR')
        );
        $repository->save($price);
        $identifierFactory = $this->createMock(IdentifierFactoryInterface::class);
        $identifierFactory->method('create')->willReturn(Identifier::create('b'));
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $factory = new PriceFactory(
            $identifierFactory,
            $transliterator
        );
        $command = new CreateCommand(
            $factory,
            $repository,
            $eventDispatcher
        );
        $this->expectException(PriceAlreadyExistsException::class);
        $command->handle(['label' => 'label', 'amount' => 100, 'currency' => 'EUR']);
    }
}
