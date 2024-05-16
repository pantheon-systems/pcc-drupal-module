<?php

namespace Drupal\pcx_connect;

/**
 * Helper methods for adding custom behavior to the view.
 */
class PccSiteViewHelper {

  /**
   * Retrieves mapping for Config Entity.
   *
   * @param string $id
   *   Config Entity id.
   *
   * @return array|null
   *   Returns array of Entity attributes.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getMapping(string $id): ?array {
    $config_type_all = self::getConfigTypedDefinitions();
    $config_prefix = self::getConfigPrefix($id);

    foreach ($config_type_all as $key => $value) {
      if (isset($value['type']) && $value['type'] == 'config_entity') {
        if (strpos($key, $config_prefix) === 0) {
          return ($value['mapping'] ?? NULL);
        }
      }
    }

    // Return an empty array to avoid PHP errors when field definitions.
    // That haven't been loaded yet or there is no mapping for the config
    // entity.
    return [];
  }

  /**
   * Gets provider and config prefix for Config Entity.
   *
   * @param string $id
   *   Config Entity id.
   *
   * @return string
   *   Returns config prefix.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getConfigPrefix(string $id): string {
    return \Drupal::entityTypeManager()->getDefinition($id)->getConfigPrefix();
  }

  /**
   * Gets the config.typed service.
   *
   * @return mixed
   *   Definitions of config.typed service.
   */
  protected static function getConfigTypedDefinitions() {
    return \Drupal::service('config.typed')->getDefinitions();
  }

  /**
   * Gets the Config Entity Label.
   *
   * @param string $id
   *   Config Entity iD.
   *
   * @return string
   *   Config Entity label used in the group list.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getConfigLabel(string $id): string {
    return (string) \Drupal::entityTypeManager()
      ->getDefinition($id)
      ->getLabel();
  }

}
