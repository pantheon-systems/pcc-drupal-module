<?php

namespace Drupal\pcx_connect\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the PCC Site add and edit forms.
 */
class PccSiteForm extends EntityForm {

  /**
   * Constructs an PccSiteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $pcc_site = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name'),
      '#maxlength' => 255,
      '#default_value' => $pcc_site->label(),
      '#description' => $this->t("Name for the PCC Site."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $pcc_site->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$pcc_site->isNew(),
    ];

    $form['site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Key'),
      '#maxlength' => 255,
      '#default_value' => $pcc_site->get('site_key') ?: '',
      '#description' => $this->t("Site Key for PCC Site."),
      '#required' => TRUE,
    ];

    $form['site_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Token'),
      '#maxlength' => 255,
      '#default_value' => $pcc_site->get('site_token') ?: '',
      '#description' => $this->t("Site token for PCC Site."),
      '#required' => TRUE,
    ];

    $form['site_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Site URL'),
      '#maxlength' => 255,
      '#default_value' => $pcc_site->get('site_url') ?: '',
      '#description' => $this->t("Site URL for PCC Site."),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://',
      ],
    ];

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $pcc_site = $this->entity;
    $status = $pcc_site->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label PCC Site is created.', [
        '%label' => $pcc_site->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label PCC Site is updated.', [
        '%label' => $pcc_site->label(),
      ]));
    }

    $form_state->setRedirect('entity.pcc_site.collection');
  }

  /**
   * Helper function to check whether an PCC Site configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('pcc_site')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}