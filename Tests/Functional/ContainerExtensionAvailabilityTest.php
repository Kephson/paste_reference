<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Simple test to verify container extension availability in CI environment
 */
final class ContainerExtensionAvailabilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'ehaerer/paste-reference',
    ];

    protected function setUp(): void
    {
        // Add container extension if available
        if (class_exists('B13\\Container\\Tca\\Registry')) {
            $this->testExtensionsToLoad[] = 'b13/container';
        }
        
        parent::setUp();
    }

    #[Test]
    public function containerExtensionClassesAreAvailable(): void
    {
        // This test should pass in CI environment after installing b13/container
        self::assertTrue(
            class_exists('B13\\Container\\Tca\\Registry'),
            'Container extension Registry class should be available after composer install'
        );
        
        self::assertTrue(
            class_exists('B13\\Container\\Tca\\ContainerConfiguration'),
            'Container extension ContainerConfiguration class should be available'
        );
    }

    #[Test]
    public function containerExtensionCanBeInstantiated(): void
    {
        if (!class_exists('B13\\Container\\Tca\\Registry')) {
            self::markTestSkipped('Container extension not available');
        }

        $registry = GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class);
        self::assertInstanceOf(\B13\Container\Tca\Registry::class, $registry);
    }

    #[Test]
    public function containerExtensionIsLoaded(): void
    {
        if (!class_exists('B13\\Container\\Tca\\Registry')) {
            self::markTestSkipped('Container extension not available');
        }

        // After proper setup, the extension should be loaded
        $isLoaded = ExtensionManagementUtility::isLoaded('container');
        
        if (!$isLoaded) {
            // Provide helpful debug information
            $loadedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
            self::fail(
                'Container extension is not loaded. Available extensions: ' . 
                implode(', ', $loadedExtensions)
            );
        }

        self::assertTrue($isLoaded, 'Container extension should be loaded');
    }

    #[Test]
    public function pasteReferenceExtensionIsLoaded(): void
    {
        // Verify our own extension is loaded
        self::assertTrue(
            ExtensionManagementUtility::isLoaded('paste_reference'),
            'Paste reference extension should be loaded'
        );
    }
}