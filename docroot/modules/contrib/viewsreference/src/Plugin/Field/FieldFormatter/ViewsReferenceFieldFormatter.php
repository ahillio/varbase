<?php

namespace Drupal\viewsreference\Plugin\Field\FieldFormatter;

use Drupal\views\Views;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field formatter for Viewsreference Field.
 *
 * @FieldFormatter(
 *   id = "viewsreference_formatter",
 *   label = @Translation("Views reference"),
 *   field_types = {"viewsreference"}
 * )
 */
class ViewsReferenceFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['plugin_types'] = ['block'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $types = Views::pluginList();
    $options = [];
    foreach ($types as $key => $type) {
      if ($type['type'] == 'display') {
        $options[str_replace('display:', '', $key)] = $type['title']->render();
      }
    }
    $form['plugin_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('View display plugins to allow'),
      '#default_value' => $this->getSetting('plugin_types'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $allowed = [];
    $settings = $this->getSettings();
    foreach ($settings['plugin_types'] as $type) {
      if ($type) {
        $allowed[] = $type;
      }
    }
    $summary[] = t('Allowed plugins: @view', ['@view' => implode(', ', $allowed)]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $view_name = $item->getValue()['target_id'];
      $display_id = $item->getValue()['display_id'];
      $data = unserialize($item->getValue()['data']);
      $view = Views::getView($view_name);
      // Add an extra check because the view could have been deleted.
      if (!is_object($view)) {
        continue;
      }

      $view->setDisplay($display_id);

      // Let all settings plugins alter the view.
      $viewsreference_plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
      $plugin_definitions = $viewsreference_plugin_manager->getDefinitions();
      $enabled_settings = array_filter($this->getFieldSetting('enabled_settings'));
      foreach ($enabled_settings as $enabled_setting) {
        if (!empty($plugin_definitions[$enabled_setting])) {
          $plugin_definition = $plugin_definitions[$enabled_setting];
          /** @var \Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface $plugin_instance */
          $plugin_instance = $viewsreference_plugin_manager->createInstance($plugin_definition['id']);
          $value = isset($data[$plugin_definition['id']]) ? $data[$plugin_definition['id']] : $plugin_definition['default_value'];
          $plugin_instance->alterView($view, $value);
        }
      }

      $view->build($display_id);
      $view->preExecute();
      $view->execute($display_id);

      if (!empty($view->result) || !empty($view->empty)) {
        if ($this->getSetting('plugin_types')) {
          // Add a custom template if the title is available.
          $title = $view->getTitle();
          if (!empty($title)) {
            $elements[$delta]['title'] = [
              '#theme' => 'viewsreference__view_title',
              '#title' => $title,
            ];
          }
        }
        $elements[$delta]['contents'] = $view->buildRenderable($display_id, $view->args, FALSE);
      }
    }
    return $elements;
  }

}
