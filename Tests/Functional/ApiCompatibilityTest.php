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

use EHAERER\PasteReference\DataHandler\ProcessCmdmap;
use EHAERER\PasteReference\Domain\Repository\TtContentRepository;
use EHAERER\PasteReference\EventListener\AfterTcaCompilationEventListener;
use EHAERER\PasteReference\Helper\BackendHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test TYPO3 API compatibility across versions v13 and v14
 */
final class ApiCompatibilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'ehaerer/paste-reference',
    ];

    private Typo3Version $typo3Version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
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
                'l18n_parent' => 0,
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
                'l18n_parent' => 0,
            ],
        ];

        // Insert test data
        foreach ($testData as $data) {
            $connection->insert('tt_content', $data);
        }
    }

    #[Test]
    public function extensionIsLoadedInCurrentTypo3Version(): void
    {
        self::assertTrue(ExtensionManagementUtility::isLoaded('paste_reference'));

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();

        // Verify extension works with supported TYPO3 versions
        self::assertContains($majorVersion, [13, 14], 'Extension should work with TYPO3 v13 and v14');
    }

    #[Test]
    public function connectionPoolApiIsCompatible(): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        // Test restrictions API
        $restrictions = $queryBuilder->getRestrictions();
        self::assertInstanceOf(\TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface::class, $restrictions);
    }

    #[Test]
    public function dataHandlerApiIsCompatible(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Test DataHandler properties and methods used by extension
        self::assertObjectHasProperty('isImporting', $dataHandler);
    }

    #[Test]
    public function TtContentRepositoryUsesCompatibleApis(): void
    {
        $ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);

        // Test database query methods
        $queryBuilder = $ttContentRepository->getQueryBuilder('tt_content');
        self::assertTrue(is_object($queryBuilder), 'QueryBuilder can be retrieved from TtContentRepository');
    }

    #[Test]
    public function processCmdmapUsesCompatibleApis(): void
    {
        $processCmdmap = GeneralUtility::makeInstance(ProcessCmdmap::class);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Test initialization with compatible parameters
        $processCmdmap->init('tt_content', 1, $dataHandler);

        self::assertEquals('tt_content', $processCmdmap->getTable());
        self::assertEquals(1, $processCmdmap->getPageUid());
        self::assertSame($dataHandler, $processCmdmap->getDataHandler());
    }

    #[Test]
    public function extensionConfigurationApiIsCompatible(): void
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        // Test that extension configuration can be retrieved
        try {
            $config = $extensionConfiguration->get('paste_reference');
            // Configuration might be empty, but method should work
            self::assertIsArray($config);
        } catch (\TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException $e) {
            // This is acceptable in test environment
            self::assertStringContainsString('paste_reference', $e->getMessage());
        }
    }

    #[Test]
    public function tcaEventListenerIsCompatible(): void
    {
        $listener = GeneralUtility::makeInstance(AfterTcaCompilationEventListener::class);

        // Create a mock TCA array
        $tca = [
            'tt_content' => [
                'types' => [
                    'shortcut' => [],
                ],
            ],
        ];

        $event = new AfterTcaCompilationEvent($tca);

        // Test that event listener can process TCA without errors
        try {
            $listener($event);
            self::assertTrue(true, 'TCA event listener executed without errors');
        } catch (\TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException $e) {
            // This is acceptable when extension configuration is not set
            self::assertStringContainsString('paste_reference', $e->getMessage());
        }
    }

    #[Test]
    public function globalVariablesAreAccessibleAcrossVersions(): void
    {
        // Test TYPO3_REQUEST global (used in ProcessCmdmap)
        // In test environment, this might not be set, but we test the access pattern
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        // The extension should handle null request gracefully
        self::assertTrue(!empty($request), 'Global variables access pattern is compatible');

        // Test BE_USER global access pattern
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        self::assertTrue(is_object($backendUser), 'Backend user global access pattern is compatible');

        // Test LANG global access pattern
        $languageService = $GLOBALS['LANG'] ?? null;
        self::assertTrue(is_object($languageService), 'Language service global access pattern is compatible');
    }

    #[Test]
    public function databaseQueryRestrictionsAreCompatible(): void
    {
        $ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);
        $queryBuilder = $ttContentRepository->getQueryBuilder('tt_content');

        // Test that restriction removal methods exist and work
        $restrictions = $queryBuilder->getRestrictions();

        // These restriction classes should exist in both TYPO3 v13 and v14
        $restrictionClasses = [
            \TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction::class,
            \TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction::class,
            \TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction::class,
        ];

        foreach ($restrictionClasses as $restrictionClass) {
            self::assertTrue(class_exists($restrictionClass), "Restriction class {$restrictionClass} should exist");
        }

        // Test that removeByType method exists
        self::assertTrue(method_exists($restrictions, 'removeByType'));
    }

    #[Test]
    public function backendUtilityMethodsAreCompatible(): void
    {
        // Test BackendUtility methods used by the extension
        self::assertTrue(class_exists(\TYPO3\CMS\Backend\Utility\BackendUtility::class));
        self::assertTrue(method_exists(\TYPO3\CMS\Backend\Utility\BackendUtility::class, 'getRecordTitle'));
    }

    #[Test]
    public function generalUtilityMethodsAreCompatible(): void
    {
        // Test GeneralUtility methods used throughout the extension
        self::assertTrue(method_exists(GeneralUtility::class, 'makeInstance'));
        self::assertTrue(method_exists(GeneralUtility::class, 'fixed_lgd_cs'));

        // Test that makeInstance works with extension classes
        $backendHelper = GeneralUtility::makeInstance(BackendHelper::class);
        self::assertInstanceOf(BackendHelper::class, $backendHelper);
    }

    #[Test]
    public function shortcutPreviewRendererApiIsCompatible(): void
    {
        // Test that ShortcutPreviewRenderer can be instantiated
        $renderer = GeneralUtility::makeInstance(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class);
        self::assertInstanceOf(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class, $renderer);

        // Test that it implements required interfaces
        self::assertInstanceOf(\TYPO3\CMS\Backend\Preview\PreviewRendererInterface::class, $renderer);
        self::assertInstanceOf(\TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer::class, $renderer);

        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test version-specific API availability
        if ($majorVersion >= 14) {
            // Test TYPO3 v14+ specific APIs
            self::assertTrue(class_exists(\TYPO3\CMS\Core\Domain\RecordFactory::class), 'RecordFactory should exist in v14+');
            self::assertTrue(interface_exists(\TYPO3\CMS\Core\Domain\RecordInterface::class), 'RecordInterface should exist in v14+');

            // Test that RecordFactory can create records
            $recordFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Domain\RecordFactory::class);
            $testData = [
                'uid' => 1,
                'pid' => 1,
                'CType' => 'shortcut',
                'sys_language_uid' => 0,
                'l18n_parent' => 0,
                't3ver_wsid' => 0,
                't3ver_oid' => 0,
                't3ver_state' => 0,
            ];
            $record = $recordFactory->createFromDatabaseRow('tt_content', $testData);

            self::assertInstanceOf(\TYPO3\CMS\Core\Domain\RecordInterface::class, $record);
            self::assertTrue(method_exists($record, 'toArray'), 'RecordInterface should have toArray() method in v14+');
            self::assertTrue(method_exists($record, 'getUid'), 'RecordInterface should have getUid() method in v14+');

        } else {
            // Test TYPO3 v13 compatibility
            self::assertLessThan(14, $majorVersion, 'This should be TYPO3 v13 or below');

            // In v13, records might be handled differently
            // The renderer should still work but use different internal methods
        }

        // Test that GridColumnItem class exists (used by the renderer)
        self::assertTrue(class_exists(\TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem::class));
    }
}
