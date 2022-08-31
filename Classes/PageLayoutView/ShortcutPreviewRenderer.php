<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\PageLayoutView;

/***************************************************************
 *  Copyright notice
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

use EHAERER\PasteReference\Helper\Helper;
use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShortcutPreviewRenderer extends StandardContentPreviewRenderer implements PreviewRendererInterface
{
    protected array $extensionConfiguration = [];
    protected Helper $helper;
    protected IconFactory $iconFactory;
    protected QueryGenerator $tree;
    protected bool $showHidden = true;
    protected string $backPath = '';

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference');
        $this->helper = Helper::getInstance();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Dedicated method for rendering preview body HTML for
     * the page module only. Receives the the GridColumnItem
     * that contains the record for which a preview should be
     * rendered and returned.
     */
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $out = '';
        $content = parent::renderPageModulePreviewContent($item);
        $context = $item->getContext();
        $record = $item->getRecord();

        $drawItem = true;
        $hookPreviewContent = '';
        // Hook: Render an own preview of a record
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'])) {
            $pageLayoutView = PageLayoutView::createFromPageLayoutContext($item->getContext());
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
                    throw new \UnexpectedValueException(
                        $className . ' must implement interface ' . PageLayoutViewDrawItemHookInterface::class,
                        1582574553
                    );
                }
                $hookObject->preProcess($pageLayoutView, $drawItem, $previewHeader, $hookPreviewContent, $record);
            }
            $item->setRecord($record);
        }

        if (!$drawItem) {
            return $hookPreviewContent;
        }
        // Check if a Fluid-based preview template was defined for this CType
        // and render it via Fluid. Possible option:
        // mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html
        $infoArr = [];
        $this->getProcessedValue($item, 'header_position,header_layout,header_link', $infoArr);
        $tsConfig = BackendUtility::getPagesTSconfig($record['pid'])['mod.']['web_layout.']['tt_content.']['preview.'] ?? [];
        if (!empty($tsConfig[$record['CType']]) || !empty($tsConfig[$record['CType'] . '.'])) {
            $fluidPreview = $this->renderContentElementPreviewFromFluidTemplate($record);
            if ($fluidPreview !== null) {
                return $fluidPreview;
            }
        }

        if (!empty($record['records'])) {
            $shortCutRenderItems = $this->addShortcutRenderItems($item);
            $preview = '';
            foreach ($shortCutRenderItems as $shortcutRecord) {
                $shortcutItem = GeneralUtility::makeInstance(GridColumnItem::class, $item->getContext(), $item->getColumn(), $shortcutRecord);
                $preview .= '<p class="pt-2 small"><b><a href="' . $shortcutItem->getEditUrl() . '">' . $this->getLanguageService()->getLL('edit') . '</a></b></p>';
                $preview .= '<div class="mb-2 p-2 border reference">' . $shortcutItem->getPreview() . '<div class="reference-overlay"></div></div>';
            }
            return $preview;
        }
        return parent::renderPageModulePreviewContent($item);
    }

    protected function addShortcutRenderItems(GridColumnItem $gridColumnItem): array
    {
        $renderItems = [];
        $record = $gridColumnItem->getRecord();
        $shortcutItems = explode(',', $record['records']);
        $collectedItems = [];
        foreach ($shortcutItems as $shortcutItem) {
            $shortcutItem = trim($shortcutItem);
            if (strpos($shortcutItem, 'pages_') !== false) {
                $this->collectContentDataFromPages(
                    $shortcutItem,
                    $collectedItems,
                    $record['recursive'],
                    $record['uid'],
                    $record['sys_language_uid']
                );
            } else {
                if (strpos($shortcutItem, '_') === false || strpos($shortcutItem, 'tt_content_') !== false) {
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
     * @param string $shortcutItem : The single page to be used as the tree root
     * @param array $collectedItems : The collected item data rows ordered by parent position, column position and sorting
     * @param int $recursive : The number of levels for the recursion
     * @param int $parentUid : uid of the referencing tt_content record
     * @param int $language : sys_language_uid of the referencing tt_content record
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function collectContentDataFromPages(
        string $shortcutItem,
        array  &$collectedItems,
        int    $recursive = 0,
        int    $parentUid = 0,
        int    $language = 0
    )
    {
        $itemList = str_replace('pages_', '', $shortcutItem);
        if ($recursive) {
            if (!$this->tree instanceof QueryGenerator) {
                $this->tree = GeneralUtility::makeInstance(QueryGenerator::class);
            }
            $itemList = $this->tree->getTreeList($itemList, $recursive, 0, 1);
        }
        $itemList = GeneralUtility::intExplode(',', $itemList);

        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->addSelectLiteral($queryBuilder->expr()->inSet(
                    'pid',
                    $queryBuilder->createNamedParameter($itemList, Connection::PARAM_INT_ARRAY)
                ) . ' AS inSet')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($itemList, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->gte('colPos', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY)
                )
            )
            ->orderBy('inSet')
            ->addOrderBy('colPos')
            ->addOrderBy('sorting')
            ->executeQuery();

        while ($item = $result->fetchAssociative()) {
            if (!empty($this->extensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', $item['uid'], $language);
                if (!empty($translatedItem)) {
                    $item = array_shift($translatedItem);
                }
            }
            if ($this->helper->getBackendUser()->workspace > 0) {
                unset($item['inSet']);
                BackendUtility::workspaceOL('tt_content', $item, $this->helper->getBackendUser()->workspace);
            }
            $item['tx_gridelements_reference_container'] = $item['pid'];
            $collectedItems[] = $item;
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param string $shortcutItem : The tt_content element to fetch the data from
     * @param array $collectedItems : The collected item data row
     * @param int $parentUid : uid of the referencing tt_content record
     * @param int $language : sys_language_uid of the referencing tt_content record
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function collectContentData(string $shortcutItem, array &$collectedItems, int $parentUid, int $language)
    {
        $shortcutItem = str_replace('tt_content_', '', $shortcutItem);
        if ((int)$shortcutItem !== $parentUid) {
            $queryBuilder = $this->getQueryBuilder();
            if ($this->showHidden) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }
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
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', $item['uid'], $language);
                if (!empty($translatedItem)) {
                    $item = array_shift($translatedItem);
                }
            }

            if ($this->helper->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL(
                    'tt_content',
                    $item,
                    $this->helper->getBackendUser()->workspace
                );
            }
            $collectedItems[] = $item;
        }
    }

    /**
     * getter for queryBuilder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        return $queryBuilder;
    }
}
