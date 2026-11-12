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
    'version' => '6.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.8-14.99.99',
        ],
        'conflicts' => [
            'gridelements' => '*',
        ],
        'suggests' => [
            'container' => '3.2.0-4.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'EHAERER\\PasteReference\\' => 'Classes'
        ],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Ephraim Härer, David Bruchmann',
    'author_email' => 'mail@ephra.im, david.bruchmann@gmail.com',
    'author_company' => 'private, WDB Barlian',
];
