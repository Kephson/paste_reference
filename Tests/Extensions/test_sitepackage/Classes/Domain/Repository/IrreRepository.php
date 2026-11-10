<?php

declare(strict_types=1);

/*
 * This file is part of the package test/sitepackage.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Test\Sitepackage\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class IrreRepository extends Repository
{
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        // Show comments from all pages
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function getChildRecords(int $uid): QueryResultInterface
    {
        $currentRecord = $this->findByUid($uid);
        $childRecords = [];
        $query = $this->createQuery();
        $childRecords = $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('tablenames', 'tt_content'),
                    $query->equals('tx_testsitepacke_parent', $uid),
                ),
            )
            ->execute();
        return $childRecords;
    }
}
