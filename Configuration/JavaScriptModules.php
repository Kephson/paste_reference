<?php

return [
    /** @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/Backend/JavaScript/ES6/Index.html */
    'dependencies' => ['core', 'backend'],
    'tags' => [
        'backend.contextmenu',
    ],
    'imports' => [
        '@ehaerer/paste-reference/' => 'EXT:paste_reference/Resources/Public/JavaScript/',
    ],
];
