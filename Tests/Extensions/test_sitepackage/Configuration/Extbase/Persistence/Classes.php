<?php

declare(strict_types=1);

return [
    \Test\Sitepackage\Domain\Model\Irre::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'childRecords' => [
                'fieldName' => 'tx_testsitepacke_kids',
            ],
        ],
    ],
];
