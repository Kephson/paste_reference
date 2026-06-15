<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\PageLayoutView;

/***************************************************************
 *  Copyright notice
 *  (c) 2023 Ephraim Härer <mail@ephra.im>
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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

use Doctrine\DBAL\Exception as DBALException;
// use Doctrine\DBAL\DriverException as DBALDriverException;
use EHAERER\PasteReference\Domain\Repository\TtContentRepository;
use EHAERER\PasteReference\Helper\BackendHelper;
use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShortcutPreviewRenderer extends StandardContentPreviewRenderer implements PreviewRendererInterface
{
    /** @var array<string, mixed> */
    protected array $extensionConfiguration = [];
    protected int $majorTypo3Version = 0;
    protected TtContentRepository $ttContentRepository;
    protected BackendHelper $backendHelper;

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct()
    {
        /** @var array<non-empty-string, string|int|float|bool|null> $emConf */
        $emConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference') ?? [];
        $this->extensionConfiguration = $emConf;
        $this->majorTypo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        $this->ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);
        $this->backendHelper = GeneralUtility::makeInstance(BackendHelper::class);
    }

    /**
     * Dedicated method for rendering preview body HTML for
     * the page module only. Receives the GridColumnItem
     * that contains the record for which a preview should be
     * rendered and returned.
     *
     * @param $gridColumnItem GridColumnItem
     * @return string
     * @throws DBALException
     */
    public function renderPageModulePreviewContent(GridColumnItem $gridColumnItem): string
    {
        $infoArr = [];
        $tsConfigPage = [];
        $tsConfig = [];
        $this->getProcessedValue($gridColumnItem, 'header_position,header_layout,header_link', $infoArr);
        $dataRow = $this->getDataRow($gridColumnItem);

        if (!empty($dataRow['pid']) && $tsConfigPage = BackendUtility::getPagesTSconfig($dataRow['pid'])) {
            $tsConfig = $tsConfigPage['mod.']['web_layout.']['tt_content.']['preview.'] ?? [];
        }

        if (!empty($dataRow['records'])) {
            $shortCutRenderItems = $this->addShortcutRenderItems($gridColumnItem);
            $preview = '';
            foreach ($shortCutRenderItems as $shortcutRecord) {
                $shortcutItem = GeneralUtility::makeInstance(
                    GridColumnItem::class,
                    $gridColumnItem->getContext(),
                    $gridColumnItem->getColumn(),
                    $shortcutRecord
                );
                $preview .= $this->getRenderedPreviewItem(
                    $shortcutItem->getEditUrl(),
                    $this->backendHelper->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit'),
                    $this->getPreviewShortcutItem($shortcutItem)
                );
            }
            return $preview;
        }
        return parent::renderPageModulePreviewContent($gridColumnItem);
    }

    protected function getRenderedPreviewItem($url, $actionLabel, $content)
    {
        return '<p class="pt-2 small"><b><a href="' . $url . '">' . $actionLabel . '</a></b></p>'
            . '<div class="mb-2 p-2 border position-relative reference">'
            . $content
            . '<div class="reference-overlay bg-primary-subtle opacity-25 position-absolute top-0 start-0 w-100 h-100 pe-none"></div>'
            . '</div>';
    }

    public function getPreviewShortcutItem(GridColumnItem $gridColumnItem): string
    {
        $previewShortcutItem = $gridColumnItem->getPreview();
        return $previewShortcutItem;
    }

    protected function getDataRow($gridColumnItem): array
    {
        if ($this->majorTypo3Version >= 14) {
            $rawRecord = $gridColumnItem->getRecord()->getRawRecord();
            $dataRow = $rawRecord->toArray();
        } else {
            $dataRow = $gridColumnItem->getRecord();
        }
        return $dataRow;
    }

    /**
     * @param GridColumnItem $gridColumnItem
     * @return array
     * @throws DBALException
     */
    protected function addShortcutRenderItems(GridColumnItem $gridColumnItem): array
    {
        $renderItems = [];
        $dataRow = $this->getDataRow($gridColumnItem);

        $shortcutItems = explode(',', $dataRow['records']);
        $collectedItems = [];
        foreach ($shortcutItems as $shortcutItem) {
            $shortcutItem = trim($shortcutItem);
            if (str_contains($shortcutItem, 'pages_')) {
                $this->ttContentRepository->collectContentDataFromPages(
                    $shortcutItem,
                    $collectedItems,
                    $dataRow['recursive'],
                    $dataRow['uid'],
                    $dataRow['sys_language_uid']
                );
            } else {
                if (!str_contains($shortcutItem, '_') || str_contains($shortcutItem, 'tt_content_')) {
                    $this->ttContentRepository->collectContentData(
                        $shortcutItem,
                        $collectedItems,
                        $dataRow['uid'],
                        $dataRow['sys_language_uid']
                    );
                }
            }
        }
        if (!empty($collectedItems)) {
            $dataRow['shortcutItems'] = [];
            foreach ($collectedItems as $item) {
                if ($item) {
                    if ($this->majorTypo3Version >= 14) {
                        $renderItems[] = $this->getContentRecordObj($item);
                    } else {
                        $renderItems[] = $item;
                    }
                }
            }
            if ($this->majorTypo3Version >= 14) {
                $recordObj = $this->getContentRecordObj($dataRow);
                $record = $gridColumnItem->getRecord();
                $gridColumnItem->setRecord($record);
            } else {
                $record = $gridColumnItem->getRecord();
                $gridColumnItem->setRecord($record);
            }
        }
        return $renderItems;
    }

    protected function getContentRecordObj(array $record): RecordInterface
    {
        $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
        $recordObj = $recordFactory->createFromDatabaseRow('tt_content', $record);
        return $recordObj;
    }
}
