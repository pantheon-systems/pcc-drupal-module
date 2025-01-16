<?php

namespace Drupal\pcx_smart_components\Plugin\views\field;

use Drupal\pcx_smart_components\Service\SmartComponentRenderer;
use Drupal\pcx_connect\Plugin\views\field\PccContent as BasePccContent;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler to render html markup.
 * Overrides Drupal\pcx_connect\Plugin\views\field\PccContent.
 *
 * @ingroup views_field_handlers
 */
class PccContent extends BasePccContent {

  /**
   * PccContent Plugin constructor.
   *
   * @param ContainerInterface $container
   *   Container Interface Object.
   * @param array $configuration
   *   Configuration array.
   * @param $plugin_id
   *   Plugin ID.
   * @param $plugin_definition
   *   Plugin Definition.
   * @param SmartComponentRenderer $smartComponentRenderer
   *   Smart Component Renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected SmartComponentRenderer $smartComponentRenderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, 
      $plugin_id, 
      $plugin_definition, 
      $container->get('pcx_smart_components.renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->smartComponentRenderer->render($value);
  }

}
