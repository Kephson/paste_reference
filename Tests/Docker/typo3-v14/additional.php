<?php
/**
 * Additional configuration for TYPO3 v14 test environment
 */

// Enable development mode
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED);

// Configure logging for testing
$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['Core']['Resource']['ResourceStorage']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'var/log/typo3_test.log'
        ],
    ],
];

// Disable caching for testing
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['hash']['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pagesection']['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;

// Configure test database
if (getenv('TYPO3_TEST_DB_HOST')) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = getenv('TYPO3_TEST_DB_HOST');
}
if (getenv('TYPO3_TEST_DB_NAME')) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = getenv('TYPO3_TEST_DB_NAME');
}
if (getenv('TYPO3_TEST_DB_USER')) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] = getenv('TYPO3_TEST_DB_USER');
}
if (getenv('TYPO3_TEST_DB_PASSWORD')) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] = getenv('TYPO3_TEST_DB_PASSWORD');
}

// Enable paste-reference extension for testing
$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['paste_reference'] = serialize([
    'enableDebug' => '1',
    'enableLogging' => '1',
]);

// TYPO3 v14 specific configurations
// Updated log file paths for v14 structure
$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::NOTICE => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'var/log/deprecations.log'
        ],
    ],
];