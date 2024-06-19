<?php

namespace Drupal\pcx_smart_components\Service;

use Drupal\sdc\Component\ComponentMetadata;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;

/**
 * Smart Component Manager to map SDC to smart components.
 */
class SmartComponentManager {

  /**
   * SDC Component Plugin Manager.
   *
   * @var ComponentPluginManager $componentPluginManager
   */
  protected ComponentPluginManager $componentPluginManager;

  /**
   * Constructs SmartComponentManager.
   *
   * @param ComponentPluginManager $componentPluginManager
   *   SDC Component Plugin Manager.
   */
  public function __construct(ComponentPluginManager $componentPluginManager) {
    $this->componentPluginManager = $componentPluginManager;
  }

  /**
   * Get All smart components converting Drupal SDC components.
   *
   * @return array
   *   Array of smart components.
   */
  public function getAllSmartComponents(): array {
    $pccComponents = $this->getAllPccComponents();

    $smartComponents = [];
    foreach ($pccComponents as $pccComponent) {
      $componentId = $pccComponent->metadata->machineName;
      $smartComponents[strtoupper($componentId)] = $this->toSmartComponent($pccComponent);
    }

    return $smartComponents;
  }

  /**
   * Finds SDC Component based on machineName.
   *
   * @param string $machineName
   *   Component machine name without module / theme prefix.
   *
   * @return Component|null
   *   SDC Component or null.
   */
  public function getSDCComponent(string $machineName): ?Component {
    $allPccComponents = $this->getAllPccComponents();
    $result = NULL;

    foreach ($allPccComponents as $pccComponent) {
      if ($pccComponent instanceof Component
        && $machineName === $pccComponent->metadata->machineName) {
        $result = $pccComponent;
      }
    }

    return $result;
  }

  /**
   * Returns all PCC Components.
   *
   * @return Component[]
   *   Components with pcc_component set as true.
   */
  private function getAllPccComponents(): array {
    $allComponents = $this->componentPluginManager->getAllComponents();

    /**
     * @variable Drupal\sdc\Plugin\Component[]
     */
    $pccComponents = [];
    foreach ($allComponents as $component) {
      if ($component instanceof Component
        && !empty($component->getPluginDefinition()['pcc_component'])) {
        $pccComponents[] = $component;
      }
    }

    return $pccComponents;
  }

  /**
   * Convert Component to Smart Component array.
   *
   * @param Component $component
   *   Component object.
   *
   * @return array
   *   Smart Component array.
   */
  private function toSmartComponent(Component $component): array {
    return [
      'title' => $component->metadata->name,
      'iconUrl' => null,
      'fields' => $this->getFields($component->metadata),
    ];
  }

  /**
   * Get fields for smart component from ComponentMetadata.
   *
   * @param ComponentMetadata $componentMetadata
   *   ComponentMetadata object.
   *
   * @return array
   *   Array of fields for smart component.
   */
  private function getFields(ComponentMetadata $componentMetadata): array {
    $schema = $componentMetadata->schema;
    $requiredFields = $componentMetadata->schema['required'] ?? [];
    $fields = [];
    if (!empty($schema['properties'])) {
      foreach ($schema['properties'] as $field => $definition) {
        $fields[$field] = [
          'displayName' => $definition['title'],
          'required' => in_array($field, $requiredFields),
          'type' => !empty($definition['type'][0]) ? $definition['type'][0] : 'object',
        ];
      }
    }

    return $fields;
  }

}
