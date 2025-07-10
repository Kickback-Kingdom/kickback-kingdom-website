<?php
declare(strict_types=1);

namespace Kickback\ApiWrapper\Tests;

require_once __DIR__ . '/item-api.php';

/**
 * Unit tests for JavaScript API Wrapper components
 * Follows the project's standard unit testing pattern
 */
class WrapperUnitTest
{
    /**
     * Test ItemAPI PHP component functionality
     */
    private static function unittest_itemAPI(): void
    {
        echo("  unittest_itemAPI()\n");
        
        // Test ItemAPI class exists and can be instantiated
        assert(class_exists('ItemAPI'), 'ItemAPI class should exist');
        
        $itemAPI = new \ItemAPI();
        assert($itemAPI instanceof \ItemAPI, 'ItemAPI should be instantiable');
        
        // Test that getById method exists
        assert(method_exists($itemAPI, 'getById'), 'ItemAPI should have getById method');
        assert(method_exists($itemAPI, 'getByIds'), 'ItemAPI should have getByIds method');
        
        // Note: We can't easily test actual API calls in unit tests without mocking
        // Those would be integration tests. Here we test the structure.
        
        echo("    ✓ ItemAPI class structure validated\n");
    }
    
    /**
     * Test that the ItemAPI wrapper generates proper JavaScript
     */
    private static function unittest_itemAPIJavaScript(): void
    {
        echo("  unittest_itemAPIJavaScript()\n");
        
        // Capture the JavaScript output
        ob_start();
        $itemAPI = new \ItemAPI();
        $itemAPI->renderJavaScript();
        $jsOutput = ob_get_clean();
        
        // Verify JavaScript contains expected patterns
        assert(strpos($jsOutput, 'class ItemAPI') !== false, 'JavaScript should contain ItemAPI class');
        assert(strpos($jsOutput, 'async getById(') !== false, 'JavaScript should contain getById method');
        assert(strpos($jsOutput, 'async getByIds(') !== false, 'JavaScript should contain getByIds method');
        assert(strpos($jsOutput, '/api/v1/item/get.php') !== false, 'JavaScript should reference correct API endpoint');
        
        echo("    ✓ ItemAPI JavaScript generation validated\n");
    }
    
    /**
     * Test the backward compatibility function
     */
    private static function unittest_backwardCompatibility(): void
    {
        echo("  unittest_backwardCompatibility()\n");
        
        // Test that GetItemInformationById function exists
        assert(function_exists('GetItemInformationById'), 'GetItemInformationById function should exist for backward compatibility');
        
        // Test that it returns a string (JavaScript function call)
        $result = GetItemInformationById(1);
        assert(is_string($result), 'GetItemInformationById should return a string');
        assert(strpos($result, 'kickbackApi.item.getById(1)') !== false, 'Should return proper wrapper call');
        
        echo("    ✓ Backward compatibility validated\n");
    }
    
    /**
     * Test wrapper configuration and structure
     */
    private static function unittest_wrapperStructure(): void
    {
        echo("  unittest_wrapperStructure()\n");
        
        // Test that the wrapper JavaScript file exists
        $wrapperPath = __DIR__ . '/kickback-api-wrapper.js';
        assert(file_exists($wrapperPath), 'kickback-api-wrapper.js should exist');
        
        // Read and validate wrapper content
        $wrapperContent = file_get_contents($wrapperPath);
        assert($wrapperContent !== false, 'Should be able to read wrapper file');
        
        // Check for key components
        assert(strpos($wrapperContent, 'class KickbackAPI') !== false, 'Wrapper should contain KickbackAPI class');
        assert(strpos($wrapperContent, 'get item()') !== false, 'Wrapper should have item getter');
        assert(strpos($wrapperContent, 'version:') !== false, 'Wrapper should have version property');
        assert(strpos($wrapperContent, 'getStatus()') !== false, 'Wrapper should have getStatus method');
        
        echo("    ✓ Wrapper structure validated\n");
    }
    
    /**
     * Main unit test entry point
     */
    public static function unittest(): void
    {
        echo("Running `\\Kickback\\ApiWrapper\\Tests\\WrapperUnitTest::unittest()`\n");
        
        self::unittest_itemAPI();
        self::unittest_itemAPIJavaScript();
        self::unittest_backwardCompatibility();
        self::unittest_wrapperStructure();
        
        echo("  ... all tests passed.\n\n");
    }
}

// Allow running this test directly from command line or including in test suite
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    WrapperUnitTest::unittest();
}
?> 