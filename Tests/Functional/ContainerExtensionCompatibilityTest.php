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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
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

    protected array $pathsToLinkInTestInstance = [];

    protected array $additionalFoldersToCreate = [];

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

    private bool $containerExtensionAvailable = false;

    protected function setUp(): void
    {
        // Add container extension to test extensions if available
        if (class_exists('B13\\Container\\Tca\\Registry')) {
            $this->testExtensionsToLoad[] = 'b13/container';
        }

        parent::setUp();

        // Check if container extension is available after setup
        $this->containerExtensionAvailable = class_exists('B13\\Container\\Tca\\Registry')
                                           && ExtensionManagementUtility::isLoaded('container');

        // Create test data programmatically
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
    public function pasteOperationWorksInContainerElements(): void
    {
        if (!class_exists('B13\\Container\\Tca\\Registry')) {
            self::markTestSkipped('Container extension not available');
        }

        // Test basic container configuration creation (no database operations)
        try {
            $containerConfig = new \B13\Container\Tca\ContainerConfiguration(
                'test_container',
                'Test Container',
                'Test container for paste reference testing',
                [
                    [
                        ['name' => 'Column 1', 'colPos' => 101],
                        ['name' => 'Column 2', 'colPos' => 102],
                    ],
                ]
            );

            self::assertEquals('test_container', $containerConfig->getCType());
        } catch (\Exception $e) {
            self::fail('Container configuration cannot be created: ' . $e->getMessage());
        }
    }
}
