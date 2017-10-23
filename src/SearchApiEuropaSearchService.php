<?php

use EC\EuropaSearch\EuropaSearch;
use EC\EuropaSearch\EuropaSearchConfig;

/**
 * Class SearchApiEuropaSearchService.
 *
 * Defines the Europa Search service for Search API.
 */
class SearchApiEuropaSearchService extends SearchApiAbstractService {

  protected $indexingClient;

  protected $searchClient;

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
    initEuropaSearchClient();
    // TODO: Implement indexItems() method.
    $returned_keys = array();
    foreach ($items as $id => $item) {
      watchdog('GILLES item ' . $id, print_r($item, TRUE));
      $returned_keys[] = $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems($ids = 'all', SearchApiIndex $index = NULL) {
    // TODO: Implement deleteItems() method.
  }

  /**
   * {@inheritdoc}
   */
  public function search(SearchApiQueryInterface $query) {
    // TODO: Implement search() method.
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, array &$form_state) {
    // Default value.
    $this->options += array(
      'url_root' => '',
      'url_port' => '',
      'ingestion_settings' => array(
        'ingestion_api_key' => '',
        'ingestion_database' => '',
      ),
      'search_settings' => array(
        'search_api_key' => '',
        'activate_database_filter' => FALSE,
      ),
    );

    $form['url_root'] = array(
      '#type' => 'textfield',
      '#title' => t('Europa Search Service domain name'),
      '#description' => t('URL root (without the last slash) where the the Europa Search REST services to use are host; ex.: https://search.ec.europa.eu.'),
      '#required' => TRUE,
      '#default_value' => $this->options['url_root'],
    );
    $form['url_port'] = array(
      '#type' => 'textfield',
      '#title' => t('Europa Search Service url port'),
      '#description' => t('Port number to use with the URL of the Europa Search REST services.'),
      '#default_value' => $this->options['url_port'],
    );
    $form['ingestion_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Ingestion services settings (Indexing requests)'),
    );
    $form['ingestion_settings']['ingestion_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Registered API key'),
      '#description' => t('The Europa Search API key to use with any indexing requests.'),
      '#required' => TRUE,
      '#default_value' => $this->options['ingestion_settings']['ingestion_api_key'],
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
    $url_root = $values['url_root'];
    if (!valid_url($url_root, TRUE) || ('/' == substr($url_root, -1))) {
      form_error($form['url_root'], t('The @title is not a valid url', array('@title' => $form['url_root']['#title'])));
    }

    // Checks the Services URL port validity.
    $url_port = $values['url_port'];
    if (!empty($url_port) && (!is_numeric($url_port) || $url_port < 0 || $url_port > 65535)) {
      $message_parameter = array('@title' => $form['url_port']['#title']);
      form_error($form['url_port'], t('The @title is not a valid port. It should be a numeric value between 0 and 65535', $message_parameter));
    }
  }

  /**
   * Initialize the EuropaSearch factory object.
   */
  protected function initEuropaSearchClient() {
    $fullRoot = $this->options['url_root'];
    if (!empty($this->options['url_port'])) {
      $fullRoot .= ':' . $this->options['url_port'];
    }
    $wsSettings = array(
      'URLRoot' => $fullRoot,
      'APIKey' => $this->options['ingestion_settings']['ingestion_api_key'],
      'database' => $this->options['ingestion_settings']['ingestion_database'],
    );
    $settings = new EuropaSearchConfig($wsSettings);
    $this->ESClientFactory = new EuropaSearch($settings);
  }

  /**
   * Set the HTTP client object to send indexing requests.
   *
   * @param string $entityType
   *   The type of the entity that must be sent for indexing.
   *
   * @throws Exception
   *   Raised if the entity type is not supported by the
   *   "ec-europa/oe-europa-search-client" library yet.
   */
  protected function getIndexingClient($entityType = 'node') {

    $webContentType = array(
      'node',
      'taxonomy',
    );
    if (in_array($entityType, $webContentType)) {
      $this->indexingClient = $this->ESClientFactory->getIndexingWebContentClient();
    }

    throw new \Exception('The entity type is not supported by the Europa Search Search API module yet.');
  }

}
