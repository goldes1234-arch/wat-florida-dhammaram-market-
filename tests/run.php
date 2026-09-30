<?php

/**
 * Dependency-free test runner. Usage: php tests/run.php
 * Discovers every tests/**\/*Test.php, expects a class name mirroring the file's
 * path under the Tests\ namespace (tests/Unit/FooTest.php -> Tests\Unit\FooTest),
 * and runs every public method starting with "test". Exits non-zero on failure
 * so it can be wired into a CI step later.
 */

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/TestCase.php';

function discoverTestFiles(string $dir): array
{
    $files = [];
    foreach (glob($dir . '/*') ?: [] as $path) {
        if (is_dir($path)) {
            $files = array_merge($files, discoverTestFiles($path));
        } elseif (str_ends_with($path, 'Test.php')) {
            $files[] = $path;
        }
    }
    return $files;
}

$testsRoot = __DIR__;
$files = discoverTestFiles($testsRoot);
sort($files);

$total = 0;
$passed = 0;
$failures = [];

foreach ($files as $file) {
    require_once $file;

    $relative = substr($file, strlen($testsRoot) + 1, -4); // strip "tests/" prefix and ".php" suffix
    $fqcn = 'Tests\\' . str_replace('/', '\\', $relative);

    if (!class_exists($fqcn)) {
        echo "SKIP  could not resolve class $fqcn from $file\n";
        continue;
    }

    foreach (get_class_methods($fqcn) as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }

        $total++;
        $label = $fqcn . '::' . $method;
        $instance = new $fqcn();

        try {
            $instance->setUp();
            $instance->$method();
            $passed++;
            echo "PASS  $label\n";
        } catch (\Throwable $e) {
            $failures[] = $label . ' — ' . $e->getMessage();
            echo "FAIL  $label — " . $e->getMessage() . "\n";
        } finally {
            try {
                $instance->tearDown();
            } catch (\Throwable $e) {
                echo "WARN  tearDown failed for $label — " . $e->getMessage() . "\n";
            }
        }
    }
}

echo str_repeat('-', 60) . "\n";
echo "$passed / $total passed\n";

if ($failures) {
    echo "\nFailures:\n";
    foreach ($failures as $f) {
        echo " - $f\n";
    }
    exit(1);
}

exit(0);
