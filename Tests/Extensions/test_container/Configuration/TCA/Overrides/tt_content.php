<?php

defined('TYPO3') or die();

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Only register if container extension is loaded
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {

    $containerRegistry = GeneralUtility::makeInstance(Registry::class);

    // Register a simple two-column container for testing
    $containerConfiguration = new ContainerConfiguration(
        'test_container_2col', // CType
        'Test Two Columns', // label
        'Two column container for testing paste-reference functionality', // description
        [
            [
                ['name' => 'Left Column', 'colPos' => 101],
                ['name' => 'Right Column', 'colPos' => 102],
            ],
        ] // grid configuration
    );

    $containerConfiguration->setIcon('EXT:test_container/Resources/Public/Icons/container-2col.svg');
    $containerConfiguration->setSaveAndCloseInNewContentElementWizard(false);

    $containerRegistry->configureContainer($containerConfiguration);

    // Register a nested container for advanced testing
    $nestedContainerConfiguration = new ContainerConfiguration(
        'test_container_nested', // CType
        'Test Nested Container', // label
        'Nested container for testing complex paste-reference scenarios', // description
        [
            [
                ['name' => 'Main Area', 'colPos' => 201],
            ],
            [
                ['name' => 'Sub Left', 'colPos' => 202],
                ['name' => 'Sub Right', 'colPos' => 203],
            ],
        ] // grid configuration
    );

    $nestedContainerConfiguration->setIcon('EXT:test_container/Resources/Public/Icons/container-nested.svg');
    $nestedContainerConfiguration->setSaveAndCloseInNewContentElementWizard(false);

    $containerRegistry->configureContainer($nestedContainerConfiguration);

    // Add containers to content element wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'Test Two Columns',
            'value' => 'test_container_2col',
            'icon' => 'content-container-columns-2',
            'group' => 'container',
            'description' => 'Two column container for testing',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'Test Nested Container',
            'value' => 'test_container_nested',
            'icon' => 'content-container-columns-3',
            'group' => 'container',
            'description' => 'Nested container for testing',
        ]
    );
}
