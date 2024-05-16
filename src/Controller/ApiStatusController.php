<?php

namespace Drupal\pcx_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Api Status Controller responsible for returning response for pcc site status endpoint.
 */
class ApiStatusController extends ControllerBase {

  /**
   * Returns empty response.
   *
   * @return JsonResponse
   *   Empty JSON response.
   */
  public function emptyResponse() {
    return new JsonResponse();
  }
}
