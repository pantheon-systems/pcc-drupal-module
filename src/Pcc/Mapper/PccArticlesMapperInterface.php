<?php

namespace Drupal\pcx_connect\Pcc\Mapper;

use PccPhpSdk\api\Response\Article;
use PccPhpSdk\api\Response\PaginatedArticles;

interface PccArticlesMapperInterface {

  function toArticleData(Article $article): array;

  function toArticlesList(PaginatedArticles $paginatedArticles): array;

}
