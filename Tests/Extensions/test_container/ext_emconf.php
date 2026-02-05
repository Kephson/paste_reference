<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Test Container Extension',
    'description' => 'Minimal container extension for testing paste-reference functionality',
    'category' => 'misc',
    'author' => 'Test Author',
    'author_email' => 'test@example.com',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-14.99.99',
            'container' => '3.0.0-3.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];