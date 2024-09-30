<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\EventListener;

/***************************************************************
 *  Copyright notice
 *  (c) 2021-2023 Ephraim Härer <mail@ephra.im>
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AfterTcaCompilationEventListener
{
    /**
     * @param AfterTcaCompilationEvent $event
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        /** @var array<non-empty-string, string|int|float|bool|null> $extConf */
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('paste_reference') ?? [];
        if (isset($extConf['enableExtendedShortcutPreviewRenderer']) && (int)$extConf['enableExtendedShortcutPreviewRenderer'] === 1) {
            $tca = $event->getTca();
            $tca['tt_content']['types']['shortcut']['previewRenderer'] = ShortcutPreviewRenderer::class;
            $event->setTca($tca);
        }
    }
}
