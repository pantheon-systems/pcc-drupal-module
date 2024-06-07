<?php

namespace Drupal\pcx_connect\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\SqlBase;

/**
 * The plugin to handle mini pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "pcc_pager",
 *   title = @Translation("PCC pager"),
 *   short_title = @Translation("PCC Pager"),
 *   help = @Translation("A simple pager containing previous and next links."),
 *   theme = "views_pcc_pager"
 * )
 */
class PccPager extends SqlBase {

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPlugin::defineOptions().
   *
   * Provides sane defaults for the next/previous links.
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['cursor'] = ['default' => 1713824001053];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $hide_fields = ['offset', 'id', 'total_pages', 'expose'];
    foreach ($hide_fields as $field) {
      $form[$field]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'],
        '@count item, skip @skip', 'PCC Pager, @count items, skip @skip',
        ['@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']]);
    }
    return $this->formatPlural($this->options['items_per_page'], '@count item', 'PCC Pager, @count items', ['@count' => $this->options['items_per_page']]);
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    // The 1, 3 indexes are correct, see template_preprocess_pager().
    $tags = [
      1 => $this->options['tags']['previous'],
      3 => $this->options['tags']['next'],
    ];
    $cursor = $this->options['cursor'];
    return [
      '#theme' => $this->themeFunctions(),
      '#tags' => $tags,
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
      '#cursor' => $cursor,
    ];
  }

}
