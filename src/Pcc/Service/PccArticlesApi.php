<?php

namespace Drupal\pcx_connect\Pcc\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\pcx_connect\Pcc\Mapper\PccArticlesMapperInterface;
use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\api\Query\Enums\ArticleSortField;
use PccPhpSdk\api\Query\Enums\ArticleSortOrder;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishStatus;
use PccPhpSdk\Exception\PccClientException;

/**
 * PCC Content API Integration service.
 */
class PccArticlesApi implements PccArticlesApiInterface {
  /**
   * Current page cursor.
   *
   * @var int
   */
  public static $cursor = 0;

  /**
   * Pcc API Client.
   *
   * @var PccApiClient
   */
  protected PccApiClient $pccApiClient;

  /**
   * PCC Articles Mapper.
   *
   * @var \Drupal\pcx_connect\Pcc\Mapper\PccArticlesMapperInterface
   */
  protected PccArticlesMapperInterface $pccArticlesMapper;

  /**
   * Logger Channel Interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * PCC Content API.
   *
   * @var \PccPhpSdk\api\ArticlesApi
   */
  protected ArticlesApi $articlesApi;

  /**
   * PccContentApi Constructor.
   *
   * @param PccApiClient $pccApiClient
   *   The pcc api client.
   * @param \\Drupal\pcx_connect\Pcc\Mapper\PccArticlesMapperInterface $pccArticlesMapper
   *   The PCC article mapper.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger Channel Factory.
   */
  public function __construct(PccApiClient $pccApiClient, PccArticlesMapperInterface $pccArticlesMapper, LoggerChannelFactory $loggerChannelFactory) {
    $this->logger = $loggerChannelFactory->get('pcx_connect');
    $this->pccApiClient = $pccApiClient;
    $this->pccArticlesMapper = $pccArticlesMapper;
  }

  /**
   * {@inheritDoc}
   */
  public function getArticles(string $siteId, string $siteToken, array $fields = [], array $pager = []): array {
    $articles = [];
    $microtime = microtime(TRUE);
    // Convert to milliseconds.
    self::$cursor = round($microtime * 1000);
    try {

      $articles_api = $this->getArticlesApi($siteId, $siteToken);
      $queryArgs = new ArticleQueryArgs(
        ArticleSortField::UPDATED_AT,
        ArticleSortOrder::DESC,
        $pager['items_per_page'],
        self::$cursor,
        ContentType::TEXT_MARKDOWN
      );
      $searchArgs = NULL;
      if ($pager['filters']) {
        $filters = $pager['filters'];
        $searchArgs = new ArticleSearchArgs(
          '',
          '',
          '',
          PublishStatus::PUBLISHED
        );

        if (isset($filters['title'])) {
          $searchArgs->setTitleContains($filters['title']);
        }
        if (isset($filters['content'])) {
          $searchArgs->setBodyContains($filters['content']);
        }
        if (isset($filters['tags'])) {
          $searchArgs->setTagContains($filters['tags']);
        }
      }

      $response = $articles_api->getAllArticles($queryArgs, $searchArgs, $fields);
      $articles['articles'] = $this->pccArticlesMapper->toArticlesList($response);
      $articles['total'] = $response->total;
      self::$cursor = $response->cursor;
    }

    catch (PccClientException $e) {
      $this->logger->error('Failed to get articles: <pre>' . print_r($e->getMessage(), TRUE) . '</pre>');
    }
    return $articles;
  }

  /**
   * {@inheritDoc}
   */
  public function getArticle(string $slug_or_id, string $siteId, string $siteToken, string $type, array $fields = []): mixed {
    $article = [];
    try {
      $articles_api = $this->getArticlesApi($siteId, $siteToken);
      if ($type == 'slug') {
        $article = $articles_api->getArticleBySlug($slug_or_id, $fields);
      }
      else {
        $article = $articles_api->getArticleById($slug_or_id, $fields);
      }
    }
    catch (PccClientException $e) {
      $this->logger->error('Failed to get article: <pre>' . print_r($e->getMessage(), TRUE) . '</pre>');
    }
    return $article;
  }

  /**
   * Get Content API.
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   *
   * @return \PccPhpSdk\api\ArticlesApi
   *   PCC Content API.
   */
  protected function getArticlesApi(string $siteId, string $siteToken): ArticlesApi {
    if (empty($this->articlesApi)) {
      $api_client = $this->pccApiClient->getPccClient($siteId, $siteToken);
      $this->articlesApi = new ArticlesApi($api_client);

    }
    return $this->articlesApi;
  }

}
