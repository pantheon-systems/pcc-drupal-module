<?php

namespace Drupal\pcx_connect\Plugin\views\field;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler to render html markup.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("pcc_metadata")
 */
class PccMetadata extends FieldPluginBase {
  /**
   * PCC Content API service.
   *
   * @var \Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface
   */
  protected PccArticlesApiInterface $pccContentApi;

  /**
   * Constructs a PccSiteViewQuery object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The database-specific date handler.
   * @param \Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface $pccContentApi
   *   The PCC Content API Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PccArticlesApiInterface $pccContentApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pccContentApi = $pccContentApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pcx_connect.pcc_articles_api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['pcc_metadata_fields'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['pcc_metadata'] = [
      '#type' => 'details',
      '#title' => $this->t('PCC Metadata'),
      '#weight' => 98,
    ];
    $pcc_site = PccSite::load($this->table);
    $options = [];
    if ($pcc_site) {
      $site_key = $pcc_site->get('site_key');
      $site_token = $pcc_site->get('site_token');
      $site_data = $this->pccContentApi->getPccSiteData($site_key, $site_token);
      if ($site_data) {
        $site_data_arr = Json::decode($site_data);
        if (!empty($site_data_arr['data']['site']['metadataFields'])) {
          $metadata = $site_data_arr['data']['site']['metadataFields'];
          foreach ($metadata as $data) {
            $options[$data['title']] = $this->t('@title', ['@title' => $data['title']]);
          }
        }
      }
      if ($options) {
        $form['pcc_metadata_fields'] = [
          '#type' => 'radios',
          '#title' => $this->t('Available Metadata Fields'),
          '#options' => $options,
          '#default_value' => $this->options['pcc_metadata_fields'],
          '#fieldset' => 'pcc_metadata',
        ];
      }
      else {
        $form['pcc_metadata_empty'] = [
          '#type' => 'markup',
          '#markup' => 'Metadata fields are not available for this site.',
          '#fieldset' => 'pcc_metadata',
        ];
      }
    }
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (!$form_state->getValue(['options', 'pcc_metadata_fields'])) {
      $form_state->setError($form['pcc_metadata'], $this->t('Please select a metadata field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $metadata_field = $this->options['pcc_metadata_fields'];
    $value = $this->getValue($values);

    if ($value && isset($value[$metadata_field])) {
      // Post Date returns an array so need special handeling.
      if ($metadata_field === 'Post Date') {
        $field_value = $value[$metadata_field]['msSinceEpoch'];
      }
      else {
        $field_value = $value[$metadata_field];
      }
      return $field_value;
    }
  }

}
