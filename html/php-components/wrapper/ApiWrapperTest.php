<?php
declare(strict_types=1);

namespace Kickback\ApiWrapper\Tests;

/**
 * Unit tests for JavaScript API Wrapper components
 * Follows the project's standard unit testing pattern
 */
class WrapperUnitTest
{
    /**
     * Test that item-api.php file exists and generates proper JavaScript
     */
    private static function unittest_itemAPIFile(): void
    {
        // Test that the item-api.php file exists
        $itemApiPath = __DIR__ . '/item-api.php';
        assert(file_exists($itemApiPath));
        
        // Mock required globals for CLI testing
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        
        // Mock the Version class since we can't initialize full framework in CLI
        if (!class_exists('Kickback\Common\Version')) {
            eval('namespace Kickback\Common { class Version { public static function formatUrl($path) { return $path; } } }');
        }
        
        // Capture the JavaScript output
        ob_start();
        include $itemApiPath;
        $jsOutput = ob_get_clean();
        
        // Test JavaScript structure
        assert(strpos($jsOutput, '<script>') !== false);
        assert(strpos($jsOutput, 'class ItemAPI') !== false);
        assert(strpos($jsOutput, 'static async getById(') !== false);
        assert(strpos($jsOutput, 'static async getByIds(') !== false);
        assert(strpos($jsOutput, '/api/v1/item/get.php') !== false);
        assert(strpos($jsOutput, '</script>') !== false);
        
        echo("  unittest_itemAPIFile()\n");
    }
    
    /**
     * Test backward compatibility functions in the JavaScript output
     */
    private static function unittest_backwardCompatibility(): void
    {
        // Mock required globals
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        
        // Mock Version class
        if (!class_exists('Kickback\Common\Version')) {
            eval('namespace Kickback\Common { class Version { public static function formatUrl($path) { return $path; } } }');
        }
        
        // Capture JavaScript output
        ob_start();
        include __DIR__ . '/item-api.php';
        $jsOutput = ob_get_clean();
        
        // Test that backward compatibility functions exist
        assert(strpos($jsOutput, 'function GetItemInformationById(') !== false);
        assert(strpos($jsOutput, 'function GetItemInformationByIdWithAPI(') !== false);
        assert(strpos($jsOutput, 'window.itemInformation') !== false);
        assert(strpos($jsOutput, 'ItemAPI component loaded') !== false);
        
        echo("  unittest_backwardCompatibility()\n");
    }
    
    /**
     * Test the wrapper JavaScript file structure
     */
    private static function unittest_wrapperJavaScript(): void
    {
        $wrapperPath = __DIR__ . '/kickback-api-wrapper.js';
        assert(file_exists($wrapperPath));
        
        $wrapperContent = file_get_contents($wrapperPath);
        assert($wrapperContent !== false);
        
        // Test for key components
        assert(strpos($wrapperContent, 'class KickbackAPI') !== false);
        assert(strpos($wrapperContent, 'get item()') !== false);
        assert(strpos($wrapperContent, 'version:') !== false);
        assert(strpos($wrapperContent, 'getStatus()') !== false);
        assert(strpos($wrapperContent, 'window.kickbackApi') !== false);
        
        echo("  unittest_wrapperJavaScript()\n");
    }
    
    /**
     * Test core wrapper files exist and are accessible
     */
    private static function unittest_coreFiles(): void
    {
        $requiredFiles = [
            'item-api.php',
            'kickback-api-wrapper.js',
            'run-wrapper-tests.php',
            'ApiWrapperTest.php'
        ];
        
        foreach ($requiredFiles as $filename) {
            $filePath = __DIR__ . '/' . $filename;
            assert(file_exists($filePath));
            assert(is_readable($filePath));
            assert(filesize($filePath) > 0);
        }
        
        echo("  unittest_coreFiles()\n");
    }
    
    /**
     * Test that generated JavaScript has proper error handling
     */
    private static function unittest_errorHandling(): void
    {
        // Mock globals
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        
        // Mock Version class
        if (!class_exists('Kickback\Common\Version')) {
            eval('namespace Kickback\Common { class Version { public static function formatUrl($path) { return $path; } } }');
        }
        
        // Capture JavaScript output
        ob_start();
        include __DIR__ . '/item-api.php';
        $jsOutput = ob_get_clean();
        
        // Test error handling patterns
        assert(strpos($jsOutput, 'try {') !== false);
        assert(strpos($jsOutput, 'catch (error)') !== false);
        assert(strpos($jsOutput, 'throw new Error(') !== false);
        assert(strpos($jsOutput, 'console.error') !== false);
        
        echo("  unittest_errorHandling()\n");
    }

    /**
     * Main unit test entry point
     */
    public static function unittest(): void
    {
        echo("Running `\\Kickback\\ApiWrapper\\Tests\\WrapperUnitTest::unittest()`\n");
        
        self::unittest_itemAPIFile();
        self::unittest_backwardCompatibility();
        self::unittest_wrapperJavaScript();
        self::unittest_coreFiles();
        self::unittest_errorHandling();
        
        echo("  ... passed.\n\n");
    }
}

// Allow running this test directly from command line
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    WrapperUnitTest::unittest();
}
?> 