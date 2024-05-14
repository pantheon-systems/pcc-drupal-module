<?php

namespace Drupal\pcx_connect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an PCC Site entity.
 */
interface PccSiteInterface extends ConfigEntityInterface {

  /**
   * Getter for captcha type property.
   *
   * @return string
   *   Captcha type.
   */
  public function getSiteKey();

  /**
   * Getter for captcha type property.
   *
   * @return string
   *   Captcha type.
   */
  public function getSiteToken();

  /**
   * Getter for captcha type property.
   *
   * @return string
   *   Captcha type.
   */
  public function getSiteName();

  /**
   * Getter for captcha type property.
   *
   * @return string
   *   Captcha type.
   */
  public function getSiteUrl();

}
