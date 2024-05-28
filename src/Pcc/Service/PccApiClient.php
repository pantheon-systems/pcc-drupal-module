<?php

namespace Drupal\pcx_connect\Pcc\Service;

use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;

/**
 * PCC API Client Service.
 */
class PccApiClient {

  /**
   * Associative list of siteId to PccClient.
   *
   * @var array<string, PccClient>
   */
  private array $pccClientList;

  /**
   * Get PCC Client.
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   * @param bool $refresh
   *   Refresh Flag to get new PccClient.
   *
   * @return \PccPhpSdk\core\PccClient
   *   Returns PccClient.
   */
  public function getPccClient(string $siteId, string $siteToken, bool $refresh = FALSE): PccClient {
    if (!empty($this->pccClientList) && !empty($this->pccClientList[$siteId]) && !$refresh) {
      return $this->pccClientList[$siteId];
    }
    $client_config = new PccClientConfig($siteId, $siteToken);
    $this->pccClientList[$siteId] = new PccClient($client_config);

    return $this->pccClientList[$siteId];
  }

}
