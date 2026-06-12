<?php

defined('TYPO3') || die();

use EHAERER\PasteReference\Hooks\DataHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function () {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = DataHandler::class;

    $disableCopyFromPageButtonColumn = [
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
    ];
    if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 14) {
        // TYPO3 v14 renders the User Settings module through the FormEngine,
        // which builds a fake "be_users_settings" TCA from these columns and
        // expects a proper "config" block. Without it, SingleFieldContainer
        // reads an undefined "config" key and the whole module crashes.
        $disableCopyFromPageButtonColumn['config'] = [
            'type' => 'check',
        ];
    } else {
        // TYPO3 v13 and below render the setup form themselves and read the
        // legacy flat format with the type at the top level.
        $disableCopyFromPageButtonColumn['type'] = 'check';
    }
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['disableCopyFromPageButton'] = $disableCopyFromPageButtonColumn;

    ExtensionManagementUtility::addFieldsToUserSettings(
        '--div--;LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:pasteReference,disableCopyFromPageButton',
    );
})();
