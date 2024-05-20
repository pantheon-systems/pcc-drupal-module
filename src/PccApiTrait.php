<?php

namespace Drupal\pcx_connect;

use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;

/**
 * PCC API Trait consists of PCC Client.
 */
trait PccApiTrait {

  /**
   * PCC Client.
   *
   * @var PccClient
   */
  protected PccClient $pccClient;

  /**
   * Get PCC Client.
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   *
   * @return PccClient
   *   Returns PccClient.
   */
  protected function getPccClient(string $siteId, string $siteToken): PccClient {
    if (empty($this->pccClient)) {
      $this->pccClient = new PccClient(
        new PccClientConfig(
          $siteId,
          $siteToken
        )
      );
    }

    return $this->pccClient;
  }
}
