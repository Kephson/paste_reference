<?php

declare(strict_types=1);

namespace Test\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/*
 * This file is part of the package test/sitepackage.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

class Irre extends AbstractEntity
{
    protected $txTestsitepackeParent = 0;

    /**
     * @var ObjectStorage<Irre>
     */
    protected ?ObjectStorage $childRecords = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->childRecords = new ObjectStorage();
    }

    public function getTxTestsitepackeParent(): int
    {
        return $this->txTestsitepackeParent;
    }

    public function setTxTestsitepackeParent(int $txTestsitepackeParent): void
    {
        $this->txTestsitepackeParent = $txTestsitepackeParent;
    }

    /**
     * @return ObjectStorage<Irre>
     */
    public function getChildRecords(): ObjectStorage
    {
        return $this->childRecords; // ?? new ObjectStorage();
    }

    /**
     * @param ObjectStorage<Irre> $childRecords
     */
    public function setChildRecords(ObjectStorage $childRecords): void
    {
        $this->childRecords = $childRecords;
    }

    public function addChildRecord(Irre $childRecord): void
    {
        $this->childRecords->attach($childRecord);
    }

    public function removeChildRecord(Irre $childRecord): void
    {
        $this->childRecords->detach($childRecord);
    }
}
