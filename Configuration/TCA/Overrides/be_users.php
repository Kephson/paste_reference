<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// @see #108843
ExtensionManagementUtility::addUserSetting(
    'disableCopyFromPageButton',
    [
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ],
);
