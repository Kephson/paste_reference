<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *  (c) 2026 David Bruchmann <david.bruchmann@gmail.com>
 *  (c) 2021-2026 Ephraim Härer <mail@ephra.im>
 *  (c) 2013 Dirk Hoffmann <dirk-hoffmann@telekom.de>
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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\DriverException as DBALDriverException;
use EHAERER\PasteReference\Helper\BackendHelper;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository class
 *
 * @author David Bruchmann <david.bruchmann@gmail.com>
 * @author Dirk Hoffmann <dirk-hoffmann@telekom.de>
 */
class TtContentRepository implements SingletonInterface
{
    protected array $extensionConfiguration = [];
    protected bool $showHidden = true;
    protected BackendHelper $backendHelper;
    protected string $table = 'tt_content';

    public function __construct()
    {
        /** @var array<non-empty-string, string|int|float|bool|null> $emConf */
        $emConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference') ?? [];
        $this->extensionConfiguration = $emConf;
        $this->backendHelper = GeneralUtility::makeInstance(BackendHelper::class);
    }

    /**
     * Collects tt_content data from a single page or a page tree starting at a given page
     *
     * @todo: move this in a repository
     *
     * @param string $shortcutItem The single page to be used as the tree root
     * @param-out array $collectedItems The collected item data rows ordered by parent position, column position and sorting
     * @param int $recursive The number of levels for the recursion
     * @param int $parentUid uid of the referencing tt_content record
     * @param int $language sys_language_uid of the referencing tt_content record
     * @throws DBALException
     */
    public function collectContentDataFromPages(
        string $shortcutItem,
        array &$collectedItems,
        int $recursive = 0,
        int $parentUid = 0,
        int $language = 0
    ): void {
        $itemList = str_replace('pages_', '', $shortcutItem);
        $itemList = GeneralUtility::intExplode(',', $itemList);

        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->addSelectLiteral($queryBuilder->expr()->inSet(
                'pid',
                $queryBuilder->createNamedParameter($itemList, ArrayParameterType::INTEGER)
            ) . ' AS inSet')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($itemList, ArrayParameterType::INTEGER)
                ),
                $queryBuilder->expr()->gte('colPos', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([0, -1], ArrayParameterType::INTEGER)
                )
            )
            ->orderBy('inSet')
            ->addOrderBy('colPos')
            ->addOrderBy('sorting')
            ->executeQuery();

        /** @var array<string,mixed>|false $item */
        while ($item = $result->fetchAssociative()) {
            if (!empty($this->extensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', (int)($item['uid'] ?? 0), $language) ?: [];
                if (is_array($translatedItem) && $translatedItem !== []) {
                    $item = array_shift($translatedItem);
                }
            }
            if ($this->backendHelper->getBackendUser()->workspace > 0) {
                unset($item['inSet']);
                BackendUtility::workspaceOL('tt_content', $item, $this->backendHelper->getBackendUser()->workspace);
            }
            $item['tx_paste_reference_container'] = $item['pid'];
            /** @var array<int, array<non-empty-string, mixed>> $collectedItems */
            $collectedItems[] = $item;
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @todo: move this in a repository
     *
     * @param string $shortcutItem The tt_content element to fetch the data from
     * @param-out array $collectedItems The collected item data row
     * @param int $parentUid uid of the referencing tt_content record
     * @param int $language sys_language_uid of the referencing tt_content record
     * @throws DBALException
     */
    public function collectContentData(
        string $shortcutItem,
        array &$collectedItems,
        int $parentUid,
        int $language
    ): void {
        $shortcutItem = str_replace('tt_content_', '', $shortcutItem);
        if ((int)$shortcutItem !== $parentUid) {
            $queryBuilder = $this->getQueryBuilder();
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
            if ($this->showHidden) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }
            /** @var array<non-empty-string, string|int|float|bool|null>|false $item */
            $item = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int)$shortcutItem, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            if (!empty($this->extensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', (int)($item['uid'] ?? 0), $language) ?: [];
                if (is_array($translatedItem) && $translatedItem !== []) {
                    $item = array_shift($translatedItem);
                }
            }

            if ($this->backendHelper->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL(
                    'tt_content',
                    $item,
                    $this->backendHelper->getBackendUser()->workspace
                );
            }
            /** @var array<int, array<non-empty-string, mixed>> $collectedItems */
            $collectedItems[] = $item;
        }
    }

    /**
     * get pid for a ContentElement
     *
     * @param int $uid the uid value of a tt_content record
     *
     * @return int
     * @throws DBALException|DBALDriverException
     */
    public function getPidFromUid(int $uid = 0): int
    {
        $queryBuilder = $this->getQueryBuilder();
        /** @var array<non-empty-string, string|int|float|bool|null> $contentElement */
        $contentElement = $queryBuilder
            ->select('pid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(abs($uid), Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return $contentElement['pid'] ? (int)$contentElement['pid'] : 0;
    }

    /**
     * Function to remove any remains of versioned records after finalizing a workspace action
     * via 'Discard' or 'Publish' commands
     */
    public function cleanupWorkspacesAfterFinalizing(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(-1, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public function getQueryBuilder(string $table = 'tt_content'): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        return $queryBuilder;
    }

    public function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table);
    }
}
