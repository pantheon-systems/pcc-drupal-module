<?php

namespace Drupal\pcx_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\api\Query\Enums\ArticleSortField;
use PccPhpSdk\api\Query\Enums\ArticleSortOrder;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishStatus;
use PccPhpSdk\api\SitesApi;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Debug Site Controller.
 */
class DebugSiteController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The Pcc Client.
   *
   * @var \PccPhpSdk\core\PccClient
   */
  protected PccClient $pccClient;

  /**
   * Constructs an UpdateRootFactory instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
    $this->pccClient = new PccClient(
      new PccClientConfig(
        $this->getSiteID(),
        $this->getSiteToken()
      )
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Get site info.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse with site info from Pcc.
   */
  public function getSite(): JsonResponse {
    $siteId = $this->getSiteID();
    $contentApi = new SitesApi($this->pccClient);
    $content = $contentApi->getSite($siteId);

    return new JsonResponse(
      $content,
      200,
      [],
      TRUE
    );
  }

  /**
   * Get all articles.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse with all articles.
   */
  public function getAllPccArticles(): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getAllArticles();
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      TRUE
    );
  }

  /**
   * Get article by id.
   *
   * @param string $id
   *   Article ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response containing the article.
   */
  public function getArticleById(string $id): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getArticleById($id);
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      TRUE
    );
  }

  /**
   * Get article by slug.
   *
   * @param string $slug
   *   Article Slug.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response containing the article.
   */
  public function getArticleBySlug(string $slug): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getArticleBySlug($slug);
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      TRUE
    );
  }

  /**
   * Search Articles.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response containing the articles.
   */
  public function getAllArticles(): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getAllArticles(
      new ArticleQueryArgs(
        ArticleSortField::from($this->getQueryArg('sortField') ?? 'updatedAt'),
        ArticleSortOrder::from($this->getQueryArg('sortOrder') ?? 'DESC'),
        $this->getQueryArg('pageSize') ?? 20,
        $this->getQueryArg('pageIndex') ?? 1,
        ContentType::from($this->getQueryArg('contentType') ?? 'TREE_PANTHEON_V2')
      ),
      new ArticleSearchArgs(
      $this->getQueryArg('bodyContains') ?? '',
      $this->getQueryArg('tagContains') ?? '',
      $this->getQueryArg('titleContains') ?? '',
      $this->getQueryArg('published') ? PublishStatus::PUBLISHED : PublishStatus::UNPUBLISHED
    ));
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      TRUE
    );
  }

  /**
   * Get Site ID from query.
   *
   * @return string|null
   *   Site ID.
   */
  private function getSiteID(): ?string {
    return $this->requestStack->getCurrentRequest()->query->get('site-id');
  }

  /**
   * Get Site Token from query.
   *
   * @return string|null
   *   Site Token.
   */
  private function getSiteToken(): ?string {
    return $this->requestStack->getCurrentRequest()->query->get('site-token');
  }

  /**
   *
   */
  private function getQueryArg(string $name): ?string {
    return $this->requestStack->getCurrentRequest()->query->get($name);
  }

}
