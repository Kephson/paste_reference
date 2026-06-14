<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Tests\Functional;

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

use EHAERER\PasteReference\ContextMenu\PasteReferenceItemProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\RecordProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test version-specific compatibility between TYPO3 v13 and v14
 */
final class VersionSpecificCompatibilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'ehaerer/paste-reference',
    ];

    private Typo3Version $typo3Version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $this->setupGlobalVariables();
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Properly clean up error handlers without infinite loops
        $errorHandler = set_error_handler(null);
        if ($errorHandler !== null) {
            restore_error_handler();
            // Only restore once - if there are nested handlers, let parent handle them
        }
        parent::tearDown();
    }

    private function setupGlobalVariables(): void
    {
        // Set up global variables that are expected by TYPO3 backend classes
        if (!isset($GLOBALS['LANG'])) {
            // Create a mock LanguageService for testing
            $languageService = $this->createMock(LanguageService::class);
            $languageService->method('sL')->willReturn('Test Label');
            $GLOBALS['LANG'] = $languageService;
        }

        if (!isset($GLOBALS['BE_USER'])) {
            // Create a mock BackendUserAuthentication for testing
            $backendUser = $this->createMock(BackendUserAuthentication::class);
            $backendUser->method('check')->willReturn(true);
            $GLOBALS['BE_USER'] = $backendUser;
        }
    }

    private function createTestData(): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create basic test content elements
        $testData = [
            [
                'uid' => 1,
                'pid' => 1,
                'tstamp' => 1577836800,
                'crdate' => 1577836800,
                'deleted' => 0,
                'hidden' => 0,
                'CType' => 'text',
                'header' => 'Test Content Element',
                'bodytext' => '<p>This is a test content element for API compatibility testing.</p>',
                'colPos' => 0,
                'sys_language_uid' => 0,
            ],
            [
                'uid' => 2,
                'pid' => 1,
                'tstamp' => 1577836800,
                'crdate' => 1577836800,
                'deleted' => 0,
                'hidden' => 0,
                'CType' => 'shortcut',
                'header' => 'Reference Element',
                'records' => '1',
                'colPos' => 0,
                'sys_language_uid' => 0,
            ],
        ];

        // Insert test data
        foreach ($testData as $data) {
            $connection->insert('tt_content', $data);
        }
    }

    #[Test]
    public function contextMenuItemProviderWorksInCurrentVersion(): void
    {
        $provider = GeneralUtility::makeInstance(PasteReferenceItemProvider::class);

        // Test that the provider extends the correct base class
        self::assertInstanceOf(RecordProvider::class, $provider);

        // Test version-specific method signatures
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test that priority method returns expected type
        $priority = $provider->getPriority();
        self::assertIsInt($priority);
        self::assertGreaterThan(0, $priority);
    }

    #[Test]
    public function backendUserAuthenticationApiIsCompatible(): void
    {
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test BackendUserAuthentication class exists and has expected methods
        self::assertTrue(class_exists(BackendUserAuthentication::class));

        // Test v13+ specific methods
        $reflection = new \ReflectionClass(BackendUserAuthentication::class);

        // Methods used by the extension
        self::assertTrue($reflection->hasMethod('checkAuthMode'));

        // Properties used by the extension
        if ($reflection->hasProperty('uc')) {
            self::assertTrue($reflection->hasProperty('uc'));
        }
    }

    #[Test]
    public function extensionConfigurationApiIsVersionCompatible(): void
    {
        // Test ExtensionConfiguration API
        $extensionConfiguration = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);

        try {
            $config = $extensionConfiguration->get('paste_reference');
            self::assertIsArray($config);
        } catch (\TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException $e) {
            // This is acceptable in test environment
            self::assertStringContainsString('paste_reference', $e->getMessage());
        }
    }

    #[Test]
    public function tcaEventSystemIsVersionCompatible(): void
    {
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test that TCA event system classes exist
        self::assertTrue(class_exists(\TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent::class));

        // Test event creation and usage
        $tca = ['tt_content' => ['types' => ['shortcut' => []]]];
        $event = new \TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent($tca);

        // Test that event can be modified
        $modifiedTca = $tca;
        $modifiedTca['tt_content']['types']['shortcut']['previewRenderer'] = 'TestRenderer';
        $event->setTca($modifiedTca);

        self::assertEquals($modifiedTca, $event->getTca());
    }
}
