<?php

/**
 * Minimal unit test runner — zero dependencies, pure PHP.
 *
 * Provides a simple assertion-based testing framework inspired by PHPUnit
 * but without any external dependency. Designed for didactic purposes:
 * students can read and understand the entire test infrastructure.
 *
 * Usage:
 *     require_once __DIR__ . '/TestRunner.php';
 *
 *     $t = new TestRunner();
 *
 *     $t->test('something works', function () use ($t) {
 *         $t->assertEqual(1 + 1, 2);
 *         $t->assertTrue(true);
 *     });
 *
 *     $t->run();
 *
 * Run from the command line:
 *     php tests/run.php
 */

declare(strict_types=1);

class TestRunner
{
    /** @var array<array{name: string, fn: callable}> Registered test cases */
    private array $tests = [];

    /** @var int Number of passed tests */
    private int $passed = 0;

    /** @var int Number of failed tests */
    private int $failed = 0;

    /** @var array<string> Failure messages for the summary */
    private array $failures = [];

    /**
     * Register a named test case.
     *
     * @param string   $name Description of what is being tested
     * @param callable $fn   Closure containing assertions
     * @return void
     */
    public function test(string $name, callable $fn): void
    {
        $this->tests[] = ['name' => $name, 'fn' => $fn];
    }

    /**
     * Execute all registered tests and print results.
     *
     * @return void Exits with code 1 if any test failed, 0 otherwise
     */
    public function run(): void
    {
        echo "\n  Bibliotheca Test Suite\n";
        echo str_repeat('=', 50) . "\n\n";

        foreach ($this->tests as $test) {
            try {
                ($test['fn'])();
                $this->passed++;
                echo "  PASS  {$test['name']}\n";
            } catch (\Exception $e) {
                $this->failed++;
                $msg = "  FAIL  {$test['name']}: {$e->getMessage()}";
                $this->failures[] = $msg;
                echo "{$msg}\n";
            }
        }

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "  Results: {$this->passed} passed, {$this->failed} failed, "
           . count($this->tests) . " total\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    /**
     * Assert that two values are strictly equal (===).
     *
     * @param  mixed  $expected The expected value
     * @param  mixed  $actual   The actual value
     * @param  string $message  Optional failure message
     * @return void
     * @throws \Exception If the assertion fails
     */
    public function assertEqual(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true)
                 . ", got " . var_export($actual, true);
            throw new \Exception($msg);
        }
    }

    /**
     * Assert that a value is true.
     *
     * @param  bool   $value   The value to check
     * @param  string $message Optional failure message
     * @return void
     * @throws \Exception If the value is not true
     */
    public function assertTrue(bool $value, string $message = ''): void
    {
        if ($value !== true) {
            throw new \Exception($message ?: "Expected true, got false");
        }
    }

    /**
     * Assert that a value is false.
     *
     * @param  bool   $value   The value to check
     * @param  string $message Optional failure message
     * @return void
     * @throws \Exception If the value is not false
     */
    public function assertFalse(bool $value, string $message = ''): void
    {
        if ($value !== false) {
            throw new \Exception($message ?: "Expected false, got true");
        }
    }

    /**
     * Assert that a value is null.
     *
     * @param  mixed  $value   The value to check
     * @param  string $message Optional failure message
     * @return void
     * @throws \Exception If the value is not null
     */
    public function assertNull(mixed $value, string $message = ''): void
    {
        if ($value !== null) {
            throw new \Exception($message ?: "Expected null, got " . var_export($value, true));
        }
    }

    /**
     * Assert that a value is not null.
     *
     * @param  mixed  $value   The value to check
     * @param  string $message Optional failure message
     * @return void
     * @throws \Exception If the value is null
     */
    public function assertNotNull(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            throw new \Exception($message ?: "Expected non-null value, got null");
        }
    }

    /**
     * Assert that an array has a specific number of elements.
     *
     * @param  int    $expected Expected count
     * @param  array  $array    The array to check
     * @param  string $message  Optional failure message
     * @return void
     * @throws \Exception If the count does not match
     */
    public function assertCount(int $expected, array $array, string $message = ''): void
    {
        $actual = count($array);
        if ($actual !== $expected) {
            throw new \Exception($message ?: "Expected count {$expected}, got {$actual}");
        }
    }

    /**
     * Assert that a value is greater than another.
     *
     * @param  mixed  $expected The lower bound (exclusive)
     * @param  mixed  $actual   The value that should be greater
     * @param  string $message  Optional failure message
     * @return void
     * @throws \Exception If actual is not greater than expected
     */
    public function assertGreaterThan(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($actual <= $expected) {
            throw new \Exception($message ?: "Expected value greater than {$expected}, got {$actual}");
        }
    }
}
