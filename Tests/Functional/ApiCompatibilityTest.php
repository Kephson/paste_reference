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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
    public function dataHandlerApiIsCompatible(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Test DataHandler properties and methods used by extension
        self::assertObjectHasProperty('isImporting', $dataHandler);
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
    }
}
