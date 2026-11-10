<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Tests\Functional;

use EHAERER\PasteReference\Controller\RecordInfoController;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A reference is the datamap sent by Resources/Public/JavaScript/command.js.
 */
final class ReferenceDatamapTest extends FunctionalTestCase
{
    private const PAGE_DEFAULT = 1;
    private const PAGE_ALLOWS_SHORTCUT = 2;
    private const PAGE_DENIES_SHORTCUT = 3;
    private const CONTENT_FIRST = 1;
    private const CONTENT_SECOND = 2;
    private const CONTENT_CONTAINER = 3;
    private const CONTENT_ALL_LANGUAGES = 4;

    protected array $testExtensionsToLoad = [
        'ehaerer/paste-reference',
        'b13/container',
        'typo3conf/ext/paste_reference/Tests/Extensions/test_container',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $pool = $this->get(ConnectionPool::class);
        $pool->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 1,
            'username' => 'admin',
            'password' => 'unused',
            'admin' => 1,
        ]);
        $pool->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 2,
            'username' => 'editor',
            'password' => 'unused',
            'admin' => 0,
            'usergroup' => '1',
        ]);
        $pool->getConnectionForTable('be_groups')->insert('be_groups', [
            'uid' => 1,
            'title' => 'Editors',
            'tables_select' => 'tt_content',
        ]);
        $pages = $pool->getConnectionForTable('pages');
        $pages->insert('pages', ['uid' => self::PAGE_DEFAULT, 'pid' => 0, 'title' => 'Default', 'slug' => '/', 'doktype' => 1, 'is_siteroot' => 1]);
        $this->insertRestrictedPage(self::PAGE_ALLOWS_SHORTCUT, 'allowedContentTypes = shortcut');
        $this->insertRestrictedPage(self::PAGE_DENIES_SHORTCUT, 'disallowedContentTypes = shortcut');

        $content = $pool->getConnectionForTable('tt_content');
        $content->insert('tt_content', ['uid' => self::CONTENT_FIRST, 'pid' => self::PAGE_DEFAULT, 'CType' => 'text', 'header' => 'First', 'colPos' => 0, 'sorting' => 256]);
        $content->insert('tt_content', ['uid' => self::CONTENT_SECOND, 'pid' => self::PAGE_DEFAULT, 'CType' => 'text', 'header' => 'Second', 'colPos' => 0, 'sorting' => 512]);
        $content->insert('tt_content', ['uid' => self::CONTENT_CONTAINER, 'pid' => self::PAGE_DEFAULT, 'CType' => 'test_container_2col', 'header' => 'Container', 'colPos' => 0, 'sorting' => 768]);
        $content->insert('tt_content', ['uid' => self::CONTENT_ALL_LANGUAGES, 'pid' => self::PAGE_DEFAULT, 'CType' => 'text', 'header' => 'All languages', 'colPos' => 0, 'sorting' => 1024, 'sys_language_uid' => -1]);

        $this->get(SiteWriter::class)->write('test', [
            'rootPageId' => self::PAGE_DEFAULT,
            'base' => '/',
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => '/'],
                ['languageId' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF-8', 'base' => '/de/'],
            ],
        ]);

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    private function insertRestrictedPage(int $uid, string $restriction): void
    {
        $tsConfig = "mod.web_layout.BackendLayouts.restricted {\n"
            . "  title = Restricted\n"
            . "  config.backend_layout {\n"
            . "    colCount = 1\n"
            . "    rowCount = 1\n"
            . "    rows.1.columns.1 {\n"
            . "      name = Main\n"
            . "      colPos = 0\n"
            . '      ' . $restriction . "\n"
            . "    }\n"
            . "  }\n"
            . "}\n";
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->insert('pages', [
            'uid' => $uid,
            'pid' => self::PAGE_DEFAULT,
            'title' => 'Restricted ' . $uid,
            'slug' => '/restricted-' . $uid,
            'doktype' => 1,
            'TSconfig' => $tsConfig,
            'backend_layout' => 'pagets__restricted',
        ]);
    }

    /**
     * @param array<string, int|string> $fields
     */
    private function createReference(int|string $pid, array $fields = []): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tt_content' => ['NEW1' => array_merge([
            'pid' => $pid,
            'CType' => 'shortcut',
            'records' => 'tt_content_' . self::CONTENT_FIRST,
            'colPos' => 0,
            'sys_language_uid' => 0,
            'header' => 'Reference: First',
        ], $fields)]], []);
        $dataHandler->process_datamap();
        return $dataHandler;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCreatedRecord(DataHandler $dataHandler): array
    {
        self::assertSame([], $dataHandler->errorLog);
        $uid = (int)($dataHandler->substNEWwithIDs['NEW1'] ?? 0);
        self::assertGreaterThan(0, $uid);
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $record = $queryBuilder->select('*')->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()->fetchAssociative();
        self::assertIsArray($record);
        return $record;
    }

    private function getSorting(int $uid): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        return (int)$queryBuilder->select('sorting')->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()->fetchOne();
    }

    #[Test]
    public function referenceIsCreatedAtStartOfPageColumn(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_DEFAULT));

        self::assertSame('shortcut', $record['CType']);
        // The core normalizes single-table relations to the plain uid
        self::assertSame((string)self::CONTENT_FIRST, $record['records']);
        self::assertSame('Reference: First', $record['header']);
        self::assertSame(self::PAGE_DEFAULT, (int)$record['pid']);
        self::assertLessThan($this->getSorting(self::CONTENT_FIRST), (int)$record['sorting']);
    }

    #[Test]
    public function referenceIsCreatedInGivenColumn(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_DEFAULT, ['colPos' => 3]));

        self::assertSame(3, (int)$record['colPos']);
    }

    #[Test]
    public function referenceIsCreatedAfterElement(): void
    {
        $record = $this->getCreatedRecord($this->createReference(-self::CONTENT_FIRST));

        self::assertSame(self::PAGE_DEFAULT, (int)$record['pid']);
        self::assertGreaterThan($this->getSorting(self::CONTENT_FIRST), (int)$record['sorting']);
        self::assertLessThan($this->getSorting(self::CONTENT_SECOND), (int)$record['sorting']);
    }

    #[Test]
    public function referenceKeepsExplicitLanguage(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_DEFAULT, ['sys_language_uid' => 1]));

        self::assertSame(1, (int)$record['sys_language_uid']);
    }

    #[Test]
    public function referenceToAllLanguagesElementStaysAllLanguagesInDefaultLanguage(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_DEFAULT, [
            'records' => 'tt_content_' . self::CONTENT_ALL_LANGUAGES,
            'sys_language_uid' => -1,
        ]));

        self::assertSame(-1, (int)$record['sys_language_uid']);
    }

    #[Test]
    public function referenceToAllLanguagesElementTakesTranslationLanguage(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_DEFAULT, [
            'records' => 'tt_content_' . self::CONTENT_ALL_LANGUAGES,
            'sys_language_uid' => 1,
        ]));

        self::assertSame(1, (int)$record['sys_language_uid']);
    }

    private function requestRecordInfo(int $uid): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequest('https://example.com/typo3/'))->withQueryParams(['uid' => (string)$uid]);
        return $this->get(RecordInfoController::class)->getRecordInfoAction($request);
    }

    #[Test]
    public function recordInfoIsReturnedForAdmin(): void
    {
        $response = $this->requestRecordInfo(self::CONTENT_ALL_LANGUAGES);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'uid' => self::CONTENT_ALL_LANGUAGES,
            'CType' => 'text',
            'sys_language_uid' => -1,
            'title' => 'All languages',
        ], json_decode((string)$response->getBody(), true));
    }

    #[Test]
    public function recordInfoOfUnknownRecordIsNotFound(): void
    {
        $response = $this->requestRecordInfo(999);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame([], json_decode((string)$response->getBody(), true));
    }

    #[Test]
    public function recordInfoIsDeniedWithoutPageAccess(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        self::assertTrue($backendUser->check('tables_select', 'tt_content'));

        $response = $this->requestRecordInfo(self::CONTENT_ALL_LANGUAGES);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame([], json_decode((string)$response->getBody(), true));
    }

    #[Test]
    public function referenceIsCreatedInContainer(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_DEFAULT, [
            'colPos' => 101,
            'tx_container_parent' => self::CONTENT_CONTAINER,
        ]));

        self::assertSame(101, (int)$record['colPos']);
        self::assertSame(self::CONTENT_CONTAINER, (int)$record['tx_container_parent']);
    }

    #[Test]
    public function referenceIsCreatedInColumnAllowingShortcut(): void
    {
        $record = $this->getCreatedRecord($this->createReference(self::PAGE_ALLOWS_SHORTCUT));

        self::assertSame(self::PAGE_ALLOWS_SHORTCUT, (int)$record['pid']);
    }

    #[Test]
    public function referenceIsRejectedInColumnDenyingShortcut(): void
    {
        $dataHandler = $this->createReference(self::PAGE_DENIES_SHORTCUT);

        self::assertArrayNotHasKey('NEW1', $dataHandler->substNEWwithIDs);
    }
}
