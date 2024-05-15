<?php

namespace Drupal\pcx_connect\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of PCC Sites.
 */
class PccSiteListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Site name');
    $header['site_key'] = $this->t('PCC Site Key');
    $header['site_token'] = $this->t('PCC Site Token');
    $header['site_url'] = $this->t('Site Url');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['site_key'] = $entity->get('site_key');
    $row['site_token'] = $entity->get('site_token');
    $row['site_url'] = $entity->get('site_url');
    return $row + parent::buildRow($entity);
  }

}