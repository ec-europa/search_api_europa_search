<?php

/**
 * Processor for specific results treatment on the Europa Search query results.
 *
 * It allows 2 things:
 * - Defining highlighting settings and communicating them to the
 *   Europa Search services.
 * - Applying a text format on any strings.
 */
class SearchApiEuropaSearchProcessor extends SearchApiAbstractProcessor {

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    $this->options += array(
      'highlight_prefix' => '<strong>',
      'highlight_suffix' => '</strong>',
      'highlight_limit' => 256,
      'result_text_format' => '_none',
    );

    $form['highlight_prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Highlighting prefix'),
      '#description' => t('Opening HTML tag(s) that will be prepended to all occurrences of search keywords in highlighted text.'),
      '#default_value' => $this->options['highlight_prefix'],
      '#required' => TRUE,
    );
    $form['highlight_suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Highlighting suffix'),
      '#description' => t('Closing HTML tag(s) that will be appended to all occurrences of search keywords in highlighted text.'),
      '#default_value' => $this->options['highlight_suffix'],
      '#required' => TRUE,
    );
    $form['highlight_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Highlight limit'),
      '#description' => t('Limit of highlighted text inside the fields text.'),
      '#default_value' => $this->options['highlight_limit'],
      '#element_validate' => array('element_validate_integer_positive'),
      '#required' => TRUE,
    );

    if (module_exists('filter')) {
      $formats = filter_formats();
      $options = array('_none' => t('Check plain'));
      foreach ($formats as $id => $format) {
        $options[$id] = check_plain($format->name);
      }

      $form['result_text_format'] = array(
        '#type' => 'select',
        '#title' => t('Text format on result text fields'),
        '#description' => t('Text format to apply on the text fields returned with the search results.<br />
        If "Check plain" is selected, "check_plain()" will be used.'),
        '#default_value' => $this->options['result_text_format'],
        '#options' => $options,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormValidate(array $form, array &$values, array &$form_state) {
    // Check that prefix and suffix values are correctly set.
    $testedString = $values['highlight_prefix'] . 'Test string' . $values['highlight_suffix'];
    $doc = new DOMDocument();
    $doc->validateOnParse = TRUE;
    libxml_use_internal_errors(TRUE);
    $doc->loadHTML($testedString);
    $results = libxml_get_errors();
    $dom = NULL;
    libxml_clear_errors();

    if (!empty($results) && count($results) > 0) {
      $prefixFormItem = $form['highlight_prefix'];
      $suffixFormItem = $form['highlight_suffix'];
      form_error(
        $prefixFormItem,
        t('The @title1 and @title2 are not correctly set! 
         Please check if opening tags are correctly closed in @title2.',
          array(
            '@title1' => $prefixFormItem['#title'],
            '@title2' => $suffixFormItem['#title'],
          )));
    }

    // Secure the text values.
    $values['highlight_prefix'] = filter_xss_admin($values['highlight_prefix']);
    $values['highlight_suffix'] = filter_xss_admin($values['highlight_suffix']);
    $values['highlight_limit'] = check_plain($values['highlight_limit']);
  }

  /**
   * {@inheritdoc}
   *
   * It injects the Europa Search Highlighting settings into the query options.
   */
  public function preprocessSearchQuery(SearchApiQuery $query) {
    parent::preprocessSearchQuery($query);

    $regex = $this->options['highlight_prefix'] . '{}' . $this->options['highlight_suffix'];

    $settings = array(
      'highlight_regex' => $regex,
      'highlight_limit' => intval($this->options['highlight_limit']),
    );
    $query->setOption('europa_search_highlight_settings', $settings);

    $isTextFormatEnabled = ('_none' != $this->options['result_text_format']);
    $query->setOption('europa_search_text_format_enabled', $isTextFormatEnabled);
  }

  /**
   * {@inheritdoc}
   *
   * It applies the text format on text fields.
   */
  public function postprocessSearchResults(array &$response, SearchApiQuery $query) {

    if (empty($response['results'])) {
      return;
    }

    if (!module_exists('filter')) {
      watchdog('Search API Europa Search', '"Text formatting" on result text fields cannot work because the "Filter" module is disable.');
      return;
    }

    $indexedField = $query->getIndex()->getFields();
    foreach ($response['results'] as $id => &$result) {
      foreach ($result['fields'] as $fieldName => &$field) {
        if (!isset($indexedField[$fieldName])) {
          // If another processor added a non-indexed field, it is up to it to
          // sanitize the value.
          continue;
        }

        $dataType = search_api_extract_inner_type($indexedField[$fieldName]['type']);
        $typeInfo = search_api_get_data_type_info($dataType);

        if ('string' != $typeInfo['fallback']) {
          continue;
        }

        $field['#value'] = check_markup($field['#value'], $this->options['result_text_format']);
      }
    }
  }

}
