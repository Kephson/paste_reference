<?php

defined('TYPO3') || die();

// @see #108843
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserSetting(
    'disableCopyFromPageButton',
    [
        'label' => 'LLL:EXT:paste_reference/Resources/Private/Language/locallang.xlf:disableCopyFromPageButton',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ],
    // 'after:somOtherSetting'
);
