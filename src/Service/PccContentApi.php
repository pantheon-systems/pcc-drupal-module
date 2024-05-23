<?php

namespace Drupal\pcx_connect\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\Exception\PccClientException;

/**
 * PCC Content API Integration service
 */
class PccContentApi implements PccContentApiInterface {

  /**
   * Pcc API Client.
   *
   * @var PccApiClient
   */
  protected PccApiClient $pccApiClient;

  /**
   * Logger Channel Interface.
   *
   * @var LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * PCC Content API.
   *
   * @var \PccPhpSdk\api\ArticlesApi
   */
  protected ArticlesApi $contentApi;

  /**
   * PccContentApi Constructor.
   *
   * @param LoggerChannelFactory $loggerChannelFactory
   *   Logger Channel Factory.
   */
  public function __construct(PccApiClient $pccApiClient, LoggerChannelFactory $loggerChannelFactory) {
    $this->logger = $loggerChannelFactory->get('pcx_connect');
    $this->pccApiClient = $pccApiClient;
  }

  /**
   * {@inheritDoc}
   */
  public function getAllArticles(string $siteId, string $siteToken): mixed {
    $articles = [];
    try {
      $response = $this->getContentApi($siteId, $siteToken)->getAllArticles();
      $articles = json_decode($response, TRUE);
    } catch (PccClientException $e) {
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
  protected function getContentApi(string $siteId, string $siteToken): ArticlesApi {
    if (empty($this->contentApi)) {
      $this->contentApi = new ArticlesApi($this->pccApiClient->getPccClient($siteId, $siteToken));
    }
    return $this->contentApi;
  }
}
