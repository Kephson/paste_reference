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
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageLayoutController
{
    /** @var array<string, mixed> */
    protected array $extensionConfiguration = [];
    protected string $LLL = 'LLL:EXT:paste_reference/Resources/Private/Language/locallang_db.xml';
    protected string $jsScriptName = '@ehaerer/paste-reference/paste-reference.js';
    protected array $elFromTable = [];
    protected string $copyMode = '';
    protected ?Helper $helper = null;
    protected ?IconFactory $iconFactory = null;
    protected ?PageRenderer $pageRenderer = null;

    /**
     * @param PageRenderer $pageRenderer
     * @param IconFactory $iconFactory
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(PageRenderer $pageRenderer, IconFactory $iconFactory)
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference') ?? [];
        $this->helper = GeneralUtility::makeInstance(Helper::class);
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;

        $this->elFromTable = $this->getClipboard()->elFromTable('tt_content');
        $this->copyMode = $this->getClipboard()->clipData['normal']['mode'] ?? '';
    }

    /**
     * @return string
     */
    public function drawHeaderHook(): string
    {
        $languageFile = str_starts_with($this->LLL, 'LLL:') ? substr($this->LLL, 4) : $this->LLL;
        $this->pageRenderer->addInlineLanguageLabelFile($languageFile, 'tx_paste_reference_js');

        $jsLines = [];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $jsLines[] = 'top.pasteReferenceAllowed = ' . (int)$this->helper->getBackendUser()->checkAuthMode('tt_content', 'CType', 'shortcut') . ';';
            $jsLines[] = 'top.browserUrl = ' . json_encode((string)$uriBuilder->buildUriFromRoute('wizard_element_browser')) . ';';
        } catch (RouteNotFoundException $e) {
        }

        if (!empty($this->elFromTable)) {
            $this->addJavaScriptModuleInstruction();
            $jsLines[] = 'top.copyMode = "' . $this->copyMode . '";';
        }

        if (
            !(bool)($this->extensionConfiguration['disableCopyFromPageButton'] ?? false)
            && !(bool)($this->helper->getBackendUser()->uc['disableCopyFromPageButton'] ?? false)
        ) {
            $jsLines[] = 'top.copyFromAnotherPageLinkTemplate = ' . json_encode($this->getButtonTemplate()) . ';';
        }

        $javaScript = implode("\n", $jsLines);
        $this->pageRenderer->addJsInlineCode('pasteReference', $javaScript, true, false, true);
        $this->pageRenderer->loadJavaScriptModule($this->jsScriptName);

        return '';
    }

    protected function getButtonTemplate(): string
    {
        $title = $this->helper->getLanguageService()->sL($this->LLL . ':tx_paste_reference_js.copyfrompage');
        $icon = $this->iconFactory->getIcon('actions-insert-reference', Icon::SIZE_SMALL)->render();
        // the CSS-class "t3js-paste-new" does not exist in system extensions
        return '<button type="button" class="t3js-paste-new btn btn-default btn-sm" title="' . $title . '">' . $icon . '</button>';
    }

    protected function addJavaScriptModuleInstruction(): void
    {
        $JavaScriptModuleInstruction = JavaScriptModuleInstruction::create($this->jsScriptName);
        /** @var \TYPO3\CMS\Core\Page\JavaScriptRenderer $javaScriptRenderer */
        $javaScriptRenderer = $this->pageRenderer->getJavaScriptRenderer();
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            $JavaScriptModuleInstruction->instance(
                $this->getJsArgumentsArray()
            )
        );
    }

    protected function getJsArgumentsArray(): array
    {
        $pasteItem = (int)substr((string)key($this->elFromTable), 11);
        $pasteRecord = BackendUtility::getRecordWSOL('tt_content', $pasteItem);
        $pasteTitle = BackendUtility::getRecordTitle('tt_content', $pasteRecord);
        return [
            'itemOnClipboardUid' => $pasteItem,
            'itemOnClipboardTitle' => $pasteTitle,
            'copyMode' => $this->copyMode,
        ];
    }

    protected function getClipboard(): Clipboard
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard();
        $clipboard->lockToNormal();
        $clipboard->cleanCurrent();
        $clipboard->endClipboard();
        return $clipboard;
    }

}
