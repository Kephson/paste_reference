<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

defined('TYPO3') || die();

(static function () {

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']['gridelements'] = \EHAERER\PasteReference\Hooks\PageLayoutController::class . '->drawHeaderHook';

    $GLOBALS['TBE_STYLES']['skins']['paste_reference']['name'] = 'paste_reference';
    $GLOBALS['TBE_STYLES']['skins']['paste_reference']['stylesheetDirectories']['paste_reference_structure'] = 'EXT:paste_reference/Resources/Public/Backend/Css/Skin/';

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487270761] = \EHAERER\PasteReference\ContextMenu\ItemProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['disableCopyFromPageButton'] = [
        'type'  => 'check',
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
    ];

    $GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',
    --div--;LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:pasteReference,
        disableCopyFromPageButton
        ';
})();
