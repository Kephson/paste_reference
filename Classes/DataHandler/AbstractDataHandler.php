<?php

namespace EHAERER\PasteReference\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2021 Ephraim Härer <mail@ephra.im>
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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use EHAERER\PasteReference\Helper\Helper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
abstract class AbstractDataHandler
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $pageUid;

    /**
     * @var int
     */
    protected $contentUid = 0;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * initializes this class
     *
     * @param string $table The name of the table the data should be saved to
     * @param int $uidPid The uid of the record or page we are currently working on
     * @param DataHandler $dataHandler
     * @throws DBALException
     * @throws Exception
     */
    public function init($table, $uidPid, DataHandler $dataHandler)
    {
        $this->setTable($table);
        if ($table === 'tt_content' && (int)$uidPid < 0) {
            $this->setContentUid(abs($uidPid));
            $pageUid = Helper::getInstance()->getPidFromUid($this->getContentUid());
            $this->setPageUid($pageUid);
        } else {
            $this->setPageUid((int)$uidPid);
        }
        $this->setTceMain($dataHandler);
    }

    /**
     * getter for contentUid
     *
     * @return int contentUid
     */
    public function getContentUid()
    {
        return $this->contentUid;
    }

    /**
     * setter for contentUid
     *
     * @param int $contentUid
     */
    public function setContentUid($contentUid)
    {
        $this->contentUid = $contentUid;
    }

    /**
     * setter for dataHandler object
     *
     * @param DataHandler $dataHandler
     */
    public function setTceMain(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
    }

    /**
     * getter for pageUid
     *
     * @return int pageUid
     */
    public function getPageUid()
    {
        return $this->pageUid;
    }

    /**
     * setter for pageUid
     *
     * @param int $pageUid
     */
    public function setPageUid($pageUid)
    {
        $this->pageUid = $pageUid;
    }

    /**
     * Function to remove any remains of versioned records after finalizing a workspace action
     * via 'Discard' or 'Publish' commands
     */
    public function cleanupWorkspacesAfterFinalizing()
    {
        $queryBuilder = $this->getQueryBuilder();

        if (Helper::getInstance()->isTypo3OlderThen10()) {
            $constraints = [
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        't3ver_id',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                ),
            ];
        } else {
            $constraints = [
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                ),
            ];
        }

        $queryBuilder->delete('tt_content')
            ->where(...$constraints)
            ->execute();
    }

    /**
     * getter for queryBuilder
     *
     * @param string $table
     * @return QueryBuilder $queryBuilder
     */
    public function getQueryBuilder($table = 'tt_content')
    {
        /**@var $queryBuilder QueryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        return $queryBuilder;
    }

    /**
     * setter for Connection object
     *
     * @return Connection
     */
    public function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
    }

    /**
     * getter for dataHandler
     *
     * @return DataHandler dataHandler
     */
    public function getTceMain()
    {
        return $this->dataHandler;
    }

    /**
     * getter for table
     *
     * @return string table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * setter for table
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }
}
