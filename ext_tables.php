<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

defined('TYPO3_MODE') || die();

(static function ($extKey = 'eh_site_ephespage') {
    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] = 'EHAERER\\PasteReference\\Hooks\\PageRenderer->addJSCSS';
    }

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487270761] = \EHAERER\PasteReference\ContextMenu\ItemProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = \EHAERER\PasteReference\Hooks\DataHandler::class;

})();
