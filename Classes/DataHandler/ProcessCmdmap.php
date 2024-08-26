<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2023 Ephraim Härer <mail@ephra.im>
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class ProcessCmdmap extends AbstractDataHandler
{
    /**
     * Function to process the drag & drop copy action
     *
     * @param string $command The command to be handled by the command map
     * @param string $table The name of the table we are working on
     * @param int $id The id of the record that is going to be copied
     * @param array|string $value The value that has been sent with the copy command
     * @param bool $commandIsProcessed A switch to tell the parent object, if the record has been copied
     * @param DataHandler|null $parentObj The parent object that triggered this hook
     * @param bool|array $pasteUpdate Values to be updated after the record is pasted
     * @throws DBALException|DBALDriverException
     */
    public function execute_processCmdmap(
        string       $command,
        string       $table,
        int          $id,
        array|string $value,
        bool         &$commandIsProcessed,
        DataHandler  $parentObj = null,
        bool|array   $pasteUpdate = false
    ): void
    {
        $this->init($table, $id, $parentObj);
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!($request instanceof ServerRequestInterface)) {
            return;
        }
        $queryParams = $request->getQueryParams();
        $reference = isset($queryParams['reference']) ? (int)$queryParams['reference'] : null;

        if ($command === 'copy' && $reference === 1 && !$commandIsProcessed && $table === 'tt_content' && !$this->getTceMain()->isImporting) {
            $dataArray = [
                'pid' => $value,
                'CType' => 'shortcut',
                'records' => $id,
                'header' => 'Reference',
            ];

            // used for overriding container and column with real target values
            if (is_array($pasteUpdate) && !empty($pasteUpdate)) {
                $dataArray = array_merge($dataArray, $pasteUpdate);
            }

            $clipBoard = $queryParams['CB'] ??= null;
            if (!empty($clipBoard)) {
                $updateArray = $clipBoard['update'];
                if (!empty($updateArray)) {
                    $dataArray = array_merge($dataArray, $updateArray);
                }
            }

            $data = [];
            $data['tt_content']['NEW234134'] = $dataArray;

            $this->getTceMain()->start($data, []);
            $this->getTceMain()->process_datamap();

            $commandIsProcessed = true;
        }

        if ($table === 'tt_content') {
            $this->cleanupWorkspacesAfterFinalizing();
        }
    }
}
