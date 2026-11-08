<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\EventListener;

/***************************************************************
 *  Copyright notice
 *  (c) 2021-2026 Ephraim Härer <mail@ephra.im>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project
 *  is free software; you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads the paste toolbar of the page module and hands over its configuration.
 */
final readonly class ModifyPageLayoutContentListener
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private UriBuilder $uriBuilder,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * @param ModifyPageLayoutContentEvent $event
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws RouteNotFoundException
     */
    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $backendUser = $this->getBackendUser();
        $this->pageRenderer->addCssFile('EXT:paste_reference/Resources/Public/Css/paste-toolbar.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:paste_reference/Resources/Private/Language/locallang_db.xlf', 'tx_paste_reference_js');
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@ehaerer/paste-reference/paste-toolbar.js')->instance([
                'clipboard' => $this->getClipboardItem($event->getRequest()),
                'referenceAllowed' => $backendUser->checkAuthMode('tt_content', 'CType', 'shortcut'),
                'copyFromPageEnabled' => $this->isCopyFromPageEnabled($backendUser),
                'confirmPaste' => $backendUser->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE),
                'elementBrowserUrl' => (string)$this->uriBuilder->buildUriFromRoute('wizard_element_browser', ['mode' => 'db']),
            ])
        );
    }

    /**
     * @return array{uid: int, title: string, language: int, copyMode: string}|null
     */
    private function getClipboardItem(ServerRequestInterface $request): ?array
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard($request);
        $clipboard->lockToNormal();
        $clipboard->cleanCurrent();
        $clipboard->endClipboard();

        $elements = $clipboard->elFromTable('tt_content');
        if ($elements === []) {
            return null;
        }
        $uid = (int)substr((string)array_key_first($elements), strlen('tt_content|'));
        $record = BackendUtility::getRecordWSOL('tt_content', $uid);
        if ($record === null) {
            return null;
        }
        return [
            'uid' => $uid,
            'title' => (string)BackendUtility::getRecordTitle('tt_content', $record, false, false),
            'language' => (int)($record['sys_language_uid'] ?? 0),
            'copyMode' => (string)($clipboard->clipData['normal']['mode'] ?? ''),
        ];
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    private function isCopyFromPageEnabled(BackendUserAuthentication $backendUser): bool
    {
        $configuration = $this->extensionConfiguration->get('paste_reference');
        return !($configuration['disableCopyFromPageButton'] ?? false)
            && !($backendUser->uc['disableCopyFromPageButton'] ?? false);
    }

    /**
     * @return BackendUserAuthentication
     */
    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
