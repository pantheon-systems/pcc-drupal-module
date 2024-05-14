<?php

namespace Drupal\pcx_connect\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to exclude pcc site configs on config export.
 */
final class PccSiteEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    return $events;
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event): void {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();
    $all_configs = $storage->listAll();
    $pcc_site_configs = $this->getPccSiteConfigs($all_configs, 'pcx_connect.pcc_site');
    if ($pcc_site_configs) {
      foreach ($pcc_site_configs as $pcc_site_config) {
        $pcc_site = $storage->read($pcc_site_config);
        // Prevent the site_key and site_token from being exported.
        $pcc_site['site_key'] = '';
        $pcc_site['site_token'] = '';
        // Write to the storage from the event to alter it.
        $storage->write($pcc_site_config, $pcc_site);
      }
    }
  }

  /**
   * Returns the pcc site configs.
   *
   * @param array $configs
   *   The list of all configs.
   * @param string $pcc_site_config
   *   The pcc site config name.
   *
   * @return array
   *   Returns the list of pcc site config array.
   */
  protected function getPccSiteConfigs(array $configs, string $pcc_site_config): array {
    $pcc_site_configs = [];

    // Loop through the array.
    foreach ($configs as $config) {
      // Check if the element starts with "example".
      if (str_starts_with($config, $pcc_site_config)) {
        // If it does, add it to the result array.
        $pcc_site_configs[] = $config;
      }
    }
    return $pcc_site_configs;
  }

}
