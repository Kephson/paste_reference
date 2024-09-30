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
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageLayoutController
{
    /**
     * @var array<string, mixed>
     */
    protected array $extensionConfiguration = [];

    /**
     * @var PageRenderer
     */
    protected PageRenderer $pageRenderer;

    /**
     * @var IconFactory
     */
    protected IconFactory $iconFactory;

    /**
     * @var Helper
     */
    protected Helper $helper;

    /**
     * @param PageRenderer $pageRenderer
     * @param IconFactory $iconFactory
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(PageRenderer $pageRenderer, IconFactory $iconFactory)
    {
        /** @var array<non-empty-string, mixed>|null $emConf */
        $emConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference');
        $this->extensionConfiguration = is_array($emConf) ? $emConf : [];
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->helper = GeneralUtility::makeInstance(Helper::class);
    }

    /**
     * @return string
     */
    public function drawHeaderHook(): string
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard();
        $clipboard->lockToNormal();
        $clipboard->cleanCurrent();
        $clipboard->endClipboard();

        $elFromTable = $clipboard->elFromTable('tt_content');

        // pull locallang_db.xml to JS side - only the tx_paste_reference_js-prefixed keys
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:paste_reference/Resources/Private/Language/locallang_db.xlf',
            'tx_paste_reference_js'
        );

        $pAddExtOnReadyCode = '';

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $pAddExtOnReadyCode .= '
                top.pasteReferenceAllowed = ' . (int)($this->helper->getBackendUser()?->checkAuthMode(
                'tt_content',
                'CType',
                'shortcut'
            ) ?? false) . ';
                top.browserUrl = ' . json_encode((string)$uriBuilder->buildUriFromRoute('wizard_element_browser')) . ';';
        } catch (RouteNotFoundException $e) {
        }

        if (!empty($elFromTable)) {
            $pasteItem = (int)substr((string)key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecordWSOL('tt_content', $pasteItem) ?? [];
            $pasteTitle = BackendUtility::getRecordTitle('tt_content', $pasteRecord);

            if (!(bool)($this->extensionConfiguration['disableCopyFromPageButton'] ?? false)
                && !(bool)($this->helper->getBackendUser()->uc['disableCopyFromPageButton'] ?? false)
            ) {
                /* @todo: the file @haerer/paste-reference/paste-reference.js doesn't exist.
                 * @todo: It should add a button next to "+ Content"
                 * $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                 * JavaScriptModuleInstruction::create('@haerer/paste-reference/paste-reference.js')
                 * ->instance([
                 * 'itemOnClipboardUid' => $pasteItem,
                 * 'itemOnClipboardTitle' => $pasteTitle,
                 * 'copyMode' => $clipboard->clipData['normal']['mode'] ?? '',
                 * ])
                 * );*/

                $pAddExtOnReadyCode .= '
                    top.copyFromAnotherPageLinkTemplate = ' . json_encode('<button type="button" class="t3js-paste-new btn btn-default" title="' . ($this->helper->getLanguageService()?->sL('LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xml:tx_paste_reference_js.copyfrompage') ?? 'unknown') . '">' . $this->iconFactory->getIcon(
                    'actions-insert-reference',
                    Icon::SIZE_SMALL
                )->render() . '</button>') . ';';
            }
        }

        $this->pageRenderer->addJsInlineCode(
            'pasterefExtOnReady',
            $pAddExtOnReadyCode,
            true,
            false,
            true
        );

        return '';
    }

}
