<?php

use EC\EuropaSearch\EuropaSearch;
use Psr\Log\LogLevel;
use EC\EuropaSearch\Exceptions\ValidationException;

/**
 * Class SearchApiEuropaSearchService.
 *
 * Defines the Europa Search service for Search API.
 */
class SearchApiEuropaSearchService extends \SearchApiAbstractService {

  protected $ESClientFactory;

  /**
   * {@inheritdoc}
   */
  public function supportsFeature($feature) {
    $supported = drupal_map_assoc(array(
      'search_api_data_type_new_type',
    ));
    return isset($supported[$feature]);
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(SearchApiIndex $index, array $items) {
    $this->initEuropaSearchClient();

    $returned_keys = array();
    foreach ($items as $id => $item) {
      try {
        $indexSender = new SearchApiEuropaSearchIndexSender($item, $this->options['ingestion_settings']['fallback_language']);
        $reference = $indexSender->sendMessage($this->ESClientFactory);
        watchdog('Search API Europa Search', 'reference received from the ES services: @ref.', array('@ref' => print_r($reference, TRUE)), WATCHDOG_INFO);
        $returned_keys[] = $id;
      }
      catch (ValidationException $ve) {
        $message = 'The submitted index item is invalid! The following validation errors has been detected: @errors';
        watchdog_exception('Search API Europa Search', $ve, $message, array('@errors' => print_r($ve->getValidationErrors(), TRUE)));
      }
      catch (\Exception $e) {
        watchdog_exception('Search API Europa Search', $e, $e->getMessage());
      }
    }

    return $returned_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems($ids = 'all', SearchApiIndex $index = NULL) {
    // TODO: Implement deleteItems() method after the ticket SEARCH-2346.
  }

  /**
   * {@inheritdoc}
   */
  public function search(SearchApiQueryInterface $query) {
    $this->initEuropaSearchClient();

    try {
      $searchSender = new SearchApiEuropaSearchSearchSender($query);
      $response = $searchSender->sendMessage($this->ESClientFactory);
      $responseParser = new SearchApiEuropaSearchSearchResponseParser($query);

      return $responseParser->parseSearch($response);
    }
    catch (ValidationException $ve) {
      $message = 'The submitted index item is invalid! The following validation errors has been detected: @errors';
      watchdog_exception('Search API Europa Search', $ve, $message, array('@errors' => print_r($ve->getValidationErrors(), TRUE)));
    }
    catch (\Exception $e) {
      watchdog_exception('Search API Europa Search', $e, $e->getMessage());
    }

    return array('result count' => 0);
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, array &$form_state) {
    // Default value.
    $this->options += array(
      'ingestion_settings' => array(),
      'search_settings' => array(),
    );

    $this->options['ingestion_settings'] += array(
      'ingestion_url_root' => '',
      'ingestion_url_port' => '',
      'ingestion_api_key' => '',
      'ingestion_database' => '',
      'fallback_language' => language_default('language'),
    );

    $this->options['search_settings'] += array(
      'search_url_root' => '',
      'search_url_port' => '',
      'search_api_key' => '',
      'activate_database_filter' => FALSE,
    );

    $form['ingestion_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Ingestion services settings (Indexing requests)'),
    );

    $form['ingestion_settings']['ingestion_url_root'] = array(
      '#type' => 'textfield',
      '#title' => t('Europa Search Service URL'),
      '#description' => t('URL root (without the last slash) pointing to the the Europa Search Indexing services (Ingestion API).'),
      '#required' => TRUE,
      '#default_value' => $this->options['ingestion_settings']['ingestion_url_root'],
    );

    $form['ingestion_settings']['ingestion_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Registered API key'),
      '#description' => t('The Europa Search API key to use with any indexing requests.'),
      '#required' => TRUE,
      '#default_value' => $this->options['ingestion_settings']['ingestion_api_key'],
    );

    $form['ingestion_settings']['fallback_language'] = array(
      '#type' => 'textfield',
      '#title' => t('Fallback language in case of Neutral language contentEuropa Search Service url port'),
      '#description' => t('The Europa Search REST services does not support "und" language. 
        A fallback language is to be set here for any entity to send for indexing.'),
      '#default_value' => $this->options['ingestion_settings']['fallback_language'],
    );

    $form['ingestion_settings']['ingestion_database'] = array(
      '#type' => 'textfield',
      '#title' => t('Registered database'),
      '#description' => t('The Europa Search database to use with any indexing requests.'),
      '#required' => TRUE,
      '#default_value' => $this->options['ingestion_settings']['ingestion_database'],
    );

    $form['search_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Search API services settings (Search requests)'),
    );
    $form['search_settings']['search_url_root'] = array(
      '#type' => 'textfield',
      '#title' => t('Europa Search Service URL'),
      '#description' => t('URL root (without the last slash) pointing to the the Europa Search REST services (Search API).'),
      '#required' => TRUE,
      '#default_value' => $this->options['search_settings']['search_url_root'],
    );

    $form['search_settings']['search_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Registered API key'),
      '#description' => t('The Europa Search API key to use with any search requests.'),
      '#required' => TRUE,
      '#default_value' => $this->options['search_settings']['search_api_key'],
    );
    $form['search_settings']['activate_database_filter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include the database value in search queries'),
      '#description' => t('Include the value of the registered ingestion database in all search queries.'),
      '#default_value' => $this->options['search_settings']['activate_database_filter'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormValidate(array $form, array &$values, array &$form_state) {
    // Checks the Services URL root validity.
    $url_root = $values['ingestion_settings']['ingestion_url_root'];
    if (!$this->validateConfiguredUrl($url_root)) {
      $form_item = $form['ingestion_settings']['ingestion_url_root'];
      form_error($form_item, t('The @title is not a valid url', array('@title' => $form_item['#title'])));
    }

    // Checks the Search Services URL root validity.
    $url_root = $values['search_settings']['search_url_root'];
    if (!$this->validateConfiguredUrl($url_root)) {
      $form_item = $form['search_settings']['search_url_root'];
      form_error($form_item, t('The @title is not a valid url', array('@title' => $form_item['#title'])));
    }
  }

  /**
   * Validates an URL set in the configuration form.
   *
   * @param string $url
   *   The URL to validate.
   *
   * @return bool
   *   TRUE if valid.
   */
  protected function validateConfiguredUrl($url) {
    return (valid_url($url, TRUE) && ('/' != substr($url, -1)));
  }

  /**
   * Initializes the EuropaSearch factory object.
   */
  protected function initEuropaSearchClient() {
    $option = $this->options['ingestion_settings'];
    $fullRoot = $option['ingestion_url_root'];
    $indexingSettings = array(
      'url_root' => $fullRoot,
      'api_key' => $option['ingestion_api_key'],
      'database' => $option['ingestion_database'],
    );

    $option = $this->options['search_settings'];
    $fullRoot = $option['search_url_root'];
    $searchSettings = array(
      'url_root' => $fullRoot,
      'api_key' => $option['search_api_key'],
    );
    $clientConfiguration = [
      'indexing_settings' => $indexingSettings,
      'search_settings' => $searchSettings,
      'services_settings' => [
        'logger' => new Psr3DrupalLog('Search API Europa Search'),
        'log_level' => LogLevel::DEBUG,
      ],
    ];

    $this->ESClientFactory = new EuropaSearch($clientConfiguration);
  }

}
