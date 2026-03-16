<?php

// @codeCoverageIgnoreStart

declare(strict_types=1);

namespace App\Concerns;

use Mockery;
use RuntimeException;
use Mockery\Expectation;
use Mockery\MockInterface;
use Mockery\HigherOrderMessage;
use Mockery\ExpectationInterface;

trait AsFakeAction
{
    public static function mock(): MockInterface
    {
        if (static::isFake()) {
            return static::getFakeResolvedInstance() ?? throw new RuntimeException('Fake instance not found');
        }

        $mock = Mockery::mock(static::class);
        $mock->shouldAllowMockingProtectedMethods();

        return static::setFakeResolvedInstance($mock);
    }

    public static function spy(): MockInterface
    {
        if (static::isFake()) {
            return static::getFakeResolvedInstance() ?? throw new RuntimeException('Fake instance not found');
        }

        return static::setFakeResolvedInstance(Mockery::spy(static::class));
    }

    public static function partialMock(): MockInterface
    {
        /** @var MockInterface */
        return static::mock()->makePartial();
    }

    public static function shouldRun(): Expectation|ExpectationInterface|HigherOrderMessage
    {
        return static::mock()->shouldReceive('handle');
    }

    public static function shouldNotRun(): Expectation|ExpectationInterface|HigherOrderMessage
    {
        return static::mock()->shouldNotReceive('handle');
    }

    public static function allowToRun(): Expectation|ExpectationInterface|HigherOrderMessage|MockInterface
    {
        return static::spy()->allows('handle');
    }

    public static function isFake(): bool
    {
        return app()->isShared(static::getFakeResolvedInstanceKey());
    }

    /**
     * Removes the fake instance from the container.
     */
    public static function clearFake(): void
    {
        app()->forgetInstance(static::getFakeResolvedInstanceKey());
    }

    protected static function setFakeResolvedInstance(MockInterface $fake): MockInterface
    {
        /** @var MockInterface */
        return app()->instance(static::getFakeResolvedInstanceKey(), $fake);
    }

    protected static function getFakeResolvedInstance(): ?MockInterface
    {
        /** @var MockInterface|null */
        return app(static::getFakeResolvedInstanceKey());
    }

    protected static function getFakeResolvedInstanceKey(): string
    {
        return 'Action:AsFake:'.static::class;
    }

    // @codeCoverageIgnoreEnd
}