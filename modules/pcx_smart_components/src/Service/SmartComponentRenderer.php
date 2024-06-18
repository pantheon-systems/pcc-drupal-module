<?php

namespace Drupal\pcx_smart_components\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Error;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\sdc\Plugin\Component;

/**
 * Service to render pcc-component.
 */
class SmartComponentRenderer {

  /**
   * Smart Component Manager.
   *
   * @var SmartComponentManager $smartComponentManager
   */
  protected SmartComponentManager $smartComponentManager;

  /**
   * Logger.
   *
   * @var LoggerChannelInterface $logger
   */
  protected LoggerChannelInterface  $logger;

  /**
   * SmartComponentRenderer constructor.
   *
   * @param LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger Channel Factor Interface object.
   * @param SmartComponentManager $smartComponentManager
   *   Smart component manager.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, SmartComponentManager $smartComponentManager) {
    $this->logger = $loggerChannelFactory->get('pcx_smart_components');
    $this->smartComponentManager = $smartComponentManager;
  }

  /**
   * Return render array computed from the input string containing pcc-component tag.
   *
   * @param string $input
   *   Input string containing HTML tags including pcc-component.
   *
   * @return array
   *   Render Array.
   */
  function render(string $input): array {
    $output = [];
    $output['#markup'] = $input;
    try {
      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($input);
      libxml_clear_errors();
      $selector = new \DOMXPath($dom);
      $result = $selector->query('//pcc-component');
      foreach ($result as $node) {
        $component = $this->convertPccComponent($node);
        if (!empty($component)) {
          $output[] = $component;
        }
      }
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
    }

    return $output;
  }

  /**
   * Converts PccComponent Tag to Drupal component render array.
   *
   * @param \DOMElement $node
   *   DOMElement pcc-component.
   *
   * @return array|null
   *   Render Array for SDC component.
   */
  private function convertPccComponent(\DOMElement $node): ?array {
    $type = $node->getAttribute('type');
    $type = strtolower($type);
    $attrs = $node->getAttribute('attrs');
    // @todo: Update decoding based on ContentType enum.
    $attrs = !empty($attrs) ? base64_decode($attrs) : NULL;
    $attrs = !empty($attrs) ? json_decode($attrs, TRUE) : NULL;
    return !empty($attrs) ?
      [
        '#type' => 'component',
        '#component' => $this->getComponentName($type),
        '#props' => $attrs,
      ] : NULL;
  }

  /**
   * Get SDC component name to prepare render array.
   *
   * @param string $type
   *   Smart Component Type | Machine name of the SDC Component.
   *
   * @return string
   *   Render array component key.
   */
  protected function getComponentName(string $type): string {
    $component = $this->smartComponentManager->getSDCComponent($type);
    $provider = 'pcx_smart_components';
    if (!empty($component) && $component instanceof Component) {
      if (!empty($component->getPluginDefinition()['provider'])) {
        $provider = $component->getPluginDefinition()['provider'];
      }
    }

    return "$provider:$type";
  }

}
