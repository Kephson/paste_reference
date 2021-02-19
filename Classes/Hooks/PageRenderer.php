<?php

namespace EHAERER\PasteReference\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 *  (c) 2021 Ephraim Härer <mail@ephra.im>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Controller\RecordListController;

/**
 * Class/Function which adds the necessary ExtJS and pure JS stuff for the backend layout.
 *
 * @author Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 * @author Ephraim Härer <mail@ephra-im>
 */
class PageRenderer implements SingletonInterface
{
    /**
     * @var array
     */
    protected $extensionConfiguration;


    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference');
    }

    /**
     * wrapper function called by hook (\TYPO3\CMS\Core\Page\PageRenderer->render-preProcess)
     *
     * @param array $parameters An array of available parameters
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer The parent object that triggered this hook
     */
    public function addJSCSS(array $parameters, \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer)
    {
        if (!empty($GLOBALS['SOBE']) && (get_class($GLOBALS['SOBE']) === RecordListController::class || is_subclass_of(
                    $GLOBALS['SOBE'],
                    RecordListController::class
                ))) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/PasteReference/PasteReferenceOnReady');
            return;
        }
        if (!empty($GLOBALS['SOBE']) && (get_class($GLOBALS['SOBE']) === PageLayoutController::class || is_subclass_of(
                    $GLOBALS['SOBE'],
                    PageLayoutController::class
                ))) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/PasteReference/PasteReferenceOnReady');
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/PasteReference/PasteReferenceDragDrop');
            if ((bool)$this->extensionConfiguration['disableDragInWizard'] !== true) {
                $pageRenderer->loadRequireJsModule('TYPO3/CMS/PasteReference/PasteReferenceDragInWizard');
            }

            /** @var Clipboard $clipObj */
            $clipObj = GeneralUtility::makeInstance(Clipboard::class); // Start clipboard
            $clipObj->initializeClipboard();
            $clipObj->lockToNormal();
            $clipBoard = $clipObj->clipData['normal'];
            if (!$pageRenderer->getCharSet()) {
                $pageRenderer->setCharSet('utf-8');
            }

            // pull locallang_db.xlf to JS side - only the tx_paste_reference_js-prefixed keys
            $pageRenderer->addInlineLanguageLabelFile(
                'EXT:paste_reference/Resources/Private/Language/locallang_db.xlf',
                'tx_paste_reference_js'
            );

            $pAddExtOnReadyCode = '
                TYPO3.l10n = {
                    localize: function(langKey){
                        return TYPO3.lang[langKey];
                    }
                }
            ';

            // add Ext.onReady() code from file
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $pAddExtOnReadyCode .= '
            top.pasteReferenceAllowed = ' . ($this->getBackendUser()->checkAuthMode(
                    'tt_content',
                    'CType',
                    'shortcut',
                    $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']
                ) ? 'true' : 'false') . ';
            top.skipDraggableDetails = ' . ($this->getBackendUser()->uc['dragAndDropHideNewElementWizardInfoOverlay'] ? 'true' : 'false') . ';
            top.browserUrl = ' . json_encode((string)$uriBuilder->buildUriFromRoute('wizard_element_browser')) . ';';

            if (!empty($clipBoard) && !empty($clipBoard['el'])) {
                $clipBoardElement = GeneralUtility::trimExplode('|', key($clipBoard['el']));
                if ($clipBoardElement[0] === 'tt_content') {
                    $clipBoardElementData = BackendUtility::getRecord('tt_content', (int)$clipBoardElement[1]);
                    $pAddExtOnReadyCode .= '
            top.clipBoardElementCType = ' . json_encode($clipBoardElementData['CType']) . ';
            top.clipBoardElementListType = ' . json_encode($clipBoardElementData['list_type']) . ';';
                } else {
                    $pAddExtOnReadyCode .= "
            top.clipBoardElementCType = '';
            top.clipBoardElementListType = '';";
                }
            }

            if ((bool)$this->extensionConfiguration['disableCopyFromPageButton'] !== true
                && (bool)$this->getBackendUser()->uc['disableCopyFromPageButton'] !== true) {
                $pAddExtOnReadyCode .= '
                    top.copyFromAnotherPageLinkTemplate = ' . json_encode('<a class="t3js-paste-new btn btn-default" title="' . $this->getLanguageService()->sL('LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:tx_paste_reference_js.copyfrompage') . '">' . $iconFactory->getIcon(
                            'actions-insert-reference',
                            Icon::SIZE_SMALL
                        )->render() . '</a>') . ';';
            }

            $pageRenderer->addJsInlineCode('pasteReferenceExtOnReady', $pAddExtOnReadyCode);
        }
    }

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * getter for language service
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
