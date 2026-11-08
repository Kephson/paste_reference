<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Exception\InvalidSchemaTypeException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Provides the data of a content element selected in the element browser,
 * which reports only its uid and title.
 */
#[AsController]
final readonly class RecordInfoController
{
    private const TABLE = 'tt_content';

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    )
    {
    }

    /**
     * @throws UndefinedSchemaException
     * @throws InvalidSchemaTypeException
     */
    public function getRecordInfoAction(ServerRequestInterface $request): ResponseInterface
    {
        $uid = (int)($request->getParsedBody()['uid'] ?? $request->getQueryParams()['uid'] ?? 0);
        $record = BackendUtility::getRecordWSOL(self::TABLE, $uid);
        if ($record === null) {
            return new JsonResponse([], 404);
        }
        $backendUser = $this->getBackendUser();
        $pageAccess = BackendUtility::readPageAccess((int)$record['pid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        if ($pageAccess === false || !$backendUser->check('tables_select', self::TABLE)) {
            return new JsonResponse([], 403);
        }

        $schema = $this->tcaSchemaFactory->get(self::TABLE);
        $typeField = $schema->getSubSchemaTypeInformation()->getFieldName();
        $languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        return new JsonResponse([
            'uid' => (int)$record['uid'],
            'CType' => (string)$record[$typeField],
            'sys_language_uid' => (int)$record[$languageField],
            'title' => (string)BackendUtility::getRecordTitle(self::TABLE, $record, false, false),
        ]);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
