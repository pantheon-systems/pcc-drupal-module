<?php

namespace Drupal\pcx_connect\Pcc\Service;

use PccPhpSdk\api\Query\Enums\PublishingLevel;

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
  public function getAllArticles(
    string $siteId,
    string $siteToken,
    array $fields = [],
    array $pager = [],
  ): array;

  /**
   * Get an article by slug or ID.
   *
   * This method retrieves an article based on the provided slug or ID.
   * It allows specifying the publishing level to fetch articles according
   * to their publishing state.
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
   * @param \PccPhpSdk\api\Query\Enums\PublishingLevel $publishingLevel
   *   The publishing level of the article. Defaults to
   *   PublishingLevel::PRODUCTION if not specified.
   *
   * @return mixed
   *   Returns an article in the form of associative data.
   */
  public function getArticle(
    string $slug_or_id,
    string $siteId,
    string $siteToken,
    string $type,
    array $fields = [],
    PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
  ): mixed;

  /**
   * Get site data based on site id and site token.
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   *
   * @return mixed
   *   Returns an site data.
   */
  public function getPccSiteData(string $siteId, string $siteToken): mixed;

}
