<?php

namespace Drupal\pcx_connect\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\pcx_connect\PccSiteInterface;
use Drupal\views\ViewsData;

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

  protected ModuleHandlerInterface $module_handler;

  protected ViewsData $views_data;


  protected CacheBackendInterface $cache_discovery;

  protected string $id;

  protected string $label;

  protected string $site_key;

  protected string $site_token;

  protected string $site_url;

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
