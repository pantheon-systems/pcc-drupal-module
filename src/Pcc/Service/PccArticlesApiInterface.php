<?php

namespace Drupal\pcx_connect\Pcc\Service;

/**
 * The PCC article api interface.
 */
interface PccArticlesApiInterface {

  /**
   * Get all articles.
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   * @param array $fields
   *   The API fields.
   * @param array $pager
   *   The pager options.
   *
   * @return mixed
   *   Returns array of Articles in the form of Associative data.
   */
  public function searchArticles(string $siteId, string $siteToken, array $fields = [], array $pager = []): array;

  /**
   * Get all articles.
   *
   * @param string $slug_or_id
   *   Content slug or ID.
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   * @param string $type
   *   The filter type.
   * @param array $fields
   *   The API fields.
   *
   * @return mixed
   *   Returns array of Articles in the form of Associative data.
   */
  public function getArticle(string $slug_or_id, string $siteId, string $siteToken, string $type, array $fields = []): mixed;

}
