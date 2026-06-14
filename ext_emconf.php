<?php /** @noinspection PhpUndefinedVariableInspection */

/*
 * This file is part of the package ehaerer/paste-reference.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Paste reference for content elements',
    'description' => 'Paste reference instead of copy for content elements',
    'category' => 'plugin',
    'version' => '4.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.3.99',
        ],
        'conflicts' => [
            'gridelements' => '*',
        ],
        'suggests' => [
            'container' => '3.2.0-3.2.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'EHAERER\\PasteReference\\' => 'Classes'
        ],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Ephraim Härer',
    'author_email' => 'mail@ephra.im',
    'author_company' => 'private',
];
