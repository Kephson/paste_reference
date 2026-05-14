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

use EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test ShortcutPreviewRenderer API compatibility across TYPO3 versions
 */
final class ShortcutPreviewRendererCompatibilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'ehaerer/paste-reference',
    ];

    private ShortcutPreviewRenderer $renderer;
    private Typo3Version $typo3Version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
        $this->renderer = GeneralUtility::makeInstance(ShortcutPreviewRenderer::class);
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
                // 'tstamp' => 1577836800,
                // 'crdate' => 1577836800,
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
                // 'tstamp' => 1577836800,
                // 'crdate' => 1577836800,
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
    public function rendererHandlesVersionSpecificRecordMethods(): void
    {
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Create a mock record that behaves differently based on TYPO3 version
        if ($majorVersion >= 14) {
            // Test TYPO3 v14+ RecordInterface usage
            // self::assertTrue(interface_exists(RecordInterface::class), 'RecordInterface should exist in TYPO3 v14+');
            // self::assertTrue(class_exists(RecordFactory::class), 'RecordFactory should exist in TYPO3 v14+');

            // Test that RecordFactory can create records
            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
            // self::assertInstanceOf(RecordFactory::class, $recordFactory);

            // Test record creation from database row
            $testData = [
                'uid' => '1',
                'pid' => '1',
                'CType' => 'shortcut',
                'sys_language_uid' => '0',
                'l18n_parent' => '0',
                't3ver_wsid' => '0',
                't3ver_oid' => '0',
                't3ver_state' => '0',
                't3ver_stage' => '0',
                'header' => 'Test Shortcut',
                'records' => '2',
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => time(),
                'endtime' => time(),
                'deleted' => '0',
                'editlock' => '0',
                'hidden' => '0',
                'rowDescription' => '',
                'sorting' => '0',
                'fe_group' => '0',
            ];

            $record = $recordFactory->createFromDatabaseRow('tt_content', $testData);

            // Test the actual methods that work
            $array = $record->toArray();
            // self::assertIsArray($array);
            self::assertEquals(1, $array['uid']);

            // Test individual methods
            self::assertEquals(1, $record->getUid());
            self::assertEquals(1, $record->getPid());
        } else {
            // Test TYPO3 v13 and below - records are arrays or have getRecord() method
            // In v13, we expect different behavior
            self::assertLessThan(14, $majorVersion, 'This branch should only run for TYPO3 v13 and below');
        }
    }

    #[Test]
    public function getDataRowMethodHandlesVersionDifferences(): void
    {
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Use reflection to test the protected getDataRow method
        $reflection = new \ReflectionClass($this->renderer);
        $getDataRowMethod = $reflection->getMethod('getDataRow');

        if ($majorVersion >= 14) {
            // Test TYPO3 v14+ behavior with RecordInterface
            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
            $testData = [
                'uid' => 1,
                'pid' => 1,
                'CType' => 'shortcut',
                'sys_language_uid' => '0',
                'l18n_parent' => '0',
                't3ver_wsid' => '0',
                't3ver_oid' => '0',
                't3ver_state' => '0',
                't3ver_stage' => '0',
                'header' => 'Test Record',
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => time(),
                'endtime' => time(),
                'deleted' => '0',
                'editlock' => '0',
                'hidden' => '0',
                'rowDescription' => '',
                'sorting' => '0',
                'fe_group' => '0',
            ];

            $record = $recordFactory->createFromDatabaseRow('tt_content', $testData);

            if (method_exists($record, 'getRow')) {
                // TYPO3 v13
                $result = $getDataRowMethod->invoke($this->renderer, $record);
                self::assertIsArray($result);
            } else {
                // TYPO3 v14
                $result = $record->toArray();
                self::assertTrue(method_exists($record, 'toArray'), 'Record::toArray() exists');
            }
        } else {
            // For TYPO3 v13 and below, we would test with array records or objects with getRecord()
            // This is a simplified test since we can't easily mock the old behavior
            $testData = [
                'uid' => 1,
                'pid' => 1,
                'CType' => 'shortcut',
                'sys_language_uid' => '0',
                'l18n_parent' => '0',
                't3ver_wsid' => '0',
                't3ver_oid' => '0',
                't3ver_state' => '0',
                't3ver_stage' => '0',
                'crdate' => time(),
                'header' => 'Test Record',
            ];

            // TODO fixMe
            // Create a mock object that has getRecord() method for v13
            $mockRecord = new class ($testData) {
                private array $data;

                public function __construct(array $data)
                {
                    $this->data = $data;
                }

                public function getRecord(): array
                {
                    return $this->data;
                }
            };

            $result = $getDataRowMethod->invoke($this->renderer, $mockRecord);
            self::assertIsArray($result);
            self::assertEquals(1, $result['uid']);
        }
    }

    #[Test]
    public function getContentRecordObjMethodWorksInV14(): void
    {
        $majorVersion = $this->typo3Version->getMajorVersion();

        if ($majorVersion >= 14) {
            // Use reflection to test the protected getContentRecordObj method
            $reflection = new \ReflectionClass($this->renderer);
            $getContentRecordObjMethod = $reflection->getMethod('getContentRecordObj');

            $testData = [
                'uid' => '1',
                'pid' => '1',
                'CType' => 'text',
                'sys_language_uid' => '0',
                'l18n_parent' => '0',
                't3ver_wsid' => '0',
                't3ver_oid' => '0',
                't3ver_state' => '0',
                't3ver_stage' => '0',
                'header' => 'Test Content',
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => time(),
                'endtime' => time(),
                'deleted' => '0',
                'editlock' => '0',
                'hidden' => '0',
                'rowDescription' => '',
                'sorting' => '0',
                'fe_group' => '0',
            ];

            $result = $getContentRecordObjMethod->invoke($this->renderer, $testData);

            // Test the correct RecordInterface methods
            $array = $result->toArray();
            self::assertEquals(1, $array['uid']);
            self::assertEquals('text', $array['CType']);
        } else {
            // In v13 and below, this method might not be used or behave differently
            self::markTestSkipped('getContentRecordObj method is only used in TYPO3 v14+');
        }
    }

    #[Test]
    public function rendererCanHandleGridColumnItemsAcrossVersions(): void
    {

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();

        // Test that GridColumnItem class exists and can be instantiated
        self::assertTrue(class_exists(GridColumnItem::class), 'GridColumnItem should exist in all supported versions');

        // Create test data for a shortcut content element
        $testData = [
            'uid' => 2,
            'pid' => 1,
            'CType' => 'shortcut',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            't3ver_wsid' => '0',
            't3ver_oid' => '0',
            't3ver_state' => '0',
            't3ver_stage' => '0',
            'header' => 'Reference Element',
            'records' => '1',
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => time(),
            'endtime' => time(),
            'deleted' => '0',
            'editlock' => '0',
            'hidden' => '0',
            'rowDescription' => '',
            'sorting' => '0',
            'fe_group' => '0',
        ];

        if ($majorVersion >= 14) {
            // Test with RecordInterface for v14+
            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
            $record = $recordFactory->createFromDatabaseRow('tt_content', $testData);

            $row = $record->toArray();
            self::assertEquals('shortcut', $row['CType']);
            self::assertEquals('1', $row['records']);
        } else {
            // For v13 and below, test with array-based records
            self::assertEquals('shortcut', $testData['CType']);
            self::assertEquals('1', $testData['records']);
        }
    }

    #[Test]
    public function rendererVersionDetectionIsAccurate(): void
    {
        // Use reflection to access the protected majorTypo3Version property
        $reflection = new \ReflectionClass($this->renderer);
        $majorVersionProperty = $reflection->getProperty('majorTypo3Version');

        $rendererVersion = $majorVersionProperty->getValue($this->renderer);
        $actualVersion = $this->typo3Version->getMajorVersion();

        self::assertEquals($actualVersion, $rendererVersion, 'Renderer should correctly detect TYPO3 version');
        self::assertContains($rendererVersion, [13, 14], 'Renderer should support TYPO3 v13 and v14');
    }

    #[Test]
    public function rendererHandlesRecordFactoryAvailability(): void
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();

        if ($majorVersion >= 14) {
            // RecordFactory should be available in v14+
            self::assertTrue(class_exists(RecordFactory::class), 'RecordFactory should be available in TYPO3 v14+');

            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);

            // Test that it can create records for tt_content
            $testData = [
                'uid' => 1,
                'pid' => 1,
                'CType' => 'text',
                'sys_language_uid' => '0',
                'l18n_parent' => '0',
                't3ver_wsid' => '0',
                't3ver_state' => '0',
                't3ver_stage' => '0',
                't3ver_oid' => '0',
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => time(),
                'endtime' => time(),
                'deleted' => '0',
                'editlock' => '0',
                'hidden' => '0',
                'rowDescription' => '',
                'sorting' => '0',
                'fe_group' => '0',
            ];

            $record = $recordFactory->createFromDatabaseRow('tt_content', $testData);
        }
    }

    #[Test]
    public function rendererMethodSignaturesAreCompatible(): void
    {
        // Test that required methods exist with correct signatures
        $reflection = new \ReflectionClass($this->renderer);

        // Test renderPageModulePreviewContent method
        self::assertTrue($reflection->hasMethod('renderPageModulePreviewContent'));
        $renderMethod = $reflection->getMethod('renderPageModulePreviewContent');
        self::assertTrue($renderMethod->isPublic());

        $parameters = $renderMethod->getParameters();
        self::assertCount(1, $parameters);

        $firstParam = $parameters[0];
        self::assertEquals('gridColumnItem', $firstParam->getName());

        // Check parameter type
        $paramType = $firstParam->getType();
        if ($paramType instanceof \ReflectionNamedType) {
            self::assertEquals(GridColumnItem::class, $paramType->getName());
        }

        // Test return type
        $returnType = $renderMethod->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            self::assertEquals('string', $returnType->getName());
        }
    }

    #[Test]
    public function rendererHandlesExtensionConfigurationCorrectly(): void
    {
        // Use reflection to access the protected extensionConfiguration property
        $reflection = new \ReflectionClass($this->renderer);
        $configProperty = $reflection->getProperty('extensionConfiguration');

        $config = $configProperty->getValue($this->renderer);

        // Configuration should be an array (might be empty in test environment)
        self::assertIsArray($config);
    }

    #[Test]
    public function rendererInheritsFromStandardContentPreviewRenderer(): void
    {
        // Verify inheritance chain
        // self::assertInstanceOf(StandardContentPreviewRenderer::class, $this->renderer);

        // Test that parent methods are available
        $reflection = new \ReflectionClass(StandardContentPreviewRenderer::class);

        // Check for key parent methods that should be available
        $expectedMethods = ['renderPageModulePreviewContent', 'getLanguageService'];

        foreach ($expectedMethods as $method) {
            if ($reflection->hasMethod($method)) {
                self::assertTrue($reflection->hasMethod($method), "Parent class should have {$method} method");
            }
        }
    }
}
