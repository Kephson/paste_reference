<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2021-2025 Ephraim Härer <mail@ephra.im>
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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

use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Exception as DBALException;
use EHAERER\PasteReference\Helper\Helper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
abstract class AbstractDataHandler
{
    protected ?Connection $connection = null;
    protected string $table = '';
    protected int $pageUid = 0;
    protected int $contentUid = 0;
    protected ?DataHandler $dataHandler = null;

    /**
     * initializes this class
     *
     * @param string $table The name of the table the data should be saved to
     * @param int $uidPid The uid of the record or page we are currently working on
     * @param DataHandler $dataHandler
     * @throws DBALException|DBALDriverException
     */
    public function init(string $table, int $uidPid, DataHandler $dataHandler): void
    {
        $this->setTable($table);
        if ($table === 'tt_content' && $uidPid < 0) {
            $this->setContentUid(abs($uidPid));
            $helper = GeneralUtility::makeInstance(Helper::class);
            $pageUid = $helper->getPidFromUid($this->getContentUid());
            $this->setPageUid($pageUid);
        } else {
            $this->setPageUid($uidPid);
        }
        $this->setTceMain($dataHandler);
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

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * @return int
     */
    public function getPageUid(): int
    {
        return $this->pageUid;
    }

    /**
     * @param int $pageUid
     */
    public function setPageUid(int $pageUid): void
    {
        $this->pageUid = $pageUid;
    }

    /**
     * @return int
     */
    public function getContentUid(): int
    {
        return $this->contentUid;
    }

    /**
     * @param int $contentUid
     */
    public function setContentUid(int $contentUid): void
    {
        $this->contentUid = $contentUid;
    }

    /**
     * @return DataHandler
     */
    public function getTceMain(): DataHandler
    {
        return $this->dataHandler;
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function setTceMain(DataHandler $dataHandler): void
    {
        $this->dataHandler = $dataHandler;
    }
}
