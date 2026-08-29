<?php

declare(strict_types=1);

/*
 * This file is part of the package test/sitepackage.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Test\Sitepackage\Controller;

use Psr\Http\Message\ResponseInterface;
use Test\Sitepackage\Domain\Repository\IrreRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class IrreController extends ActionController
{
    public function __construct(
        private readonly IrreRepository $irreRepository
    ) {}

    public function indexAction(): ResponseInterface
    {
        // $pageId = $this->request->getAttributes()['routing']->getPageId();
        $cObj = $this->request->getAttribute('currentContentObject');
        $data = $cObj->data; // false positive of #101995 in Extension Scanner
        $currentRecord = $this->irreRepository->findByUid((int)$data['uid']);
        $childRecords = $this->irreRepository->getChildRecords((int)$data['uid']);
        $this->view->assign('data', $data);
        $this->view->assign('childRecords', $childRecords);
        return $this->htmlResponse();
    }

    public function showAction(): ResponseInterface
    {
        // $pageId = $this->request->getAttributes()['routing']->getPageId();
        $cObj = $this->request->getAttribute('currentContentObject');
        $data = $cObj->data; // false positive of #101995 in Extension Scanner
        $currentRecord = $this->irreRepository->findByUid((int)$data['uid']);
        $childRecords = $this->irreRepository->getChildRecords((int)$data['uid']);
        $this->view->assign('data', $data);
        $this->view->assign('childRecords', $childRecords);
        return $this->htmlResponse();
    }

    public function listAction(): ResponseInterface
    {
        // $pageId = $this->request->getAttributes()['routing']->getPageId();
        $cObj = $this->request->getAttribute('currentContentObject');
        $data = $cObj->data; // false positive of #101995 in Extension Scanner
        $currentRecord = $this->irreRepository->findByUid((int)$data['uid']);
        $childRecords = $this->irreRepository->getChildRecords((int)$data['uid']);
        $this->view->assign('data', $data);
        $this->view->assign('childRecords', $childRecords);
        return $this->htmlResponse();
    }
}
