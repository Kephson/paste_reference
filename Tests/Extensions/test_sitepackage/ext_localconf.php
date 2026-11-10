<?php

declare(strict_types=1);

use Test\Sitepackage\Controller\IrreController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

ExtensionUtility::configurePlugin(
    'TestSitepackage',
    'InlineRecordsIrre',
    [
        IrreController::class => 'index, list, show',
    ],
    [
        IrreController::class => '',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);
