<?php

/**
 * @file
 * Views hook implementations for the PCX Connect module.
 */

use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\PccSiteViewHelper;

/**
 * Implements hook_views_data().
 */
function pcx_connect_views_data() {
  $data = [];

  $pcc_sites = PccSite::loadMultiple();
  if ($pcc_sites) {
    foreach ($pcc_sites as $pcc_site) {
      $id = $pcc_site->getEntityTypeId();
      $name = $pcc_site->label();
      $table = &$data[$pcc_site->id()];
      // Base data.
      $table['table']['group'] = t('PCC Site - @name', ['@name' => $name]);
      $table['table']['provider'] = $id;

      // Locate the config entity type's ID.
      $entityType = \Drupal::entityTypeManager()
        ->getStorage($id)
        ->getEntityType();
      $id_key = $entityType->getKey('id');

      // Get entity fields.
      $fields = PccSiteViewHelper::getMapping($id);
      if (empty($fields[$id_key])) {
        continue;
      }
      if (empty($fields[$id_key]['label'])) {
        continue;
      }

      $table['table']['base'] = [
        'field' => strtolower($fields[$id_key]['label']),
        'index' => strtolower($fields[$id_key]['label']),
        'title' => t('PCC Site - @name', ['@name' => $name]),
        'help' => t('Use the ConfigEntity @name to view the data.', ['@name' => $name]),
        'query_id' => 'pcc_site_view_query',
      ];

      $table['title'] = [
        'title' => t('Title'),
        'help' => t('Article title'),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ];

      $table['content'] = [
        'title' => t('Content'),
        'help' => t('Article Content'),
        'field' => [
          'id' => 'standard',
          'click sortable' => FALSE,
        ],
        'filter' => [
          'id' => 'string',
        ],
      ];

      $table['snippet'] = [
        'title' => t('Snippet'),
        'help' => t('Article Snippet'),
        'field' => [
          'id' => 'standard',
          'click sortable' => FALSE,
        ],
        'filter' => [
          'id' => 'string',
        ],
      ];

      $table['publishedAt'] = [
        'title' => t('Published At'),
        'help' => t('Article publish time'),
        'field' => [
          'id' => 'date',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'date',
        ],
        'argument' => [
          'id' => 'date',
        ],
      ];

      $table['updatedAt'] = [
        'title' => t('Updated At'),
        'help' => t('Article update time'),
        'field' => [
          'id' => 'date',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'date',
        ],
        'argument' => [
          'id' => 'date',
        ],
      ];
    }
  }
  return array_filter($data);
}
