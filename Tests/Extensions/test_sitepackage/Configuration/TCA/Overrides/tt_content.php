<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// Register container element ce_columns2 for container extension v3.x
$containerRegistry = GeneralUtility::makeInstance(Registry::class);
$containerConfiguration = new ContainerConfiguration(
    // CType
    'ce_columns2',
    // label
    'Two Columns Container',
    // description
    'Two column container for content elements',
    [
        [
            [
                'name' => 'Left Column',
                'colPos' => 101,
            ],
            [
                'name' => 'Right Column',
                'colPos' => 102,
            ],
        ],
    ]
);
$containerConfiguration->setIcon(
    'EXT:test_sitepackage/Resources/Public/Icons/ce_columns2.svg',
);
$containerConfiguration->setSaveAndCloseInNewContentElementWizard(false);
$containerRegistry->configureContainer($containerConfiguration);

// Add the container to the content element wizard
ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'Two Columns Container',
        'value' => 'ce_columns2',
        'icon' => 'content-container-columns-2',
        'group' => 'container',
        'description' => 'Container with two columns for content elements',
    ],
);

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_testsitepacke_parent' => [
            'label' => 'Inline Element parent field',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tx_testsitepacke_kids' => [
            'label' => 'Inline Elements',
            'description' => 'Group several content elements',
            'exclude' => 0,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tt_content',
                'foreign_field' => 'tx_testsitepacke_parent',
                'foreign_table_field' => 'tablenames',
                'appearance' => [
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                ],
            ],
        ],
    ],
);

$tmpConfig = $GLOBALS['TCA']['tt_content']['types']['text'];
$tmpConfigArray = explode(',', $tmpConfig['showitem']);
foreach ($tmpConfigArray as $count => $tmpConfigValue) {
    if (trim($tmpConfigValue) == 'bodytext') {
        $tmpConfigArray[$count] = 'tx_testsitepacke_kids';
    }
}
$GLOBALS['TCA']['tt_content']['types']['testsitepackage_inlinerecordsirre']['showitem'] = implode(',', $tmpConfigArray);

ExtensionUtility::registerPlugin(
    'TestSitepackage',                              // extensionName or extension_key
    'InlineRecordsIrre',                            // pluginName
    'Inline Records (IRRE)',                        // pluginTitle
    'tx-testsitepacke-parent-icon',                 // pluginIcon
    'plugins',                                      // group
    'Simple Content Element with IRRE relations'    // pluginDescription
    // $$flexForm                                   // Either a reference to a flex-form XML file or the XML directly
);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['testsitepackage_inlinerecordsirre'] = 'tx-testsitepacke-parent-icon';
