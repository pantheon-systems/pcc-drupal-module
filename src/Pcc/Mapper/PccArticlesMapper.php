<?php

namespace Drupal\pcx_connect\Pcc\Mapper;

use PccPhpSdk\api\Response\Article;
use PccPhpSdk\api\Response\PaginatedArticles;

/**
 * PCC Article field mapper.
 */
class PccArticlesMapper implements PccArticlesMapperInterface {

  /**
   * Convert to Article.
   *
   * @param \PccPhpSdk\api\Response\Article $article
   *   The article.
   *
   * @return array
   *   The Article mapping.
   */
  public function toArticleData(Article $article): array {
    return (array) $article;
  }

  /**
   * Convert to article list.
   *
   * @param \PccPhpSdk\api\Response\PaginatedArticles $paginatedArticles
   *   The paginated article.
   *
   * @return array
   *   The Articles list.
   */
  public function toArticlesList(PaginatedArticles $paginatedArticles): array {
    $list = [];
    foreach ($paginatedArticles->articles as $article) {
      $list[] = $this->toArticleData($article);
    }
    return $list;
  }

}
