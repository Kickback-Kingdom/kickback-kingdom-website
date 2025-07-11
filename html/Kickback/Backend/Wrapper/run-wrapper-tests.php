<?php
declare(strict_types=1);

/**
 * Test Runner for Kickback API Wrapper Unit Tests
 * 
 * Usage:
 *   From command line: php run-wrapper-tests.php
 *   From IDE: Run this file directly
 *   From project root: php html/Kickback/Backend/Wrapper/run-wrapper-tests.php
 */

echo("🧪 Kickback API Wrapper Test Suite\n");
echo("================================\n\n");

// Enable assertions for testing
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_BAIL, 1);

try {
    // Include and run wrapper unit tests
    require_once __DIR__ . '/ApiWrapperTest.php';
    
    // Run the tests
    \Kickback\Backend\Wrapper\Tests\ApiWrapperTest::unittest();
    
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

echo("📊 Test Summary:\n");
echo("   - ItemAPI component structure\n");
echo("   - JavaScript generation\n");
echo("   - Backward compatibility\n");
echo("   - Wrapper file structure\n");
echo("   - Error handling patterns\n");
echo("\n");

echo("📁 Location: /html/Kickback/Backend/Wrapper/\n");
echo("🔧 Framework Integration: Ready for Kickback ecosystem\n\n");
?> 