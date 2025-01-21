<?php

namespace Drupal\Tests\pcx_connect\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface;
use Drupal\pcx_connect\PccSiteInterface;
use Drupal\pcx_connect\Plugin\views\query\PccSiteViewQuery;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\pcx_connect\Plugin\views\query\PccSiteViewQuery
 * @group pcx_connect
 */
class PccSiteViewQueryTest extends UnitTestCase {

  /**
   * The cursor in the request query.
   *
   * The default cursor is time based which is not convenient for testing so
   * set an override instead.
   */
  const CURSOR = 1737328742;

  protected MockObject|PccArticlesApiInterface $pccContentApi;

  protected PccSiteViewQuery $query;

  protected string $pccSiteId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pccContentApi = $this->createMock(PccArticlesApiInterface::class);
    $this->pccSiteId = $this->randomMachineName();

    $request = new Request(['cursor' => self::CURSOR]);
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->query = new PccSiteViewQuery([], '', [], $this->pccContentApi, $requestStack);
    $this->query->view = $this->createMock(ViewExecutable::class);
    $storage = $this->createMock(ViewEntityInterface::class);
    $storage
      ->method('get')
      ->with('base_table')
      ->wilLReturn($this->pccSiteId);
    $this->query->view->storage = $storage;
    $this->query->view->pager = $this->createMock(PagerPluginBase::class);
  }

  /**
   * Data provider for query tests.
   */
  public static function queryDataProvider(): array {
    return [
      'basic_article_query' => [
        'fields' => ['id', 'title'],
        'conditions' => [
          ['field' => 'slug', 'value' => 'test-article'],
        ],
        'expected_query' => <<<GRAPHQL
        article (
          contentType: "TREE_PANTHEON_V2"
          slug: "test-article"
        ) {
          id
          previewActiveUntil
          title
        }
        GRAPHQL,
      ],
      'multiple_fields_query' => [
        'fields' => ['id', 'title', 'body', 'author'],
        'conditions' => [
          ['field' => 'id', 'value' => 123],
        ],
        'expected_query' => <<<GRAPHQL
        article (
          contentType: "TREE_PANTHEON_V2"
          id: 123
        ) {
          id
          previewActiveUntil
          title
          body
          author
        }
        GRAPHQL,
      ],
    ];
  }

  /**
   * Data provider for article retrieval tests.
   */
  public static function articleDataProvider(): array {
    $rand = mt_rand(1000000000, 1700000000);
    return [
      'single_article' => [
        'api_response' => [
          'articles' => [
            [
              'id' => '1',
              'title' => 'Test Article',
              'publishedDate' => $rand * 10000,
              'updatedAt' => $rand * 1000 + 2000,
            ],
          ],
          'cursor' => '123456',
          'total' => 1,
        ],
      ],
      'multiple_articles' => [
        'api_response' => [
          'articles' => [
            [
              'id' => '1',
              'title' => 'First Article',
              'publishedDate' => $rand * 1000 + 3000,
              'updatedAt' => $rand * 1000 + 4000,
            ],
            [
              'id' => '2',
              'title' => 'Second Article',
              'publishedDate' => $rand * 1000 + 5000,
              'updatedAt' => $rand * 1000 + 6000,
            ],
          ],
          'cursor' => '123456',
          'total' => 2,
        ],
      ],
      'empty_response' => [
        'api_response' => [
          'articles' => [],
          'cursor' => '123456',
          'total' => 0,
        ],
      ],
    ];
  }

  /**
   * @dataProvider queryDataProvider
   */
  public function testQueryBuild(array $fields, array $conditions, string $expected_query): void {
    foreach ($fields as $field) {
      $this->query->addField(NULL, $field);
    }

    foreach ($conditions as $condition) {
      $this->query->addWhere(0, $condition['field'], $condition['value']);
    }

    $actual_query = $this->query->query();
    $this->assertEquals($this->normalize($expected_query), $this->normalize($actual_query));
  }

  /**
   * Remove whitespace and insignificant commas from a GraphQL query.
   */
  protected function normalize($query) {
    $query = preg_replace('/\s+|,/', ' ', $query);
    $query = preg_replace('/\s*([({:])\s*/', '\1', $query);
    $query = preg_replace('/\s+([})])/', '\1', $query);
    return $query;
  }

  /**
   * @dataProvider articleDataProvider
   */
  public function testArticleRetrieval(array $api_response): void {
    $siteKey = $this->randomMachineName();
    $siteToken = $this->randomMachineName();
    $this->mockEntityBaseLoad($siteKey, $siteToken);

    $pager = [
      'current_page' => 0,
      'items_per_page' => PccSiteViewQuery::DEFAULTITEMSPERPAGE,
      'filters' => [],
      'cursor' => self::CURSOR,
    ];
    $this->pccContentApi
      ->method('getAllArticles')
      ->with($siteKey, $siteToken, [], $pager)
      ->willReturn($api_response);
    $this->query->execute($this->query->view);

    $articles = $api_response['articles'] ?? [];
    $this->assertCount(count($articles), $this->query->view->result);
    foreach ($articles as $key => $article) {
      $resultRow = $this->query->view->result[$key];
      $this->assertSame($key, $resultRow->index);
      foreach ($article as $k => $v) {
        $this->assertSame(is_int($v) ? intdiv($v, 1000) : $v, $resultRow->$k);
      }
    }
  }

  /**
   * Mock \Drupal\Core\Entity\EntityBase::load().
   */
  public function mockEntityBaseLoad(string $siteKey, string $siteToken): void {
    $entityTypeId = $this->randomMachineName();
    $pccSiteEntity = $this->createMock(PccSiteInterface::class);
    $pccSiteEntity
      ->method('get')
      ->willReturnMap([
        ['site_key', $siteKey],
        ['site_token', $siteToken],
      ]);
    $pccSiteStorage = $this->createMock(EntityStorageInterface::class);
    $pccSiteStorage->expects($this->once())
      ->method('load')
      ->with($this->pccSiteId)
      ->willReturn($pccSiteEntity);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($entityTypeId)
      ->wilLReturn($pccSiteStorage);
    $entityTypeRepository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entityTypeRepository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with(PccSite::class)
      ->willReturn($entityTypeId);
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('entity_type.repository', $entityTypeRepository);
    \Drupal::setContainer($container);
  }

}
