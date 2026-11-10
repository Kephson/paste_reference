<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-test-sitepackage-plugin' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_sitepackage/Resources/Public/Icons/Plugin.svg',
    ],
    'tx-testsitepacke-parent-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_sitepackage/Resources/Public/Icons/tx_testsitepacke_parent.svg',
    ],
];
