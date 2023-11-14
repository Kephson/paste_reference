<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

defined('TYPO3') || die();

(static function () {

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['disableCopyFromPageButton'] = [
        'type' => 'check',
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
    ];

    $GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',
    --div--;LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:pasteReference,
        disableCopyFromPageButton
        ';
})();
