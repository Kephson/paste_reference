<?php

defined('TYPO3') || die();

use EHAERER\PasteReference\Hooks\DataHandler;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function () {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = DataHandler::class;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['disableCopyFromPageButton'] = [
        'type' => 'check',
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
    ];

    ExtensionManagementUtility::addFieldsToUserSettings(
        '--div--;LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:pasteReference,disableCopyFromPageButton',
    );
})();
