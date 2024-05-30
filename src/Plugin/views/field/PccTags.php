<?php

namespace Drupal\pcx_connect\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Handler to render html markup.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("pcc_tags")
 */
class PccTags extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $tags = (!empty($value)) ? implode(", ", $value) : '';
    return $tags;
  }

}
