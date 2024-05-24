<?php

namespace Drupal\pcx_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\api\SitesApi;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Debug Site Controller.
 */
class DebugSiteController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The Pcc Client.
   *
   * @var PccClient
   */
  protected PccClient $pccClient;

  /**
   * Constructs an UpdateRootFactory instance.
   *
   * @param RequestStack $requestStack
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
   * @param ContainerInterface $container
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
   * @return JsonResponse
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
      true
    );
  }

  /**
   * Get all articles.
   *
   * @return JsonResponse
   *   JsonResponse with all articles.
   */
  public function getAllArticles(): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getAllArticles();
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      true
    );
  }

  public function getArticleById(string $id): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getArticleById($id);
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      true
    );
  }

  public function getArticleBySlug(string $slug): JsonResponse {
    $contentApi = new ArticlesApi($this->pccClient);
    $response = $contentApi->getArticleBySlug($slug);
    $content = json_encode($response);

    return new JsonResponse(
      $content,
      200,
      [],
      true
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
}
