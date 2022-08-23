<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

defined('TYPO3') || die();

(static function () {

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487270761] = \EHAERER\PasteReference\ContextMenu\ItemProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;

})();
