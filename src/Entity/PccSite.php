<?php

namespace Drupal\pcx_connect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\pcx_connect\PccSiteInterface;

/**
 * Defines the PCC Site entity.
 *
 * @ConfigEntityType(
 *   id = "pcc_site",
 *   label = @Translation("PCC Site"),
 *   handlers = {
 *     "list_builder" = "Drupal\pcx_connect\Controller\PccSiteListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pcx_connect\Form\PccSiteForm",
 *       "edit" = "Drupal\pcx_connect\Form\PccSiteForm",
 *       "delete" = "Drupal\pcx_connect\Form\PccSiteDeleteForm",
 *     }
 *   },
 *   config_prefix = "pcc_site",
 *   admin_permission = "pcx_connect pcc_site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "site_key",
 *     "site_token",
 *     "site_url"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/pcc-sites/{pcc_site}",
 *     "delete-form" = "/admin/config/system/pcc-sites/{pcc_site}/delete",
 *   }
 * )
 */
class PccSite extends ConfigEntityBase implements PccSiteInterface {

  /**
   * The PCC Site ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The pcc site label.
   *
   * @var string
   */
  protected $label;

}
