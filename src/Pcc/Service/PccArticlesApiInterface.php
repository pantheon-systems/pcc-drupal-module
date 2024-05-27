<?php

namespace Drupal\pcx_connect\Pcc\Service;

interface PccArticlesApiInterface {

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
  public function getAllArticles(string $siteId, string $siteToken): array;

}
