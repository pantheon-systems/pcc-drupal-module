<?php

namespace Drupal\pcx_connect\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\views\ResultRow;

/**
 * Field handler to provide a list of permissions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("pcc_tags")
 */
class PccTags extends PrerenderList {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['tags_make_link'] = ['default' => FALSE];
    $options['tags_path'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['tags_make_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output tags as a custom link'),
      '#default_value' => $this->options['tags_make_link'],
    ];
    $form['tags_path'] = [
      '#title' => $this->t('Tags link path'),
      '#type' => 'textfield',
      '#default_value' => $this->options['tags_path'],
      '#description' => $this->t('The Drupal path or absolute URL for this link. The URL should start with /. You may enter {{ tag }} as a replacement pattern.'),
      '#states' => [
        'visible' => [
          ':input[name="options[tags_make_link]"]' => ['checked' => TRUE],
        ],
      ],
      '#maxlength' => 255,
    ];
    unset($form['alter']['make_link']);
    unset($form['alter']['path']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (!str_starts_with($form_state->getValue(['options', 'tags_path']), '/')) {
      $form_state->setError($form['tags_path'], $this->t('The Drupal path or absolute URL for this link should start with /.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $this->items = [];
    $tags = [];
    foreach ($values as $result) {
      $tags = $this->getValue($result);
      if ($tags) {
        foreach ($tags as $key => $tag) {
          $this->items[$key]['tags'] = $tag;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    if (!empty($this->items)) {
      return $this->items;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    return $item['tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function renderText($alter) {
    $value = (string) $this->last_render;
    if ($this->options['tags_make_link']) {
      $tags_path = $this->options['tags_path'];
      $tag_link = $this->renderTagAsLink($tags_path, $value);
      return Markup::create($tag_link);
    }
    return $value;
  }

  /**
   * Render tag as a link.
   *
   * @param string $tags_path
   *   Tags custom path.
   * @param string $value
   *   The tag value.
   *
   * @return string
   *   Returns the tag link.
   */
  private function renderTagAsLink($tags_path, $value): string {
    $token = '{{ tag }}';
    if ($tags_path != '<front>') {
      $tags_path = strip_tags($tags_path);
      $link_value = str_replace(' ', '-', strtolower($value));
      $link_path = str_replace($token, $link_value, $tags_path);
      $tags_url = Url::fromUserInput($link_path);
      $render = [
        '#type' => 'link',
        '#title' => $value,
        '#url' => $tags_url,
      ];
      return $this->getRenderer()->render($render);
    }
    return $value;
  }

}
