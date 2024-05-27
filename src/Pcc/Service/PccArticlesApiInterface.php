<?php

namespace Drupal\pcx_connect\Pcc\Service;

/**
 * PCC Article API Interface.
 */
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

  /**
   * Get article by slug or id.
   *
   * @param string $slug_or_id
   *   Content slug or ID.
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   * @param string $type
   *   The filter type.
   *
   * @return mixed
   *   Returns array of Articles in the form of Associative data.
   */
  public function getArticle(string $slug_or_id, string $siteId, string $siteToken, string $type = 'slug'): mixed;

}
