<?php

namespace Drupal\sliderwidget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'sliderwidget_widget' widget.
 *
 * @FieldWidget(
 *   id = "sliderwidget_widget",
 *   label = @Translation("Slider ui field widget"),
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer"
 *   }
 * )
 */
class SliderUIFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'sliderwidget_settings' => [
          'animate' => FALSE,
          'orientation' => 'horizontal',
          'range' => FALSE,
          'step' => 1,
          'slider_style' => NULL,
          'display_values' => TRUE,
          'display_values_format' => '%{value}%',
          'display_bubble' => FALSE,
          'display_bubble_format' => '%{value}%',
          'slider_length' => NULL,
          'hide_inputs' => TRUE,
          'multi_value' => FALSE,
          'display_ignore_button' => TRUE,
          'hide_slider_handle_when_no_value' => FALSE,
        ]
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $settings = $this->getSettings();

    $elements['sliderwidget_settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Slider Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 0
    );

    $elements['sliderwidget_settings']['animate'] = [
      '#type' => 'select',
      '#title' => $this->t('Animate'),
      '#options' => [
        FALSE => $this->t('Disable'),
        TRUE => $this->t('Default'),
        'fast' => $this->t('Fast'),
        'slow' => $this->t('Slow'),
        'custom' => $this->t('Custom')
      ],
      '#default_value' => $settings['sliderwidget_settings']['animate']
    ];
    $elements['sliderwidget_settings']['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical')
      ],
      '#require' => TRUE,
      '#description' => $this->t('Determines whether the slider handles move horizontally (min on left, max on right) or vertically (min on bottom, max on top).'),
      '#default_value' => $settings['sliderwidget_settings']['orientation']
    ];
    $elements['sliderwidget_settings']['range'] = [
      '#type' => 'select',
      '#title' => $this->t('Range'),
      '#options' => [
        FALSE => $this->t('Disable'),
        TRUE => $this->t('Auto'),
        'min' => $this->t('Minimum'),
        'max' => $this->t('Maximum')
      ],
      '#description' => $this->t('Whether the slider represents a range.'),
      '#default_value' => $settings['sliderwidget_settings']['range']
    ];
    $elements['sliderwidget_settings']['step'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Step'),
      '#size' => 5,
      '#description' => $this->t('Determines the size or amount of each interval or step the slider takes between the min and max. The full specified value range of the slider (max - min) should be evenly divisible by the step.'),
      '#required' => TRUE,
      '#element_validate' => [$this, 'sliderwidgetValidatePositiveNumber'],
      '#default_value' => $settings['sliderwidget_settings']['step']
    ];
    $elements['sliderwidget_settings']['slider_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => $this->sliderwidgetStyles(),
      '#description' => $this->t('Some default color styles for ease of use'),
      '#default_value' => $settings['sliderwidget_settings']['slider_style']
    ];
    $elements['sliderwidget_settings']['display_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Values'),
      '#description' => $this->t('If enabled display the current values of slider as simple text.'),
      '#default_value' => $settings['sliderwidget_settings']['display_values']
    ];
    $display_values_format = $settings['sliderwidget_settings']['display_values_format'];
    $display_values_format = !isset($display_values_format) ? '%{value}%' : $display_values_format;
    $elements['sliderwidget_settings']['display_values_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display Values Format'),
      '#size' => 15,
      '#description' => $this->t('Format of the displaied values, The usage is mostly for showing $,% or other signs near the value. Use %{value}% as slider value'),
      '#default_value' => $display_values_format
    ];
    $display_bubble = $settings['sliderwidget_settings']['display_bubble'];
    $display_bubble = !isset($display_bubble) ? '%{value}%' : $display_bubble;
    $elements['sliderwidget_settings']['display_bubble'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display bubble/hint'),
      '#description' => $this->t('Display a hint/bubble near each slider handle showing the value of that handle.'),
      '#default_value' => $display_bubble
    ];
    $display_bubble_format = $settings['sliderwidget_settings']['display_bubble_format'];
    $display_bubble_format = !isset($display_bubble_format) ? '%{value}%' : $display_bubble_format;
    $elements['sliderwidget_settings']['display_bubble_format'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#title' => $this->t('Display bubble/hint format'),
      '#description' => $this->t('Format of the displaied values in bubble/hint, The usage is mostly for showing $,% or other signs near the value. Use %{value}% as slider value. For range slider it can have two values separated by || like "$%{value}%MIN||$%{value}%MAX"'),
      '#default_value' => $display_bubble_format
    ];
    $elements['sliderwidget_settings']['slider_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slider Length'),
      '#size' => 5,
      '#description' => $this->t('Acceptable types are the same as css width and height (100px) and it will be used as width or height depending on #orientation'),
      '#default_value' => $settings['sliderwidget_settings']['slider_length']
    ];
    $elements['sliderwidget_settings']['hide_inputs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Input Textfields'),
      '#description' => $this->t('If enabled displays only the slider and hides input textfields.'),
      '#default_value' => $settings['sliderwidget_settings']['hide_inputs']
    ];
    $multi_value = $settings['sliderwidget_settings']['multi_value'];
    $multi_value = !isset($multi_value) ? '' : $multi_value;
    $elements['sliderwidget_settings']['multi_value'] = [
      '#type' => 'select',
      '#title' => $this->t('Multi Value'),
      '#options' => [
        FALSE => $this->t('Disable'),
        'separate' => $this->t('Enable')
      ],
      '#description' => $this->t('Uses field\'s multi value feature to store the values, currently only 2 values are supported. A separate handle for each value will be shown on slider'),
      '#default_value' => $multi_value
    ];
    $elements['sliderwidget_settings']['display_ignore_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display ignore button'),
      '#description' => $this->t('When field is not required, and hide input fields option is active a checkbox will be shown allowing user to ignore the field allowing user to ignore the field and enter no value.'),
      '#default_value' => $settings['sliderwidget_settings']['display_ignore_button'],
    ];
    $elements['sliderwidget_settings']['hide_slider_handle_when_no_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide slider handle when there is no value'),
      '#description' => $this->t('When the slider does not have any value by enabling this option it won\'t show the the slider handle unless user clicks on the slider to select a value.'),
      '#default_value' => $settings['sliderwidget_settings']['hide_slider_handle_when_no_value']
    ];

    return $elements;
  }

  /**
   * Helper function return available styles for slider.
   */
  protected function sliderwidgetStyles() {
    $items = array(
      '' => $this->t('Default'),
      'red' => $this->t('Red'),
      'green' => $this->t('Green'),
      'blue' => $this->t('Blue'),
      'orange' => $this->t('Orange'),
      'purple' => $this->t('Purple'),
      'steel-blue' => $this->t('Steel Blue'),
      'tiger-orange' => $this->t('Tiger Orange'),
      'wild-blue-yonder' => $this->t('Wild Blue Yonder'),
      'cinereous' => $this->t('Cinereous'),
      'laurel-green' => $this->t('Laurel Green')
    );

    return $items;
  }

  /**
   * Validate slider steps to be positive.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function sliderwidgetValidatePositiveNumber($element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    if (!is_numeric($value) && !is_float($value) && !empty($value)) {
      $form_state->setError($element, t('The value should be a valid number'));
    }
    elseif ($value < 0) {
      $form_state->setError($element, t('The value should be a valid positive number'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];
    $summary[] = t('Textfield orientation: @orientation', ['@orientation' => $settings['sliderwidget_settings']['orientation']]);
    $summary[] = t('Steps: @step', ['@step' => $settings['sliderwidget_settings']['step']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // Set minimum and maximum.
    if (is_numeric($field_settings['min'])) {
      $element['#min'] = $field_settings['min'];
    }
    if (is_numeric($field_settings['max'])) {
      $element['#max'] = $field_settings['max'];
    }

    // Add prefix and suffix.
    if ($field_settings['prefix']) {
      $prefixes = explode('|', $field_settings['prefix']);
      $element['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if ($field_settings['suffix']) {
      $suffixes = explode('|', $field_settings['suffix']);
      $element['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }


    $settings = $this->getSettings()['sliderwidget_settings'];
    $value = NULL;
    if (!empty($items) && isset($items[$delta]) && isset($items[$delta]->value)) {
      $value = $items[$delta]->value;
    }
    else {
      $value = $field_settings['min'];
    }
    if (!isset($settings['display_values_format'])) {
      $settings['display_values_format'] = '%{value}%';
    }
    if (!isset($settings['display_bubble'])) {
      $settings['display_bubble'] = FALSE;
    }
    if (!isset($settings['display_bubble_format'])) {
      $settings['display_bubble_format'] = '%{value}%';
    }
    $element += array(
      '#default_value' => $value,
      '#type' => 'slider',
      '#input_title' => NULL,
      '#animate' => $settings['animate'],
      '#adjust_field_min' => isset($settings['adjust_field_min'])? '.' . Html::cleanCssIdentifier('sliderwidget-field-adjust-' . $settings['adjust_field_min']) : '',
      '#adjust_field_max' => isset($settings['adjust_field_max'])? '.' . Html::cleanCssIdentifier('sliderwidget-field-adjust-' . $settings['adjust_field_max']) : '',
      '#disabled' => (isset($element['#disabled'])) ? $element['#disabled'] : FALSE,
      '#orientation' => $settings['orientation'],
      '#range' => $settings['range'],
      '#step' => $settings['step'],
      '#slider_style' => $settings['slider_style'],
      '#size' => 3,
      '#display_inputs' => !$settings['hide_inputs'],
      '#display_values' => $settings['display_values'],
      '#display_values_format' => $settings['display_values_format'],
      '#slider_length' => $settings['slider_length'],
      '#display_inside_fieldset' => FALSE,
      '#validate_range' => FALSE,
      '#display_bubble' => $settings['display_bubble'],
      '#display_bubble_format' => $settings['display_bubble_format'],
      '#display_ignore_button' => $settings['display_ignore_button'],
      '#hide_slider_handle_when_no_value' => $settings['hide_slider_handle_when_no_value'],
      '#fields_to_sync_css_selector' => @$settings['fields_to_sync_css_selector'],
    );

    return ['value' => $element];
  }

}
