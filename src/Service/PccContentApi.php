<?php

namespace Drupal\pcx_connect\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\pcx_connect\PccApiTrait;
use PccPhpSdk\api\ContentApi;
use PccPhpSdk\Exception\PccClientException;

/**
 * PCC Content API Integration service
 */
class PccContentApi {

  /**
   * PCC API Trait.
   */
  use PccApiTrait;

  /**
   * Logger Channel Interface.
   *
   * @var LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * PCC Content API.
   *
   * @var ContentApi
   */
  protected ContentApi $contentApi;

  /**
   * PccContentApi Constructor.
   *
   * @param LoggerChannelFactory $loggerChannelFactory
   *   Logger Channel Factory.
   */
  public function __construct(LoggerChannelFactory $loggerChannelFactory) {
    $this->logger = $loggerChannelFactory->get('pcx_connect');
  }

  /**
   * Get all articles.
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   *
   * @return mixed
   *   Returns array of Articles in the form of Associative data.
   */
  function getAllArticles(string $siteId, string $siteToken): mixed {
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
   * @return ContentApi
   *   PCC Content API.
   */
  protected function getContentApi(string $siteId, string $siteToken): ContentApi {
    if (empty($this->contentApi)) {
      $this->contentApi = new ContentApi($this->getPccClient($siteId, $siteToken));
    }
    return $this->contentApi;
  }
}
