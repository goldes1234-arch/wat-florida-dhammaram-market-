<?php

namespace Tests;

/**
 * Minimal, dependency-free test base class — this project has no Composer
 * dependencies anywhere else, so it doesn't pull in PHPUnit just for tests.
 * See tests/run.php for how test classes are discovered and run.
 */
class AssertionFailed extends \RuntimeException
{
}

abstract class TestCase
{
    /** Runs before every test* method on this class, when overridden. */
    public function setUp(): void
    {
    }

    /** Runs after every test* method on this class (even on failure), when overridden. */
    public function tearDown(): void
    {
    }

    protected function assertTrue(bool $condition, string $message = 'Failed asserting that a condition is true'): void
    {
        if (!$condition) {
            throw new AssertionFailed($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Failed asserting that a condition is false'): void
    {
        $this->assertTrue(!$condition, $message);
    }

    protected function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailed($message ?: sprintf(
                'Failed asserting that %s is identical to expected %s',
                var_export($actual, true),
                var_export($expected, true)
            ));
        }
    }

    protected function assertNull($actual, string $message = 'Failed asserting that a value is null'): void
    {
        $this->assertSame(null, $actual, $message);
    }

    protected function assertNotNull($actual, string $message = 'Failed asserting that a value is not null'): void
    {
        $this->assertFalse($actual === null, $message);
    }
}
