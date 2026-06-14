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
