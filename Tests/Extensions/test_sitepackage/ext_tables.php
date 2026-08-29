<?php

defined('TYPO3') or die();

// Add backend layouts
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'test_sitepackage',
    'Configuration/TypoScript',
    'Test Sitepackage'
);
