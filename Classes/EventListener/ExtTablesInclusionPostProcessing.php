<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\EventListener;

use EHAERER\PasteReference\PageLayoutView\ShortcutPreviewRenderer;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

class ExtTablesInclusionPostProcessing
{
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $tca['tt_content']['types']['shortcut']['previewRenderer'] = ShortcutPreviewRenderer::class;
        $event->setTca($tca);
    }
}
