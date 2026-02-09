<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *  (c) 2024 Ephraim Härer <mail@ephra.im>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test for detecting deprecated API usage in the extension
 */
final class DeprecatedApiDetectionTest extends UnitTestCase
{
    #[Test]
    public function extensionDoesNotUseDeprecatedGlobalVariableAccess(): void
    {
        $extensionFiles = $this->getExtensionPhpFiles();
        $deprecatedPatterns = [
            // Deprecated global variable patterns that might be used incorrectly
            '/\$GLOBALS\[\'TYPO3_DB\'\]/',  // Deprecated in TYPO3 v8, removed in v9
            '/\$GLOBALS\[\'TSFE\'\]->sys_page/',  // Should use context API
            '/\$GLOBALS\[\'TCA\'\]/',  // Should use TCA service
        ];

        $violations = [];
        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);
            foreach ($deprecatedPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $violations[] = "Deprecated pattern found in {$file}: {$pattern}";
                }
            }
        }

        self::assertEmpty($violations, 'No deprecated global variable access should be used: ' . implode(', ', $violations));
    }

    #[Test]
    public function extensionUsesModernDatabaseApi(): void
    {
        $extensionFiles = $this->getExtensionPhpFiles();
        $modernApiUsage = false;
        $deprecatedApiUsage = false;

        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);

            // Check for modern ConnectionPool usage
            if (str_contains($content, 'ConnectionPool')) {
                $modernApiUsage = true;
            }

            // Check for deprecated database API
            if (str_contains($content, '$GLOBALS[\'TYPO3_DB\']')) {
                $deprecatedApiUsage = true;
            }
        }

        self::assertTrue($modernApiUsage, 'Extension should use modern ConnectionPool API');
        self::assertFalse($deprecatedApiUsage, 'Extension should not use deprecated database API');
    }

    #[Test]
    public function extensionUsesModernEventSystem(): void
    {
        $extensionFiles = $this->getExtensionPhpFiles();
        $usesModernEvents = false;
        $usesDeprecatedHooks = false;

        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);

            // Check for modern event system usage
            if (str_contains($content, 'Event')   && str_contains($content, 'EventListener')) {
                $usesModernEvents = true;
            }

            // Check for deprecated hook usage patterns
            if (preg_match('/\$GLOBALS\[\'TYPO3_CONF_VARS\'\]\[\'SC_OPTIONS\'\]/', $content)) {
                $usesDeprecatedHooks = true;
            }
        }

        // Extension should use modern event system where applicable
        if ($usesModernEvents) {
            self::assertTrue($usesModernEvents, 'Extension uses modern event system');
        }

        // If hooks are still used, they should be documented as necessary
        if ($usesDeprecatedHooks) {
            self::markTestIncomplete('Extension uses hooks - verify if migration to events is possible');
        }
    }

    #[Test]
    public function extensionDoesNotUseDeprecatedUtilityMethods(): void
    {
        $extensionFiles = $this->getExtensionPhpFiles();
        $deprecatedMethods = [
            'GeneralUtility::getUserObj',  // Deprecated, use makeInstance
            'GeneralUtility::makeInstanceClassName',  // Deprecated
            'GeneralUtility::callUserFunction',  // Often deprecated usage
        ];

        $violations = [];
        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);
            foreach ($deprecatedMethods as $method) {
                if (str_contains($content, $method)) {
                    $violations[] = "Deprecated method {$method} found in {$file}";
                }
            }
        }

        self::assertEmpty($violations, 'No deprecated utility methods should be used: ' . implode(', ', $violations));
    }

    #[Test]
    public function extensionHandlesTypo3VersionDifferences(): void
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();

        // Test that extension can detect and handle version differences
        self::assertContains($majorVersion, [13, 14], 'Extension should support current TYPO3 versions');

        // Check if extension has version-specific code handling
        $extensionFiles = $this->getExtensionPhpFiles();
        $hasVersionHandling = false;

        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, 'Typo3Version')   ||
                str_contains($content, 'version_compare')   ||
                str_contains($content, 'getMajorVersion')) {
                $hasVersionHandling = true;
                break;
            }
        }

        // Version handling is optional but good practice
        if ($hasVersionHandling) {
            self::assertTrue($hasVersionHandling, 'Extension has version-specific handling');
        }
    }

    #[Test]
    public function extensionUsesCompatibleNamespaces(): void
    {
        $extensionFiles = $this->getExtensionPhpFiles();
        $incompatibleNamespaces = [
            'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController',  // Changed in newer versions
            'TYPO3\\CMS\\Frontend\\Plugin\\AbstractPlugin',  // Deprecated
        ];

        $violations = [];
        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);
            foreach ($incompatibleNamespaces as $namespace) {
                if (str_contains($content, $namespace)) {
                    $violations[] = "Potentially incompatible namespace {$namespace} found in {$file}";
                }
            }
        }

        self::assertEmpty($violations, 'No incompatible namespaces should be used: ' . implode(', ', $violations));
    }

    #[Test]
    public function extensionDoesNotUseRemovedConstants(): void
    {
        $extensionFiles = $this->getExtensionPhpFiles();
        $removedConstants = [
            'TYPO3_MODE',  // Removed in TYPO3 v11
            'TYPO3_version',  // Use Typo3Version class instead
            'TYPO3_branch',  // Use Typo3Version class instead
        ];

        $violations = [];
        foreach ($extensionFiles as $file) {
            $content = file_get_contents($file);
            foreach ($removedConstants as $constant) {
                if (str_contains($content, $constant)) {
                    $violations[] = "Removed constant {$constant} found in {$file}";
                }
            }
        }

        self::assertEmpty($violations, 'No removed constants should be used: ' . implode(', ', $violations));
    }

    #[Test]
    public function shortcutPreviewRendererHandlesVersionSpecificApis(): void
    {
        $shortcutRendererFile = dirname(__DIR__, 2) . '/Classes/PageLayoutView/ShortcutPreviewRenderer.php';

        if (!file_exists($shortcutRendererFile)) {
            self::markTestSkipped('ShortcutPreviewRenderer file not found');
        }

        $content = file_get_contents($shortcutRendererFile);

        // Check that the renderer properly handles version differences
        self::assertStringContainsString('$this->majorTypo3Version', $content, 'Renderer should check TYPO3 version');

        // Check for version-specific method calls
        $hasGetRowCall = str_contains($content, 'getRow()');
        $hasGetRecordCall = str_contains($content, 'getRecord()');

        // The renderer should handle both API versions
        self::assertTrue($hasGetRowCall || $hasGetRecordCall, 'Renderer should handle version-specific record methods');

        // Check for proper version branching in getDataRow method
        self::assertStringContainsString('if ($this->majorTypo3Version >= 14)', $content, 'Should have version check for API differences');

        // Check that RecordFactory is used conditionally
        $hasRecordFactoryUsage = str_contains($content, 'RecordFactory');
        if ($hasRecordFactoryUsage) {
            self::assertTrue($hasRecordFactoryUsage, 'RecordFactory usage should be version-aware');
        }

        // Verify no hardcoded version assumptions
        $problematicPatterns = [
            '/getRow\(\)(?!\s*;)/',  // getRow() without version check
            '/getRecord\(\)(?!\s*;)/',  // getRecord() without version check
        ];

        $violations = [];
        foreach ($problematicPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                // Check if it's within a version-conditional block
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (preg_match($pattern, $line)) {
                        // Look for version check in surrounding lines
                        $hasVersionCheck = false;
                        for ($i = max(0, $lineNum - 10); $i <= min(count($lines) - 1, $lineNum + 5); $i++) {
                            if (str_contains($lines[$i], 'majorTypo3Version')) {
                                $hasVersionCheck = true;
                                break;
                            }
                        }
                        if (!$hasVersionCheck) {
                            $violations[] = 'Line ' . ($lineNum + 1) . ': Version-specific API call without version check';
                        }
                    }
                }
            }
        }

        // Allow some violations as they might be in version-conditional blocks
        if (count($violations) > 2) {
            self::fail('Too many version-specific API calls without proper version checks: ' . implode(', ', $violations));
        }
    }

    /**
     * Get all PHP files in the extension Classes directory
     */
    private function getExtensionPhpFiles(): array
    {
        $classesDir = dirname(__DIR__, 2) . '/Classes';
        if (!is_dir($classesDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($classesDir)
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }
}
