<?php

namespace Drupal\pcx_connect\Pcc\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\pcx_connect\Pcc\Mapper\PccArticlesMapperInterface;
use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\Exception\PccClientException;

/**
 * PCC Content API Integration service.
 */
class PccArticlesApi implements PccArticlesApiInterface {
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
  public function getAllArticles(array $fields, string $siteId, string $siteToken): array {
    $articles = [];
    try {
      $artciles_api = $this->getArticlesApi($siteId, $siteToken);
      $response = $artciles_api->getAllArticles($fields);
      $articles = $this->pccArticlesMapper->toArticlesList($response);
    }
    catch (PccClientException $e) {
      $this->logger->error('Failed to get articles: <pre>' . print_r($e->getMessage(), TRUE) . '</pre>');
    }

    return $articles;
  }

  /**
   * {@inheritDoc}
   */
  public function getArticle(array $fields, string $slug_or_id, string $siteId, string $siteToken, string $type = 'slug'): mixed {
    $api_client = $this->pccApiClient->getPccClient($siteId, $siteToken);
    $article_api = new ArticlesApi($api_client);
    $article = [];
    if ($type == 'slug') {
      $article = $article_api->getArticleBySlug($fields, $slug_or_id);
    }
    else {
      $article = $article_api->getArticleById($fields, $slug_or_id);
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
