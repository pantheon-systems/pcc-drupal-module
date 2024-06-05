<?php

namespace Drupal\pcx_connect\Pcc\Mapper;

use PccPhpSdk\api\Response\Article;
use PccPhpSdk\api\Response\PaginatedArticles;

/**
 *
 */
class PccArticlesMapper implements PccArticlesMapperInterface {

  /**
   *
   */
  public function toArticleData(Article $article): array {
    return (array) $article;
  }

  /**
   *
   */
  public function toArticlesList(PaginatedArticles $paginatedArticles): array {
    $list = [];
    foreach ($paginatedArticles->articles as $article) {
      $list[] = $this->toArticleData($article);
    }
    return $list;
  }

}
