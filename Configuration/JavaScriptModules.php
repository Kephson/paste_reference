<?php

return [
    /** @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/Backend/JavaScript/ES6/Index.html */
    'dependencies' => ['core', 'backend'],
    'tags' => [
        'backend.contextmenu',
    ],
    'imports' => [
        'jquery-ui/' => 'EXT:core/Resources/Public/JavaScript/Contrib/jquery-ui/',
        '@ehaerer/paste-reference/' => 'EXT:paste_reference/Resources/Public/JavaScript/',
    ],
];
