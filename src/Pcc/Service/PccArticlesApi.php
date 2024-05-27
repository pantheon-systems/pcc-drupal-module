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
   * @param \Drupal\pcx_connect\Pcc\Service\PccApiClient $pccApiClient
   *   The PCC Api Client.
   * @param \Drupal\pcx_connect\Pcc\Mapper\PccArticlesMapperInterface $pccArticlesMapper
   *   The PCC articles manager.
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
  public function getAllArticles(string $siteId, string $siteToken): array {
    $articles = [];
    try {
      $response = $this->getArticlesApi($siteId, $siteToken)->getAllArticles();
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
  public function getArticle(string $slug_or_id, string $siteId, string $siteToken, string $type = 'slug'): mixed {
    $articles = [];
    try {
      $article = $this->getArticlesApi($siteId, $siteToken);
      $response = NULL;
      if ($type === 'slug') {
        $response = $article->getArticleBySlug($slug_or_id);
      }
      else {
        $response = $article->getArticleById($slug_or_id);
      }
      $articles = $this->pccArticlesMapper->toArticleData($response);
    }
    catch (PccClientException $e) {
      $this->logger->error('Failed to get articles: <pre>' . print_r($e->getMessage(), TRUE) . '</pre>');
    }

    return $articles;
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
      $this->articlesApi = new ArticlesApi($this->pccApiClient->getPccClient($siteId, $siteToken));
    }
    return $this->articlesApi;
  }

}
