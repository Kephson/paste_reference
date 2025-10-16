<?php

namespace EHAERER\PasteReference\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2023-2025 Ephraim Härer <mail@ephra.im>
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
use EHAERER\PasteReference\DataHandler\ProcessCmdmap;
use TYPO3\CMS\Core\DataHandling\DataHandler as CoreDataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
class DataHandler implements SingletonInterface
{
    /**
     * Function to process the drag & drop copy action
     *
     * @param string $command The command to be handled by the command map
     * @param string $table The name of the table we are working on
     * @param int $id The id of the record that is going to be copied
     * @param array<non-empty-string, mixed>|string $value The value that has been sent with the copy command
     * @param bool $commandIsProcessed A switch to tell the parent object, if the record has been copied
     * @param CoreDataHandler $parentObj The parent object that triggered this hook
     * @param bool|array<non-empty-string, mixed> $pasteUpdate Values to be updated after the record is pasted
     * @throws DBALException|DBALDriverException
     */
    public function processCmdmap(
        string $command,
        string $table,
        int $id,
        array|string $value,
        bool &$commandIsProcessed,
        CoreDataHandler &$parentObj,
        bool|array $pasteUpdate
    ): void {
        if (!$parentObj->isImporting) {
            $hook = GeneralUtility::makeInstance(ProcessCmdmap::class);
            $hook->execute_processCmdmap($command, $table, $id, $value, $commandIsProcessed, $parentObj, $pasteUpdate);
        }
    }
}
