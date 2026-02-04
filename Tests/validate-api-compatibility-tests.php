<?php

declare(strict_types=1);

/**
 * Validation script for API compatibility tests
 * This script validates that the API compatibility tests are properly structured
 * and can be executed in the CI environment.
 */

echo "TYPO3 API Compatibility Tests Validation\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check if test files exist
$testFiles = [
    'Tests/Functional/ApiCompatibilityTest.php',
    'Tests/Functional/VersionSpecificCompatibilityTest.php', 
    'Tests/Functional/ShortcutPreviewRendererCompatibilityTest.php',
    'Tests/Unit/DeprecatedApiDetectionTest.php',
    'Tests/phpunit.xml',
    'Tests/Fixtures/tt_content.csv'
];

echo "1. Checking test file structure...\n";
foreach ($testFiles as $file) {
    if (file_exists($file)) {
        $success[] = "✓ {$file} exists";
    } else {
        $errors[] = "✗ {$file} missing";
    }
}

// Check test file syntax
echo "\n2. Validating PHP syntax...\n";
$phpFiles = [
    'Tests/Functional/ApiCompatibilityTest.php',
    'Tests/Functional/VersionSpecificCompatibilityTest.php',
    'Tests/Functional/ShortcutPreviewRendererCompatibilityTest.php',
    'Tests/Unit/DeprecatedApiDetectionTest.php'
];

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $output = [];
        $returnCode = 0;
        exec("php -l {$file} 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            $success[] = "✓ {$file} syntax valid";
        } else {
            $errors[] = "✗ {$file} syntax error: " . implode(' ', $output);
        }
    }
}

// Check test class structure
echo "\n3. Validating test class structure...\n";
foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for required elements
        $checks = [
            'namespace' => '/namespace\s+EHAERER\\\\PasteReference\\\\Tests/',
            'class' => '/class\s+\w+Test\s+extends/',
            'test_methods' => '/#\[Test\]/',
            'use_statements' => '/use\s+PHPUnit\\\\Framework\\\\Attributes\\\\Test;/'
        ];
        
        foreach ($checks as $checkName => $pattern) {
            if (preg_match($pattern, $content)) {
                $success[] = "✓ {$file} has {$checkName}";
            } else {
                $warnings[] = "⚠ {$file} missing {$checkName}";
            }
        }
    }
}

// Check for TYPO3 API usage patterns
echo "\n4. Checking TYPO3 API usage patterns...\n";
$apiPatterns = [
    'ConnectionPool' => '/ConnectionPool/',
    'GeneralUtility' => '/GeneralUtility::makeInstance/',
    'DataHandler' => '/DataHandler/',
    'Typo3Version' => '/Typo3Version/',
    'ExtensionConfiguration' => '/ExtensionConfiguration/'
];

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        foreach ($apiPatterns as $apiName => $pattern) {
            if (preg_match($pattern, $content)) {
                $success[] = "✓ {$file} tests {$apiName} API";
            }
        }
    }
}

// Check PHPUnit configuration
echo "\n5. Validating PHPUnit configuration...\n";
if (file_exists('Tests/phpunit.xml')) {
    $xml = simplexml_load_file('Tests/phpunit.xml');
    if ($xml !== false) {
        $success[] = "✓ PHPUnit configuration is valid XML";
        
        // Check for test suites
        $testsuites = $xml->xpath('//testsuite[@name="api-compatibility"]');
        if (!empty($testsuites)) {
            $success[] = "✓ API compatibility test suite configured";
        } else {
            $warnings[] = "⚠ API compatibility test suite not found in configuration";
        }
    } else {
        $errors[] = "✗ PHPUnit configuration is invalid XML";
    }
}

// Check test fixtures
echo "\n6. Validating test fixtures...\n";
if (file_exists('Tests/Fixtures/tt_content.csv')) {
    $csvContent = file_get_contents('Tests/Fixtures/tt_content.csv');
    $lines = explode("\n", trim($csvContent));
    
    if (count($lines) >= 2) {
        $success[] = "✓ Test fixtures contain data";
    } else {
        $warnings[] = "⚠ Test fixtures appear to be empty";
    }
    
    // Check for required columns
    $header = $lines[0] ?? '';
    $requiredColumns = ['uid', 'pid', 'CType', 'header'];
    
    foreach ($requiredColumns as $column) {
        if (strpos($header, $column) !== false) {
            $success[] = "✓ Test fixtures contain {$column} column";
        } else {
            $warnings[] = "⚠ Test fixtures missing {$column} column";
        }
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "VALIDATION SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

if (!empty($success)) {
    echo "SUCCESS (" . count($success) . " items):\n";
    foreach ($success as $item) {
        echo "  {$item}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "WARNINGS (" . count($warnings) . " items):\n";
    foreach ($warnings as $item) {
        echo "  {$item}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORS (" . count($errors) . " items):\n";
    foreach ($errors as $item) {
        echo "  {$item}\n";
    }
    echo "\n";
}

// Final result
if (empty($errors)) {
    echo "✅ VALIDATION PASSED - API compatibility tests are ready for execution\n";
    echo "\nNext steps:\n";
    echo "1. Run 'composer install' to install dependencies\n";
    echo "2. Execute tests with: vendor/bin/phpunit --configuration Tests/phpunit.xml --testsuite api-compatibility\n";
    echo "3. Or use the test runner: Tests/Scripts/run-tests.sh test all\n";
    exit(0);
} else {
    echo "❌ VALIDATION FAILED - Please fix the errors above\n";
    exit(1);
}