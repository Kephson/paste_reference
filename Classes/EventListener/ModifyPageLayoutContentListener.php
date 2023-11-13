<?php
declare(strict_types=1);


namespace EHAERER\PasteReference\EventListener;

use EHAERER\PasteReference\Hooks\PageLayoutController;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ModifyPageLayoutContentListener
{

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        // Get the current page ID
        $id = (int)($event->getRequest()->getQueryParams()['id'] ?? 0);

        $pageLayoutController = GeneralUtility::makeInstance(PageLayoutController::class);
        $event->addHeaderContent($pageLayoutController->drawHeaderHook());

        $event->addHeaderContent('Additional header content');

        $event->setFooterContent('Overwrite footer content');
    }

}
