<?php

namespace Drupal\pcx_connect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\pcx_connect\PccSiteInterface;
use Drupal\views\Views;

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
 *   admin_permission = "administer pcc configurations",
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

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear cache to populate the PCC site in views.
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      Views::viewsData()->clear();
      \Drupal::cache('discovery')->delete('views:wizard');
    }
  }

}
