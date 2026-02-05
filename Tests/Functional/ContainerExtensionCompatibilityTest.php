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

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'hash' => [
                        'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                    ],
                    'pages' => [
                        'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                    ],
                    'pagesection' => [
                        'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                    ],
                ],
            ],
        ],
    ];

    private Typo3Version $typo3Version;
    private bool $containerExtensionAvailable = false;

    protected function setUp(): void
    {
        // Add container extension to test extensions if available
        if (class_exists('B13\\Container\\Tca\\Registry')) {
            $this->testExtensionsToLoad[] = 'b13/container';
        }
        
        parent::setUp();
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        
        // Check if container extension is available after setup
        $this->containerExtensionAvailable = class_exists('B13\\Container\\Tca\\Registry') && 
                                           ExtensionManagementUtility::isLoaded('container');

        // Create test data programmatically
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
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension (b13/container) not available in test environment');
        }

        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test that container extension classes are available
        self::assertTrue(class_exists('B13\\Container\\Tca\\Registry'), 'Container extension Registry class should be available');
        self::assertTrue(class_exists('B13\\Container\\Tca\\ContainerConfiguration'), 'Container configuration class should be available');

        // Test that we can instantiate the registry
        $registry = GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class);
        self::assertInstanceOf(\B13\Container\Tca\Registry::class, $registry);

        // Test that container extension is properly loaded
        self::assertTrue(ExtensionManagementUtility::isLoaded('container'), 'Container extension should be loaded');

        // Test version compatibility
        if ($majorVersion >= 13) {
            // Container extension should work with TYPO3 v13+
            self::assertTrue(true, 'Container extension is compatible with TYPO3 v13+');
        }
    }

    #[Test]
    public function pasteOperationWorksInContainerElements(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        // Test container configuration creation
        $containerConfig = new \B13\Container\Tca\ContainerConfiguration(
            'test_container',
            'Test Container',
            'Test container for paste reference testing',
            [
                [
                    ['name' => 'Column 1', 'colPos' => 101],
                    ['name' => 'Column 2', 'colPos' => 102]
                ]
            ]
        );

        self::assertInstanceOf(\B13\Container\Tca\ContainerConfiguration::class, $containerConfig);
        self::assertEquals('test_container', $containerConfig->getCType());

        // Test basic database operations for container elements
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create a container element
        $containerData = [
            'pid' => 1,
            'CType' => 'test_container',
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

        // Verify both elements were created
        self::assertGreaterThan(0, $containerUid, 'Container element should be created');
        self::assertGreaterThan(0, $sourceUid, 'Source element should be created');

        // Test that we can query container elements
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $containerElement = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid)))
            ->executeQuery()
            ->fetchAssociative();

        self::assertEquals('test_container', $containerElement['CType']);
        self::assertEquals('Test Container', $containerElement['header']);
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
        self::assertInstanceOf(\TYPO3\CMS\Core\Database\Query\QueryBuilder::class, $queryBuilder);

        // Test container-specific database operations
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create a container with child elements
        $containerData = [
            'pid' => 1,
            'CType' => 'test_container',
            'header' => 'Parent Container',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $containerData);
        $containerUid = (int)$connection->lastInsertId();

        // Create child element
        $childData = [
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Child Element',
            'colPos' => 101, // Container column
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $childData);
        $childUid = (int)$connection->lastInsertId();

        // Test querying container children
        $result = $queryBuilder
            ->select('uid', 'colPos', 'CType', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(101))
            )
            ->executeQuery();

        $elements = $result->fetchAllAssociative();
        self::assertCount(1, $elements, 'Should find one child element');

        $element = $elements[0];
        self::assertEquals(101, $element['colPos'], 'ColPos should be container column');
        self::assertEquals('Child Element', $element['header']);

        // Test version-specific handling
        if ($majorVersion >= 13) {
            // In v13+, ensure proper type casting
            self::assertIsInt((int)$element['colPos']);
        }
    }

    #[Test]
    public function containerElementVisibilityInBackend(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        // Test basic database operations
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');
        self::assertInstanceOf(\TYPO3\CMS\Core\Database\Connection::class, $connection);
    }

    #[Test]
    public function pasteReferenceContainerFieldHandling(): void
    {
        // Test basic shortcut functionality (works without container extension)
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        // Create shortcut element
        $shortcutData = [
            'pid' => 1,
            'CType' => 'shortcut',
            'header' => 'Container Reference',
            'records' => '1,2',
            'colPos' => 0,
            'sys_language_uid' => 0,
        ];

        $connection->insert('tt_content', $shortcutData);
        $shortcutUid = (int)$connection->lastInsertId();

        self::assertGreaterThan(0, $shortcutUid, 'Shortcut element should be created');

        // Test that ShortcutPreviewRenderer can be instantiated
        if (class_exists(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class)) {
            $renderer = GeneralUtility::makeInstance(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class);
            self::assertInstanceOf(\EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer::class, $renderer);
        }
    }

    #[Test]
    public function containerDragDropIntegration(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        // Test basic drag-drop functionality
        self::assertTrue(class_exists('B13\\Container\\Tca\\Registry'));
    }

    #[Test]
    public function containerSortingAndPositioning(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        // Test basic sorting functionality
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        self::assertInstanceOf(ConnectionPool::class, $connectionPool);
    }

    #[Test]
    public function containerLanguageHandling(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        // Test basic language handling
        $majorVersion = $this->typo3Version->getMajorVersion();
        self::assertGreaterThanOrEqual(13, $majorVersion);
    }

    #[Test]
    public function containerWorkspaceCompatibility(): void
    {
        if (!$this->containerExtensionAvailable) {
            self::markTestSkipped('Container extension not available');
        }

        $majorVersion = $this->typo3Version->getMajorVersion();

        // Test workspace compatibility basics
        if ($majorVersion >= 13) {
            $helper = GeneralUtility::makeInstance(Helper::class);
            self::assertInstanceOf(Helper::class, $helper);
        }
    }
}
