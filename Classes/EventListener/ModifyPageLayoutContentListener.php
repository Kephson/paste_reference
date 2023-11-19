<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\EventListener;

use EHAERER\PasteReference\Hooks\PageLayoutController;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ModifyPageLayoutContentListener
{
    /**
     * @param ModifyPageLayoutContentEvent $event
     * @return void
     */
    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $request = $event->getRequest();
        /** @var PageLayoutController $pageLayoutController */
        $pageLayoutController = GeneralUtility::makeInstance(PageLayoutController::class);
        $event->addHeaderContent($pageLayoutController->pasteReferenceModification($request));
    }
}
