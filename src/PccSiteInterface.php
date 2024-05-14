<?php

namespace Drupal\pcx_connect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an PCC Site entity.
 */
interface PccSiteInterface extends ConfigEntityInterface {

  /**
   * Getter for pcc site token property.
   *
   * @return string
   *   pcc site key.
   */
  public function getSiteKey();

  /**
   * Getter for pcc site token property.
   *
   * @return string
   *   pcc site token.
   */
  public function getSiteToken();

  /**
   * Getter for pcc site name property.
   *
   * @return string
   *   pcc site name.
   */
  public function getSiteName();

  /**
   * Getter for pcc site url property.
   *
   * @return string
   *   pcc site url.
   */
  public function getSiteUrl();

}
