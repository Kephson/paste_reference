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
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShortcutPreviewRenderer implements PreviewRendererInterface
{
    /** @var array<string, mixed> */
    protected array $extensionConfiguration = [];
    protected TtContentRepository $ttContentRepository;
    protected StandardContentPreviewRenderer $standardContentPreviewRenderer;
    protected RecordFactory $recordFactory;
    protected array $itemLabels = [];
    private readonly TcaSchemaFactory $tcaSchemaFactory;

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct()
    {
        /** @var array<non-empty-string, string|int|float|bool|null> $emConf */
        $emConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference') ?? [];
        $this->extensionConfiguration = $emConf;
        $this->ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);
        $this->standardContentPreviewRenderer = GeneralUtility::makeInstance(StandardContentPreviewRenderer::class);
        $this->recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
        $this->tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
    }

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        // $this->runtimeCache->set('tx_container_current_gridColumItem', $item);
        return $this->standardContentPreviewRenderer->renderPageModulePreviewHeader($item);
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
        // $this->standardContentPreviewRenderer->getProcessedValue($gridColumnItem, 'header_position,header_layout,header_link', $infoArr);
        $dataRow = $gridColumnItem->getRecord()->getRawRecord()->toArray();

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
                $recordType = $shortcutRecord->getRecordType();
                $preview .= $this->getRenderedPreviewItem(
                    BackendHelper::getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.' . $recordType),
                    $shortcutItem->getEditUrl(),
                    BackendHelper::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit'),
                    $this->getFakeItemForPreview($shortcutItem)->getPreview()
                );
            }
            return $preview;
        }
        return $this->standardContentPreviewRenderer->renderPageModulePreviewContent($gridColumnItem);
    }

    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        return $this->standardContentPreviewRenderer->renderPageModulePreviewFooter($item);
    }

    public function wrapPageModulePreview(
        string $previewHeader,
        string $previewContent,
        GridColumnItem $item
    ): string {
        return $this->standardContentPreviewRenderer->wrapPageModulePreview(
            $previewHeader,
            $previewContent,
            $item
        );
    }

    /**
     * @param GridColumnItem $gridColumnItem
     * @return list<RecordInterface>
     * @throws DBALException
     */
    protected function addShortcutRenderItems(GridColumnItem $gridColumnItem): array
    {
        $renderItems = [];
        $dataRow = $gridColumnItem->getRecord()->getRawRecord()->toArray();

        $shortcutItems = explode(',', $dataRow['records']);
        $collectedItems = [];
        foreach ($shortcutItems as $shortcutItem) {
            $shortcutItem = trim($shortcutItem);
            if (str_contains($shortcutItem, 'pages_')) {
                $this->ttContentRepository->collectContentDataFromPages(
                    $shortcutItem,
                    $collectedItems,
                    $dataRow['uid'],
                    $dataRow['sys_language_uid'],
                    $dataRow['recursive']
                );
            } elseif (!str_contains($shortcutItem, '_') || str_contains($shortcutItem, 'tt_content_')) {
                $this->ttContentRepository->collectContentData(
                    $shortcutItem,
                    $collectedItems,
                    $dataRow['uid'],
                    $dataRow['sys_language_uid']
                );
            }
        }
        if (!empty($collectedItems)) {
            $dataRow['shortcutItems'] = [];
            foreach ($collectedItems as $item) {
                if ($item) {
                    $itemObj = $this->recordFactory->createFromDatabaseRow('tt_content', $item);
                    $renderItems[] = $itemObj; // $this->prepareRecord($itemObj);
                }
            }
        }
        return $renderItems;
    }

    /**
     * creates a copy of the included Record, just with instantiated images.
     * This copy is fed back into the GridItem for further common procedures.
     * Common shortcut items don't have images included.
     * The resulting GridColumnItem is only used for the preview.
     * Altering the current record instead seems not possible.
     */
    protected function getFakeItemForPreview(GridColumnItem $gridColumnItem): GridColumnItem
    {
        $unrelatedTypes = [
            'bullets',
            'header',
            'html',
            'menu_abstract',
            'menu_categorized_content',
            'menu_categorized_pages',
            'menu_pages',
            'menu_recently_updated',
            'menu_related_pages',
            'menu_section',
            'menu_section_pages',
            'menu_sitemap',
            'menu_sitemap_pages',
            'menu_subpages',
            'shortcut',
        ];
        $recordObj = $gridColumnItem->getRecord();
        $rawRecord = $recordObj->getRawRecord();
        $recordType = $recordObj->getRecordType();
        $schema = $this->tcaSchemaFactory->get($recordObj->getMainType());
        $subSchema = $schema->getSubSchema($recordType);
        if (in_array($recordType, $unrelatedTypes)) {
            return $gridColumnItem;
        }
        $dataRow = $rawRecord->toArray();
        if (!$schema->hasSubSchema($recordType)) {

        }
        $doProcess = false;
        foreach ($subSchema->getFieldsOfType(TableColumnType::FILE) as $field) {
            $fieldName = $field->getName();
            if ($recordObj->has($fieldName)) {
                $doProcess = true;
            }
        }
        if (!$doProcess) {
            return $gridColumnItem;
        }
        $fakeRecordDataRow = $dataRow;
        foreach ($subSchema->getFieldsOfType(TableColumnType::FILE) as $field) {
            $fieldName = $field->getName();
            if ($recordObj->has($fieldName)) {
                if (is_object($recordObj->get($fieldName))) {
                    continue;
                }
                $tableName = 'tt_content';
                $fakeRecordDataRow[$fieldName] = BackendUtility::resolveFileReferences(
                    $tableName,
                    $fieldName,
                    $fakeRecordDataRow
                );
            }
        }
        $rawFakeRecord = GeneralUtility::makeInstance(
            RawRecord::class,
            $fakeRecordDataRow['uid'],
            $fakeRecordDataRow['pid'],
            $fakeRecordDataRow,
            $rawRecord->getComputedProperties(),
            $rawRecord->getFullType()
        );
        $fakeRecord = GeneralUtility::makeInstance(
            Record::class,
            $rawFakeRecord,
            $fakeRecordDataRow
        );
        $gridColumnItem->setRecord($fakeRecord);
        return $gridColumnItem;
    }

    protected function getRenderedPreviewItem($schema, $url, $actionLabel, $content)
    {
        return '<p class="pt-2 small"><b><a href="' . $url . '">' . $schema . '<br>' . $actionLabel . '</a></b></p>'
            . '<div class="mb-2 p-2 border position-relative reference">'
            . $content
            . '<div class="reference-overlay bg-primary-subtle opacity-25 position-absolute top-0 start-0 w-100 h-100 pe-none"></div>'
            . '</div>';
    }
}
