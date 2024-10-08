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
    $translation = \Drupal::translation();
    foreach ($pcc_sites as $pcc_site) {
      $id = $pcc_site->getEntityTypeId();
      $name = $pcc_site->label();
      $table = &$data[$pcc_site->id()];
      // Base data.
      $table['table']['group'] = $translation->translate('PCC Site - @name', ['@name' => $name]);
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
        'title' => $translation->translate('PCC Site - @name', ['@name' => $name]),
        'help' => $translation->translate('Use the ConfigEntity @name to view the data.', ['@name' => $name]),
        'query_id' => 'pcc_site_view_query',
      ];

      $table['id'] = [
        'title' => $translation->translate('ID'),
        'help' => $translation->translate('PCC Doc ID'),
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

      $table['title'] = [
        'title' => $translation->translate('Title'),
        'help' => $translation->translate('PCC Doc title'),
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
        'title' => $translation->translate('Content'),
        'help' => $translation->translate('PCC Doc Content'),
        'field' => [
          'id' => 'pcc_content',
          'click sortable' => FALSE,
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ];

      $table['snippet'] = [
        'title' => $translation->translate('Snippet'),
        'help' => $translation->translate('PCC Doc Snippet'),
        'field' => [
          'id' => 'pcc_content',
          'click sortable' => FALSE,
        ],
      ];

      $table['publishedDate'] = [
        'title' => $translation->translate('Published Date'),
        'help' => $translation->translate('PCC Doc publish time'),
        'field' => [
          'id' => 'date',
          'click sortable' => TRUE,
        ],
        'sort' => [
          'id' => 'date',
        ],
      ];

      $table['updatedAt'] = [
        'title' => $translation->translate('Updated At'),
        'help' => $translation->translate('PCC Doc update time'),
        'field' => [
          'id' => 'date',
          'click sortable' => TRUE,
        ],
        'sort' => [
          'id' => 'date',
        ],
      ];

      $table['previewActiveUntil'] = [
        'title' => $translation->translate('Preview Active Until'),
        'help' => $translation->translate('PCC Doc Preview Active Until'),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
      ];

      $table['slug'] = [
        'title' => $translation->translate('Slug'),
        'help' => $translation->translate('PCC Doc Slug'),
        'field' => [
          'id' => 'standard',
          'click sortable' => FALSE,
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ];

      $table['tags'] = [
        'title' => $translation->translate('Tags'),
        'help' => $translation->translate('PCC Doc tags'),
        'field' => [
          'id' => 'pcc_tags',
          'click sortable' => FALSE,
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ];

      $table['publishingLevel'] = [
        'title' => $translation->translate('Publishing level'),
        'help' => $translation->translate('PCC Doc Publishing level'),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ];

      $table['metadata'] = [
        'title' => $translation->translate('Metadata'),
        'help' => $translation->translate('PCC Doc Metadata'),
        'field' => [
          'id' => 'pcc_metadata',
        ],
      ];
    }
  }
  return array_filter($data);
}
