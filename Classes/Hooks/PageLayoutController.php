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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageLayoutController extends \TYPO3\CMS\Backend\Controller\PageLayoutController
{
    /**
     * @var array|mixed
     */
    protected array $extensionConfiguration = [];

    /**
     * @var Helper
     */
    protected Helper $helper;

    protected function initializeData(): void
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference');
        $this->helper = GeneralUtility::makeInstance(Helper::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    public function pasteReferenceModification(ServerRequestInterface $request): string
    {
        $this->initializeData();

        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard($request);
        $clipboard->lockToNormal();
        $clipboard->cleanCurrent();
        $clipboard->endClipboard();
        $elFromTable = $clipboard->elFromTable('tt_content');
        if (!empty($elFromTable)) {
            $pasteItem = (int)substr((string)key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecordWSOL('tt_content', $pasteItem);
            $pasteTitle = BackendUtility::getRecordTitle('tt_content', $pasteRecord);
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@ehaerer/paste-reference/paste-reference.js')
                    ->instance([
                        'itemOnClipboardUid' => $pasteItem,
                        'itemOnClipboardTitle' => $pasteTitle,
                        'copyMode' => $clipboard->clipData['normal']['mode'] ?? '',
                    ])
            );
        }

        // pull locallang_db.xlf to JS side - only the tx_paste_reference_js-prefixed keys
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:paste_reference/Resources/Private/Language/locallang_db.xlf',
            'tx_paste_reference_js'
        );

        return '';
    }

}
