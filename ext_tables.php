<?php

defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function () {
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['disableCopyFromPageButton'] = [
        'type' => 'check',
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
    ];

    ExtensionManagementUtility::addFieldsToUserSettings(
        '--div--;LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:pasteReference,disableCopyFromPageButton',
    );
})();
