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
 *   admin_permission = "administer site configuration",
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
   * The PCC Site key.
   *
   * @var string
   */
  protected $site_key;

  /**
   * The pcc site token.
   *
   * @var string
   */
  protected $site_token;

  /**
   * The PCC site URL.
   *
   * @var string
   */
  protected $site_url;

  /**
   * {@inheritdoc}
   */
  public function getSiteKey() {
    if (isset($this->site_key)) {
      return $this->site_key;
    }
    else {
      return static::getConfigManager()
        ->getConfigFactory()
        ->get('site_key');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteToken() {
    if (isset($this->site_token)) {
      return $this->site_token;
    }
    else {
      return static::getConfigManager()
        ->getConfigFactory()
        ->get('site_token');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteName() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteUrl() {
    if (isset($this->site_url)) {
      return $this->site_url;
    }
    else {
      return static::getConfigManager()
        ->getConfigFactory()
        ->get('site_url');
    }
  }

}
