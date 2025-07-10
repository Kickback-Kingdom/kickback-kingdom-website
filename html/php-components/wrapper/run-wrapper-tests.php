<?php
declare(strict_types=1);

/**
 * Test Runner for API Wrapper Unit Tests
 * 
 * Usage:
 *   From command line: php run-wrapper-tests.php
 *   From IDE: Run this file directly
 */

echo("🧪 Kickback API Wrapper Test Suite\n");
echo("================================\n\n");

// Enable assertions for testing (following project pattern)
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_BAIL, 1);

try {
    // Include and run wrapper unit tests (no framework init needed for these tests)
    require_once __DIR__ . '/ApiWrapperTest.php';
    
    // Run the tests
    \Kickback\ApiWrapper\Tests\WrapperUnitTest::unittest();
    
    echo("✅ All tests completed successfully!\n\n");
    
} catch (AssertionError $e) {
    echo("❌ Test Failed: " . $e->getMessage() . "\n");
    echo("   File: " . $e->getFile() . "\n");
    echo("   Line: " . $e->getLine() . "\n\n");
    exit(1);
    
} catch (Exception $e) {
    echo("💥 Unexpected Error: " . $e->getMessage() . "\n");
    echo("   File: " . $e->getFile() . "\n");
    echo("   Line: " . $e->getLine() . "\n\n");
    exit(1);
}

?> 