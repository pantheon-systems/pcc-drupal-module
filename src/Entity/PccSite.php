<?php

namespace Drupal\pcx_connect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $module_handler;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $views_data;


  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache_discovery;

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
  public function __construct(array $values, $entity_type) {
    $this->module_handler = \Drupal::service('module_handler');
    $this->views_data = \Drupal::service('views.views_data');
    $this->cache_discovery = \Drupal::service('cache.discovery');

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear cache to populate the PCC site in views.
    if ($this->module_handler->moduleExists('views')) {
      $this->views_data->clear();
      $this->cache_discovery->delete('views:wizard');
    }
  }

}
