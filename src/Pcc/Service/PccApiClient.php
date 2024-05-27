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
   * @return PccClient
   *   Returns PccClient.
   */
  public function getPccClient(string $siteId, string $siteToken, bool $refresh = false): PccClient {
    if (!empty($this->pccClientList) && !empty($this->pccClientList[$siteId]) && !$refresh) {
      return $this->pccClientList[$siteId];
    }

    $this->pccClientList[$siteId] = new PccClient(
      new PccClientConfig($siteId, $siteToken)
    );

    return $this->pccClientList[$siteId];
  }
}
