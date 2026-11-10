<?php

declare(strict_types=1);

use EHAERER\PasteReference\Controller\RecordInfoController;

return [
    'paste_reference_record_info' => [
        'path' => '/paste-reference/record-info',
        'target' => RecordInfoController::class . '::getRecordInfoAction',
    ],
];
