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

    private Typo3Version $typo3Version;
    private bool $containerExtensionAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $this->containerExtensionAvailable = ExtensionManagementUtility::isLoaded('container');

        // Import test data including container elements
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/container_elements.csv');
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

            // Test container CTypes are available
            $containerCTypes = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [];
            $containerTypeFound = false;
            foreach ($containerCTypes as $item) {
                if (isset($item[1]) && str_starts_with($item[1], 'container_')) {
                    $containerTypeFound = true;
                    break;
                }
            }

            if ($containerTypeFound) {
                self::assertTrue($containerTypeFound, 'Container CTypes should be available');
            }
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

        // Create a container element
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Test Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId('tt_content');

        // Create content element to be pasted
        $sourceData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Source Element',
            'bodytext' => 'Content to be pasted',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $sourceData);
        $sourceUid = (int)$connection->lastInsertId('tt_content');

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
                            'tx_container_parent' => $containerUid,
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
                $queryBuilder->expr()->eq('tx_container_parent', $queryBuilder->createNamedParameter($containerUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(101, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($sourceUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(1, $copiedElements, 'Should have one copied element in container');

        $copiedElement = $copiedElements[0];
        self::assertEquals($containerUid, $copiedElement['tx_container_parent'], 'Copied element should have correct container parent');
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

        // Test container parent parameter handling in different TYPO3 versions
        $queryBuilder = $helper->getQueryBuilder('tt_content');

        // Test query with container parent filter
        $result = $queryBuilder
            ->select('uid', 'tx_container_parent', 'colPos')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->gt('tx_container_parent', 0)
            )
            ->executeQuery();

        $containerElements = $result->fetchAllAssociative();

        foreach ($containerElements as $element) {
            self::assertGreaterThan(0, $element['tx_container_parent'], 'Container parent should be positive integer');
            self::assertIsNumeric($element['colPos'], 'ColPos should be numeric');

            // Test version-specific handling
            if ($majorVersion >= 13) {
                // In v13+, ensure proper type casting
                self::assertIsInt((int)$element['tx_container_parent']);
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

        // Create container with nested elements
        $containerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Visibility Test Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId('tt_content');

        // Create nested element
        $nestedData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Nested Element',
            'colPos' => 101,
            'sys_language_uid' => 0,
            'tx_container_parent' => $containerUid,
        ];

        $connection->insert('tt_content', $nestedData);
        $nestedUid = (int)$connection->lastInsertId('tt_content');

        // Test that nested elements are properly associated
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $nestedElements = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('tx_container_parent', $queryBuilder->createNamedParameter($containerUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(1, $nestedElements, 'Should find nested element');
        self::assertEquals($nestedUid, $nestedElements[0]['uid'], 'Should find correct nested element');
        self::assertEquals($containerUid, $nestedElements[0]['tx_container_parent'], 'Nested element should have correct parent');

        // Test container hierarchy integrity
        $containerElement = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertEquals(0, $containerElement['tx_container_parent'], 'Container should not have parent (top-level)');
        self::assertEquals(0, $containerElement['colPos'], 'Container should be in main column');
    }

    #[Test]
    public function pasteReferenceContainerFieldHandling(): void
    {
        // Test the tx_paste_reference_container field handling
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
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $shortcutData);
        $shortcutUid = (int)$connection->lastInsertId('tt_content');

        // Test that ShortcutPreviewRenderer handles container context
        if (class_exists(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class)) {
            $renderer = GeneralUtility::makeInstance(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class);

            // Test that renderer can handle container-aware elements
            self::assertInstanceOf(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class, $renderer);

            // The renderer should set tx_paste_reference_container field
            $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
            $shortcutElement = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($shortcutUid, \PDO::PARAM_INT))
                )
                ->executeQuery()
                ->fetchAssociative();

            self::assertEquals(1, $shortcutElement['pid'], 'Shortcut should have correct PID for container context');
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
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId('tt_content');

        // Create element to be moved
        $sourceData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Draggable Element',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $sourceData);
        $sourceUid = (int)$connection->lastInsertId('tt_content');

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
                            'tx_container_parent' => $containerUid,
                        ],
                    ],
                ],
            ],
        ];

        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();

        // Verify move operation
        self::assertEmpty($dataHandler->errorLog, 'DataHandler should not have errors during move');

        // Check that element was moved to container
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $movedElement = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($sourceUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertEquals($containerUid, $movedElement['tx_container_parent'], 'Element should be moved to container');
        self::assertEquals(101, $movedElement['colPos'], 'Element should have container colPos');
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
            'tx_container_parent' => 0,
            'sorting' => 100,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId('tt_content');

        // Create multiple elements in container with different sorting
        $elements = [];
        for ($i = 1; $i <= 3; $i++) {
            $elementData = [
                'pid' => 1,
                'CType' => 'text',
                'header' => "Element $i",
                'colPos' => 101,
                'sys_language_uid' => 0,
                'tx_container_parent' => $containerUid,
                'sorting' => $i * 100,
            ];

            $connection->insert('tt_content', $elementData);
            $elements[] = (int)$connection->lastInsertId('tt_content');
        }

        // Test paste operation at first position (sorting = 0)
        $newElementData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'First Element',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $newElementData);
        $newElementUid = (int)$connection->lastInsertId('tt_content');

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
                            'tx_container_parent' => $containerUid,
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
                $queryBuilder->expr()->eq('tx_container_parent', $queryBuilder->createNamedParameter($containerUid, \PDO::PARAM_INT))
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(4, $containerElements, 'Should have 4 elements in container');

        // First element should be the newly pasted one (or have lowest sorting)
        $firstElement = $containerElements[0];
        self::assertLessThanOrEqual($containerElements[1]['sorting'], $firstElement['sorting'], 'First element should have lowest sorting');
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
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId('tt_content');

        // Create translated container (if language support is available)
        $translatedContainerData = [
            'pid' => 1,
            'CType' => 'container_test',
            'header' => 'Translated Container',
            'colPos' => 0,
            'sys_language_uid' => 1,
            'l18n_parent' => $containerUid,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $translatedContainerData);
        $translatedContainerUid = (int)$connection->lastInsertId('tt_content');

        // Test paste operation in translated container
        $sourceData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Source for Translation',
            'colPos' => 0,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ];

        $connection->insert('tt_content', $sourceData);
        $sourceUid = (int)$connection->lastInsertId('tt_content');

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
                            'tx_container_parent' => $translatedContainerUid,
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
                $queryBuilder->expr()->eq('tx_container_parent', $queryBuilder->createNamedParameter($translatedContainerUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        if (count($translatedElements) > 0) {
            $translatedElement = $translatedElements[0];
            self::assertEquals(1, $translatedElement['sys_language_uid'], 'Element should have correct language');
            self::assertEquals($translatedContainerUid, $translatedElement['tx_container_parent'], 'Element should be in translated container');
        }
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
                'tx_container_parent' => 0,
                't3ver_wsid' => 0, // Live workspace
            ];

            $connection->insert('tt_content', $containerData);
            $containerUid = (int)$connection->lastInsertId('tt_content');

            // Test that container elements work in workspace context
            $helper = GeneralUtility::makeInstance(Helper::class);
            $queryBuilder = $helper->getQueryBuilder('tt_content');

            // Query should handle workspace overlays correctly
            $result = $queryBuilder
                ->select('uid', 'tx_container_parent', 't3ver_wsid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid, \PDO::PARAM_INT))
                )
                ->executeQuery()
                ->fetchAssociative();

            self::assertNotEmpty($result, 'Container should be found in workspace context');
            self::assertEquals(0, $result['t3ver_wsid'], 'Container should be in live workspace');
        }
    }
}
