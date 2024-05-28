<?php

namespace Drupal\pcx_connect\Pcc\Service;

/**
 * The PCC article api interface.
 */
interface PccArticlesApiInterface {

  /**
   * Get all articles.
   *
   * @param array $fields
   *   The API fields.
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   *
   * @return mixed
   *   Returns array of Articles in the form of Associative data.
   */
  public function getAllArticles(array $fields, string $siteId, string $siteToken): array;

  /**
   * Get all articles.
   *
   * @param array $fields
   *   The API fields.
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
  public function getArticle(array $fields, string $slug_or_id, string $siteId, string $siteToken, string $type = 'slug'): mixed;

}
