<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
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

use EHAERER\PasteReference\Helper\Helper;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Controller\PageLayoutController as CorePageLayoutController;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which adds the necessary ExtJS and pure JS stuff for the grid elements.
 *
 * @author Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 */
class PageLayoutController
{
    /**
     * @var array|mixed
     */
    protected array $extensionConfiguration = [];

    /**
     * @var Helper|null
     */
    protected ?Helper $helper;

    /**
     * @var PageRenderer|mixed|object|LoggerAwareInterface|SingletonInterface|null
     */
    protected PageRenderer $pageRenderer;

    /**
     * @var IconFactory|mixed|object|LoggerAwareInterface|(IconFactory&LoggerAwareInterface)|(IconFactory&SingletonInterface)|SingletonInterface|null
     */
    protected IconFactory $iconFactory;

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference');
        $this->helper = Helper::getInstance();
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @param array $parameters
     * @param CorePageLayoutController $pageLayoutController
     * @return void
     */
    public function drawHeaderHook(array $parameters, CorePageLayoutController $pageLayoutController)
    {
        $this->pageRenderer->loadJavaScriptModule('@haerer/paste-reference/PasteReferenceOnReady.js');
        $this->pageRenderer->loadJavaScriptModule('@haerer/paste-reference/PasteReferenceDragDrop.js');

        $clipObj = GeneralUtility::makeInstance(Clipboard::class); // Start clipboard
        $clipObj->initializeClipboard();
        $clipObj->lockToNormal();
        $clipBoard = $clipObj->clipData['normal'];
        if (!$this->pageRenderer->getCharSet()) {
            $this->pageRenderer->setCharSet('utf-8');
        }

        // pull locallang_db.xml to JS side - only the tx_paste_reference_js-prefixed keys
        $this->pageRenderer->addInlineLanguageLabelFile(
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
        try {
            $pAddExtOnReadyCode .= '
                top.pasteReferenceAllowed = ' . ($this->getBackendUser()->checkAuthMode(
                    'tt_content',
                    'CType',
                    'shortcut',
                    $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']
                ) ? 'true' : 'false') . ';
                top.browserUrl = ' . json_encode((string)$uriBuilder->buildUriFromRoute('wizard_element_browser')) . ';';
        } catch (RouteNotFoundException $e) {
        }

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

        if (!(bool)($this->extensionConfiguration['disableCopyFromPageButton'] ?? false)
            && !(bool)($this->getBackendUser()->uc['disableCopyFromPageButton'] ?? false)
        ) {
            $pAddExtOnReadyCode .= '
                    top.copyFromAnotherPageLinkTemplate = ' . json_encode('<a class="t3js-paste-new btn btn-default" title="' . $this->getLanguageService()->sL('LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xlf:tx_paste_reference_js.copyfrompage') . '">' . $this->iconFactory->getIcon(
                        'actions-insert-reference',
                        Icon::SIZE_SMALL
                    )->render() . '</a>') . ';';
        }

        $this->pageRenderer->addJsInlineCode('pasterefExtOnReady', $pAddExtOnReadyCode);
    }

    /**
     * Gets the current backend user.
     *
     * @return BackendUserAuthentication|null
     */
    public function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * getter for language service
     *
     * @return LanguageService|null
     */
    public function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
