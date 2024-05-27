<?php

namespace Drupal\pcx_connect\Pcc\Entity;

class PccSiteArticle {

  /**
   * Article ID.
   *
   * @var string $id
   */
  public string $id;

  /**
   * Article Slug.
   *
   * @var string $slug
   */
  public string $slug;

  /**
   * Article Title.
   *
   * @var string $title
   */
  public string $title;

  /**
   * Site ID.
   *
   * @var string $siteId
   */
  public string $siteId;

  /**
   * Article Content.
   *
   * @var string $content
   */
  public string $content;

  /**
   * Article snippet.
   *
   * @var string $snippet
   */
  public string $snippet;

  /**
   * Article Tags.
   *
   * @todo Redefine exact type of Tags, id or string.
   *
   * @var array $tags
   */
  public array $tags;

  /**
   * Published Date.
   *
   * @var string $publishedDate
   */
  public string $publishedDate;

  /**
   * Updated Date.
   *
   * @var string $updatedAt
   */
  public string $updatedAt;

  public static function getDefaultFields() {
    return [
      'id',
      'slug',
      'title',
      'siteId',
      'content',
      'snippet',
      'tags',
      'publishedDate',
      'updatedAt',
    ];
  }

}
