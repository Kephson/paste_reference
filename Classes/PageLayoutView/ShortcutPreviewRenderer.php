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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use EHAERER\PasteReference\Helper\Helper;
use JsonException;
use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShortcutPreviewRenderer extends StandardContentPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $extensionConfiguration = [];
    protected Helper $helper;
    protected bool $showHidden = true;

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct()
    {
        /** @var array<non-empty-string, string|int|float|bool|null> $emConf */
        $emConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference') ?? [];
        $this->extensionConfiguration = $emConf;
        $this->helper = GeneralUtility::makeInstance(Helper::class);
    }

    /**
     * Dedicated method for rendering preview body HTML for
     * the page module only. Receives the GridColumnItem
     * that contains the record for which a preview should be
     * rendered and returned.
     *
     * @param $item GridColumnItem
     * @return string
     * @throws DBALException
     * @throws JsonException
     */
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();

        // Check if a Fluid-based preview template was defined for this CType
        // and render it via Fluid. Possible option:
        // mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html
        $infoArr = [];
        $this->getProcessedValue($item, 'header_position,header_layout,header_link', $infoArr);
        $tsConfig = BackendUtility::getPagesTSconfig($record['pid'])['mod.']['web_layout.']['tt_content.']['preview.'] ?? [];

        if (!empty($record['records'])) {
            $shortCutRenderItems = $this->addShortcutRenderItems($item);
            $preview = '';
            foreach ($shortCutRenderItems as $shortcutRecord) {
                $shortcutItem = GeneralUtility::makeInstance(GridColumnItem::class, $item->getContext(), $item->getColumn(), $shortcutRecord);
                $preview .= '<p class="pt-2 small"><b><a href="' . $shortcutItem->getEditUrl() . '">' . $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit') . '</a></b></p>';
                $preview .= '<div class="mb-2 p-2 border position-relative reference">' . $shortcutItem->getPreview() . '<div class="reference-overlay bg-primary-subtle opacity-25 position-absolute top-0 start-0 w-100 h-100 pe-none"></div></div>';
            }
            return $preview;
        }

        return parent::renderPageModulePreviewContent($item);
    }

    /**
     * @param GridColumnItem $gridColumnItem
     * @return array<int, array<non-empty-string, mixed>>
     * @throws DBALException
     */
    protected function addShortcutRenderItems(GridColumnItem $gridColumnItem): array
    {
        $renderItems = [];
        $record = $gridColumnItem->getRecord();
        $shortcutItems = explode(',', $record['records']);
        $collectedItems = [];
        foreach ($shortcutItems as $shortcutItem) {
            $shortcutItem = trim($shortcutItem);
            if (str_contains($shortcutItem, 'pages_')) {
                $this->collectContentDataFromPages(
                    $shortcutItem,
                    $collectedItems,
                    $record['recursive'],
                    $record['uid'],
                    $record['sys_language_uid']
                );
            } else {
                if (!str_contains($shortcutItem, '_') || str_contains($shortcutItem, 'tt_content_')) {
                    $this->collectContentData(
                        $shortcutItem,
                        $collectedItems,
                        $record['uid'],
                        $record['sys_language_uid']
                    );
                }
            }
        }
        if (!empty($collectedItems)) {
            $record['shortcutItems'] = [];
            foreach ($collectedItems as $item) {
                if ($item) {
                    $renderItems[] = $item;
                }
            }
            $gridColumnItem->setRecord($record);
        }
        return $renderItems;
    }

    /**
     * Collects tt_content data from a single page or a page tree starting at a given page
     *
     * @param string $shortcutItem The single page to be used as the tree root
     * @param array<int, array<non-empty-string, mixed>> $collectedItems The collected item data rows ordered by parent position, column position and sorting
     * @param int $recursive The number of levels for the recursion
     * @param int $parentUid uid of the referencing tt_content record
     * @param int $language sys_language_uid of the referencing tt_content record
     * @throws DBALException
     * @todo: move this in a repository
     *
     */
    protected function collectContentDataFromPages(
        string $shortcutItem,
        array  &$collectedItems,
        int    $recursive = 0,
        int    $parentUid = 0,
        int    $language = 0
    ): void
    {
        $itemList = str_replace('pages_', '', $shortcutItem);
        $itemList = GeneralUtility::intExplode(',', $itemList);

        $queryBuilder = $this->helper->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->addSelectLiteral($queryBuilder->expr()->inSet(
                    'pid',
                    $queryBuilder->createNamedParameter($itemList, ArrayParameterType::INTEGER)
                ) . ' AS inSet')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($itemList, ArrayParameterType::INTEGER)
                ),
                $queryBuilder->expr()->gte('colPos', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([0, -1], ArrayParameterType::INTEGER)
                )
            )
            ->orderBy('inSet')
            ->addOrderBy('colPos')
            ->addOrderBy('sorting')
            ->executeQuery();

        while ($item = $result->fetchAssociative()) {
            /** @var array<non-empty-string, string|int|float|bool|null> $item */
            if (!empty($this->extensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', (int)($item['uid'] ?? 0), $language) ?: [];
                if (is_array($translatedItem) && $translatedItem !== []) {
                    $item = array_shift($translatedItem);
                }
            }
            if ($this->getBackendUser()->workspace > 0) {
                unset($item['inSet']);
                BackendUtility::workspaceOL('tt_content', $item, $this->getBackendUser()->workspace);
            }
            $item['tx_paste_reference_container'] = $item['pid'];
            $collectedItems[] = $item;
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param string $shortcutItem The tt_content element to fetch the data from
     * @param array<int, array<non-empty-string, mixed>> $collectedItems The collected item data row
     * @param int $parentUid uid of the referencing tt_content record
     * @param int $language sys_language_uid of the referencing tt_content record
     * @throws DBALException
     * @todo: move this in a repository
     *
     */
    protected function collectContentData(string $shortcutItem, array &$collectedItems, int $parentUid, int $language): void
    {
        $shortcutItem = str_replace('tt_content_', '', $shortcutItem);
        if ((int)$shortcutItem !== $parentUid) {
            $queryBuilder = $this->helper->getQueryBuilder();
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
            if ($this->showHidden) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }
            /** @var array<non-empty-string, string|int|float|bool|null>|false $item */
            $item = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int)$shortcutItem, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            if (!empty($this->extensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', (int)($item['uid'] ?? 0), $language) ?: [];
                if (is_array($translatedItem) && $translatedItem !== []) {
                    $item = array_shift($translatedItem);
                }
            }

            if ($this->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL(
                    'tt_content',
                    $item,
                    $this->getBackendUser()->workspace
                );
            }
            $collectedItems[] = $item;
        }
    }
}
