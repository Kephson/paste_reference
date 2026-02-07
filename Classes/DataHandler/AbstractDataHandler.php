<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2021-2023 Ephraim Härer <mail@ephra.im>
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
use EHAERER\PasteReference\Domain\Repository\TtContentRepository;
use TYPO3\CMS\Core\Database\Connection;
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
    protected TtContentRepository $ttContentRepository;

    /**
     * initializes this class
     *
     * @param string $table The name of the table the data should be saved to
     * @param int $uidPid The uid of the record or page we are currently working on
     * @param DataHandler $dataHandler
     * @throws DBALException|DBALDriverException
     */
    public function init(
        string $table,
        int $uidPid,
        DataHandler $dataHandler
    ): void
    {
        $this->ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);
        $this->setTable($table);
        if ($table === 'tt_content' && $uidPid < 0) {
            $this->setContentUid(abs($uidPid));
            $pageUid = $this->ttContentRepository->getPidFromUid($this->getContentUid());
            $this->setPageUid($pageUid);
        } else {
            $this->setPageUid($uidPid);
        }
        $this->setDataHandler($dataHandler);
    }

    /**
     * @return string
     * usually 'tt_content'
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     * usually 'tt_content'
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getPageUid(): int
    {
        return $this->pageUid;
    }

    public function setPageUid(int $pageUid): void
    {
        $this->pageUid = $pageUid;
    }

    public function getContentUid(): int
    {
        return $this->contentUid;
    }

    public function setContentUid(int $contentUid): void
    {
        $this->contentUid = $contentUid;
    }

    public function getDataHandler(): DataHandler
    {
        return $this->dataHandler;
    }

    public function setDataHandler(DataHandler $dataHandler): void
    {
        $this->dataHandler = $dataHandler;
    }
}
