<?php

namespace Drupal\pcx_smart_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\pcx_smart_components\Service\SmartComponentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns schema response for PCC Smart Component route.
 */
class ComponentSchemaController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Smart Component Manager
   * To get all pcc components and convert into smart components.
   *
   * @var SmartComponentManager $smartComponentManager
   */
  protected SmartComponentManager $smartComponentManager;

  /**
   * @param SmartComponentManager $smartComponentManager
   */
  public function __construct(SmartComponentManager $smartComponentManager) {
    $this->smartComponentManager = $smartComponentManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pcx_smart_components.smart_component_manager')
    );
  }

  /**
   * Builds the response.
   */
  public function __invoke(): JsonResponse {
    $smartComponents = $this->smartComponentManager->getAllSmartComponents();
    $smartComponentsJson = json_encode($smartComponents);

    return new JsonResponse(json_decode($smartComponentsJson, TRUE));
  }

}
