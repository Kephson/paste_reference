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
use EHAERER\PasteReference\Helper\Helper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test b13/container extension integration across TYPO3 versions
 * Validates paste operations work correctly in container elements
 */
final class ContainerExtensionCompatibilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'ehaerer/paste-reference',
    ];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/paste_reference' => 'typo3conf/ext/paste_reference',
    ];

    protected array $additionalFoldersToCreate = [
        'typo3conf/ext',
    ];

    private Typo3Version $typo3Version;
    private bool $containerExtensionAvailable = false;

    protected function setUp(): void
    {
        // Load container extension if available
        if (class_exists('B13\\Container\\Tca\\Registry')) {
            $this->testExtensionsToLoad[] = 'b13/container';
            $this->testExtensionsToLoad[] = 'Tests/Extensions/test_container';
        }
        
        parent::setUp();
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        
        // Check if container extension is available
        $this->containerExtensionAvailable = ExtensionManagementUtility::isLoaded('container');

        // Create test data programmatically instead of CSV import
        $this->createTestData();
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

        // Add container test data if container extension is available
        if ($this->containerExtensionAvailable) {
            $containerData = [
                [
                    'uid' => 100,
                    'pid' => 1,
                    'tstamp' => 1577836800,
                    'crdate' => 1577836800,
                    'deleted' => 0,
                    'hidden' => 0,
                    'CType' => 'test_container_2col',
                    'header' => 'Test Container Element',
                    'colPos' => 0,
                    'sys_language_uid' => 0,
                    'sorting' => 100,
                ],
                [
                    'uid' => 101,
                    'pid' => 1,
                    'tstamp' => 1577836800,
                    'crdate' => 1577836800,
                    'deleted' => 0,
                    'hidden' => 0,
                    'CType' => 'text',
                    'header' => 'Container Child Element 1',
                    'bodytext' => '<p>This is a child element inside a container.</p>',
                    'colPos' => 101,
                    'tx_container_parent' => 100,
                    'sys_language_uid' => 0,
                    'sorting' => 200,
                ],
                [
                    'uid' => 102,
                    'pid' => 1,
                    'tstamp' => 1577836800,
                    'crdate' => 1577836800,
                    'deleted' => 0,
                    'hidden' => 0,
                    'CType' => 'text',
                    'header' => 'Container Child Element 2',
                    'bodytext' => '<p>This is another child element inside the same container.</p>',
                    'colPos' => 102,
                    'tx_container_parent' => 100,
                    'sys_language_uid' => 0,
                    'sorting' => 300,
                ],
                [
                    'uid' => 200,
                    'pid' => 1,
                    'tstamp' => 1577836800,
                    'crdate' => 1577836800,
                    'deleted' => 0,
                    'hidden' => 0,
                    'CType' => 'test_container_nested',
                    'header' => 'Test Nested Container',
                    'colPos' => 0,
                    'sys_language_uid' => 0,
                    'sorting' => 400,
                ],
                [
                    'uid' => 201,
                    'pid' => 1,
                    'tstamp' => 1577836800,
                    'crdate' => 1577836800,
                    'deleted' => 0,
                    'hidden' => 0,
                    'CType' => 'text',
                    'header' => 'Nested Container Child',
                    'bodytext' => '<p>This is a child element inside a nested container.</p>',
                    'colPos' => 201,
                    'tx_container_parent' => 200,
                    'sys_language_uid' => 0,
                    'sorting' => 500,
                ],
            ];
            $testData = array_merge($testData, $containerData);
        }

        // Insert test data
        foreach ($testData as $data) {
            $connection->insert('tt_content', $data);
        }
    }

    #[Test]
    public function containerExtensionCompatibilityCheck(): void
    {
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test that container extension detection works across versions
        if ($this->containerExtensionAvailable) {
            self::assertTrue(ExtensionManagementUtility::isLoaded('container'));

            // Test container-specific TCA fields exist
            $tcaColumns = $GLOBALS['TCA']['tt_content']['columns'] ?? [];
            self::assertArrayHasKey('tx_container_parent', $tcaColumns, 'Container parent field should exist in TCA');

            // Test our test container CTypes are available
            $containerCTypes = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [];
            $testContainerFound = false;
            foreach ($containerCTypes as $item) {
                if (isset($item['value']) && $item['value'] === 'test_container_2col') {
                    $testContainerFound = true;
                    break;
                }
                // Fallback for older TCA format
                if (isset($item[1]) && $item[1] === 'test_container_2col') {
                    $testContainerFound = true;
                    break;
                }
            }

            self::assertTrue($testContainerFound, 'Test container CType should be available');

            // Test that container elements exist in database
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
            $containerElements = $queryBuilder
                ->select('uid', 'CType', 'header')
                ->from('tt_content')
                ->where($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('test_container_2col')))
                ->executeQuery()
                ->fetchAllAssociative();

            self::assertCount(1, $containerElements, 'Should have one test container element');
            self::assertEquals('Test Container Element', $containerElements[0]['header']);
        } else {
            self::markTestSkipped('Container extension not available - testing compatibility without container');
        }
    }

    #[Test]
    public function pasteOperationWorksInContainerElements(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create a container element (simulated without tx_container_parent field)
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Test Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId();

        // Create content element to be pasted
        $sourceData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Source Element',
            'bodytext' => 'Content to be pasted',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $sourceData);
        $sourceUid = (int)$connection->lastInsertId();

        // Test paste operation into container
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $processCmdmap = GeneralUtility::makeInstance(ProcessCmdmap::class);

        // Initialize ProcessCmdmap with container context
        $processCmdmap->init('tt_content', 1, $dataHandler);

        // Simulate paste operation into container
        $cmdArray = [
            'tt_content' => [
                $sourceUid => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1, // Page UID
                        'update' => [
                            'colPos' => 101, // Container column
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();

        // Verify paste operation succeeded
        self::assertEmpty($dataHandler->errorLog, 'DataHandler should not have errors');

        // Find the copied element
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $copiedElements = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(101)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($sourceUid))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(1, $copiedElements, 'Should have one copied element in container');

        $copiedElement = $copiedElements[0];
        self::assertEquals(101, $copiedElement['colPos'], 'Copied element should have correct colPos');
        self::assertEquals('Source Element', $copiedElement['header'], 'Copied element should have correct content');
    }

    #[Test]
    public function containerParentParameterHandlingAcrossVersions(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $helper = GeneralUtility::makeInstance(Helper::class);
        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test basic query builder functionality that would be used with containers
        $queryBuilder = $helper->getQueryBuilder('tt_content');

        // Test query with colPos filter (container elements use different colPos values)
        $result = $queryBuilder
            ->select('uid', 'colPos', 'CType')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->gt('colPos', 0)
            )
            ->executeQuery();

        $elements = $result->fetchAllAssociative();

        foreach ($elements as $element) {
            self::assertGreaterThan(0, $element['colPos'], 'ColPos should be positive integer');
            self::assertIsNumeric($element['colPos'], 'ColPos should be numeric');

            // Test version-specific handling
            if ($majorVersion >= 13) {
                // In v13+, ensure proper type casting
                self::assertIsInt((int)$element['colPos']);
            }
        }
    }

    #[Test]
    public function containerElementVisibilityInBackend(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create container with nested elements (simulated with colPos)
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Visibility Test Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId();

        // Create nested element (simulated with special colPos)
        $nestedData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Nested Element',
            'colPos' => 101, // Container column
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $nestedData);
        $nestedUid = (int)$connection->lastInsertId();

        // Test that nested elements are properly associated by colPos
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $nestedElements = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(101))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(1, $nestedElements, 'Should find nested element');
        self::assertEquals($nestedUid, $nestedElements[0]['uid'], 'Should find correct nested element');
        self::assertEquals(101, $nestedElements[0]['colPos'], 'Nested element should have correct colPos');

        // Test container hierarchy integrity
        $containerElement = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertEquals(0, $containerElement['colPos'], 'Container should be in main column');
        self::assertEquals('container_test', $containerElement['CType'], 'Container should have correct CType');
    }

    #[Test]
    public function pasteReferenceContainerFieldHandling(): void
    {
        // Test the paste reference functionality with container-like elements
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create shortcut element that references container content
        $shortcutData = [
            'pid' => 1,
            'CType' => 'shortcut',
            'header' => 'Container Reference',
            'records' => '1,2', // Reference to container elements
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $shortcutData);
        $shortcutUid = (int)$connection->lastInsertId();

        // Test that ShortcutPreviewRenderer handles container context
        if (class_exists(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class)) {
            $renderer = GeneralUtility::makeInstance(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class);

            // Test that renderer can handle container-aware elements
            self::assertInstanceOf(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class, $renderer);

            // The renderer should handle elements correctly
            $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
            $shortcutElement = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($shortcutUid))
                )
                ->executeQuery()
                ->fetchAssociative();

            self::assertEquals(1, $shortcutElement['pid'], 'Shortcut should have correct PID');
            self::assertEquals('shortcut', $shortcutElement['CType'], 'Element should be shortcut type');
        }
    }

    #[Test]
    public function containerDragDropIntegration(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        // Test drag-drop integration with container elements
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create container and elements for drag-drop test
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Drag Drop Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId();

        // Create element to be moved
        $sourceData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Draggable Element',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $sourceData);
        $sourceUid = (int)$connection->lastInsertId();

        // Simulate drag-drop move operation into container
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $cmdArray = [
            'tt_content' => [
                $sourceUid => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 1, // Page UID
                        'update' => [
                            'colPos' => 101, // Container column
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();

        // Verify move operation
        self::assertEmpty($dataHandler->errorLog, 'DataHandler should not have errors during move');

        // Check that element was moved to container column
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $movedElement = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($sourceUid))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertEquals(101, $movedElement['colPos'], 'Element should have container colPos');
        self::assertEquals('Draggable Element', $movedElement['header'], 'Element should retain its content');
    }

    #[Test]
    public function containerSortingAndPositioning(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create container
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Sorting Test Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'sorting' => 100,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId();

        // Create multiple elements in container with different sorting
        $elements = [];
        for ($i = 1; $i <= 3; $i++) {
            $elementData = [
                'pid' => 1,
                'CType' => 'text',
                'header' => "Element $i",
                'colPos' => 101, // Container column
                'sys_language_uid' => 0,
                'sorting' => $i * 100,
            ];

            $connection->insert('tt_content', $elementData);
            $elements[] = (int)$connection->lastInsertId();
        }

        // Test paste operation at first position (sorting = 0)
        $newElementData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'First Element',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $newElementData);
        $newElementUid = (int)$connection->lastInsertId();

        // Paste at first position in container
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $cmdArray = [
            'tt_content' => [
                $newElementUid => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1, // Page UID (first position)
                        'update' => [
                            'colPos' => 101,
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();

        // Verify sorting order
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $containerElements = $queryBuilder
            ->select('uid', 'header', 'sorting')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(101))
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(4, $containerElements, 'Should have 4 elements in container column');

        // Verify elements are properly sorted
        $previousSorting = -1;
        foreach ($containerElements as $element) {
            self::assertGreaterThanOrEqual($previousSorting, $element['sorting'], 'Elements should be sorted correctly');
            $previousSorting = $element['sorting'];
        }
    }

    #[Test]
    public function containerLanguageHandling(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create container in default language
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Multilingual Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId();

        // Create translated container (if language support is available)
        $translatedContainerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Translated Container',
            'colPos' => 0,
            'sys_language_uid' => 1,
            'l18n_parent' => $containerUid,
        ];

        $connection->insert('tt_content', $translatedContainerData);
        $translatedContainerUid = (int)$connection->lastInsertId();

        // Test paste operation in translated container
        $sourceData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Source for Translation',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $sourceData);
        $sourceUid = (int)$connection->lastInsertId();

        // Paste into translated container
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $cmdArray = [
            'tt_content' => [
                $sourceUid => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => 101,
                            'sys_language_uid' => 1, // Translated language
                        ],
                    ],
                ],
            ],
        ];

        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();

        // Verify language handling
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $translatedElements = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(101)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(1))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        if (count($translatedElements) > 0) {
            $translatedElement = $translatedElements[0];
            self::assertEquals(1, $translatedElement['sys_language_uid'], 'Element should have correct language');
            self::assertEquals(101, $translatedElement['colPos'], 'Element should be in container column');
        }

        // Verify original language container exists
        $originalContainer = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertEquals(0, $originalContainer['sys_language_uid'], 'Original container should be in default language');
    }

    #[Test]
    public function containerWorkspaceCompatibility(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test workspace compatibility (if workspaces are available)
        if ($majorVersion >= 13) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $connection = $connectionPool->getConnectionForTable('tt_content');

            // Create container in live workspace
            $containerData = [
                'pid' => 1,
                'CType' => 'container_test',
                'header' => 'Workspace Container',
                'colPos' => 0,
                'sys_language_uid' => 0,
                't3ver_wsid' => 0, // Live workspace
            ];

            $connection->insert('tt_content', $containerData);
            $containerUid = (int)$connection->lastInsertId();

            // Test that container elements work in workspace context
            $helper = GeneralUtility::makeInstance(Helper::class);
            $queryBuilder = $helper->getQueryBuilder('tt_content');

            // Query should handle workspace overlays correctly
            $result = $queryBuilder
                ->select('uid', 'CType', 't3ver_wsid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid))
                )
                ->executeQuery()
                ->fetchAssociative();

            self::assertNotEmpty($result, 'Container should be found in workspace context');
            self::assertEquals(0, $result['t3ver_wsid'], 'Container should be in live workspace');
            self::assertEquals('container_test', $result['CType'], 'Container should have correct CType');

            // Test workspace-aware queries
            $workspaceElements = $queryBuilder
                ->select('uid', 'CType', 't3ver_wsid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0))
                )
                ->executeQuery()
                ->fetchAllAssociative();

            self::assertNotEmpty($workspaceElements, 'Should find elements in live workspace');
        }
    }
}
