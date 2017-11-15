<?php

namespace Drupal\search_api_europa_search;

use Drupal\search_api_europa_search\Traits\SearchApiEuropaSearchUtil;
use Drupal\search_api_europa_search\Index\SearchApiEuropaSearchIndexSender;
use Drupal\search_api_europa_search\Search\SearchApiEuropaSearchSearchSender;
use Drupal\search_api_europa_search\Search\SearchApiEuropaSearchSearchResponseParser;
use EC\EuropaSearch\EuropaSearch;
use Psr\Log\LogLevel;
use EC\EuropaSearch\Exceptions\ValidationException;

/**
 * Class SearchApiEuropaSearchService.
 *
 * Defines the Europa Search service for Search API.
 */
class SearchApiEuropaSearchService extends \SearchApiAbstractService {

  use SearchApiEuropaSearchUtil;

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
  public function indexItems(\SearchApiIndex $index, array $items) {
    $this->initEuropaSearchClient();

    $returned_keys = array();
    $indexSender = new SearchApiEuropaSearchIndexSender($this->ESClientFactory, $this->options['ingestion_settings']['fallback_language']);

    foreach ($items as $id => $item) {
      try {
        $reference = $indexSender->sendIndexingMessage($item);
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
  public function deleteItems($ids = 'all', \SearchApiIndex $index = NULL) {
    $this->initEuropaSearchClient();

    if (is_string($ids) && ('all' == $ids)) {
      throw new \Exception('Unsupported action, a full index deletion is not supported yet by the Search API Europa Search module.');
    }

    $indexDeleteSender = new SearchApiEuropaSearchIndexSender($this->ESClientFactory, $this->options['ingestion_settings']['fallback_language']);
    $basicEntityType = $index->item_type;

    if ('multiple' != $basicEntityType) {
      // Deletion treatment for index that covers only one entity type.
      $this->deleteEntities($basicEntityType, $ids, $indexDeleteSender);

      return;
    }

    // Deletion treatment for index that covers multiple entity types.
    $entityInfos = array();
    // Organize the index item by entity types.
    foreach ($ids as $id) {
      list($entityType, $entityId) = explode('/', $id);
      if (!isset($entityInfos[$entityType])) {
        $entityInfos[$entityType] = array();
      }
      $entityInfos[$entityType][] = $entityId;
    }

    foreach ($entityInfos as $type => $entityInfo) {
      $this->deleteEntities($type, $entityInfo, $indexDeleteSender);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search(\SearchApiQueryInterface $query) {
    $this->initEuropaSearchClient();

    try {
      $searchSender = new SearchApiEuropaSearchSearchSender($this->ESClientFactory);
      $response = $searchSender->sendMessage($query);

      $responseParser = new SearchApiEuropaSearchSearchResponseParser($query);
      $searchResults = $responseParser->parseSearch($response);

      return $searchResults;
    }
    catch (ValidationException $ve) {
      $message = 'The submitted search query is invalid! The following validation errors has been detected: @errors';
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

    // Fields for the Indexing services settings.
    // Default values.
    $this->options['ingestion_settings'] += array(
      'ingestion_url_root' => '',
      'proxy_settings' => array(),
      'ingestion_api_key' => '',
      'ingestion_database' => '',
      'fallback_language' => language_default('language'),
    );

    $form['ingestion_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Ingestion services settings (Indexing requests)'),
    );

    // Fields for defining the proxy settings.
    $this->buildConnectionSettingsForm($form);

    // Settings specific to the requests.
    $form['ingestion_settings']['ingestion_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Registered API key'),
      '#description' => t('The Europa Search API key to use with any indexing requests.'),
      '#required' => TRUE,
      '#default_value' => $this->options['ingestion_settings']['ingestion_api_key'],
    );

    $form['ingestion_settings']['fallback_language'] = array(
      '#type' => 'textfield',
      '#title' => t('Fallback language in case of Neutral language content'),
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

    // Fields for the Search services settings.
    // Default values.
    $this->options['search_settings'] += array(
      'search_url_root' => '',
      'proxy_settings' => array(),
      'search_api_key' => '',
      'activate_database_filter' => FALSE,
    );

    $form['search_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Search API services settings (Search requests)'),
    );

    // Fields for defining the proxy settings.
    $this->buildConnectionSettingsForm($form, 'search_url_root', 'search_settings');

    // Settings specific to the requests.
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
      $formItem = $form['ingestion_settings']['ingestion_url_root'];
      form_error($formItem, t('The "@title" is not a valid url', array('@title' => $formItem['#title'])));
    }

    // Checks the Search Services URL root validity.
    $url_root = $values['search_settings']['search_url_root'];
    if (!$this->validateConfiguredUrl($url_root)) {
      $formItem = $form['search_settings']['search_url_root'];
      form_error($formItem, t('The "@title" is not a valid url', array('@title' => $formItem['#title'])));
    }

    // Secure other saved values: check_plain.
    $values['ingestion_settings']['ingestion_api_key'] = check_plain($values['ingestion_settings']['ingestion_api_key']);
    $values['ingestion_settings']['ingestion_database'] = check_plain($values['ingestion_settings']['ingestion_database']);
    $values['search_settings']['search_api_key'] = check_plain($values['search_settings']['search_api_key']);

    $this->validateAndSecureProxySettings($form, $values, 'ingestion_settings');
    $this->validateAndSecureProxySettings($form, $values, 'search_settings');
  }

  /**
   * Build the service connection settings used in the configuration form.
   *
   * @param array $form
   *   The service configuration form that must service connection fields.
   * @param string $urlRootKey
   *   The array key cf the form item containing the URL root.
   * @param string $proxySettingsKey
   *   The array key cf the form item containing the proxy settings.
   *
   * @see SearchApiEuropaSearchService::configurationForm()
   */
  protected function buildConnectionSettingsForm(array &$form, $urlRootKey = 'ingestion_url_root', $proxySettingsKey = 'ingestion_settings') {
    $form[$proxySettingsKey][$urlRootKey] = array(
      '#type' => 'textfield',
      '#title' => t('Europa Search Service URL'),
      '#description' => t('URL root (without the last slash) pointing to the the Europa Search Indexing services (Ingestion API).'),
      '#required' => TRUE,
      '#default_value' => $this->options[$proxySettingsKey][$urlRootKey],
    );

    $form[$proxySettingsKey]['proxy_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Proxy settings for accessing the services'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    // Default values.
    $proxySettings = $this->options[$proxySettingsKey]['proxy_settings'];

    $proxySettings += array(
      'configuration_type' => 'default',
      'custom_address' => '',
      'user_name' => '',
      'user_password' => '',
    );

    $form[$proxySettingsKey]['proxy_settings']['configuration_type'] = array(
      '#type' => 'select',
      '#title' => t('Configuration type'),
      '#description' => t('The type of proxy configuration to use for requests.'),
      '#required' => TRUE,
      '#options' => array(
        'default' => t("Host system's settings"),
        'custom' => t("Specific proxy's settings"),
        'none' => t('Bypass proxy'),
      ),
      '#default_value' => $proxySettings['configuration_type'],
    );

    $form[$proxySettingsKey]['proxy_settings']['custom_address'] = array(
      '#type' => 'textfield',
      '#title' => t('Proxy URL'),
      '#description' => t('The proxy\'s URL to use. Only mandatory and taken into account if the configuration type is "Specific proxy\'s settings"'),
      '#default_value' => $proxySettings['custom_address'],
    );

    $form[$proxySettingsKey]['proxy_settings']['user_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Proxy user name'),
      '#description' => t('The proxy\'s credentials user name. Only taken into account if the configuration type is "Specific proxy\'s settings"'),
      '#default_value' => $proxySettings['user_name'],
    );

    $form[$proxySettingsKey]['proxy_settings']['user_password'] = array(
      '#type' => 'textfield',
      '#title' => t('Proxy user password'),
      '#description' => t('The proxy\'s credentials user password. Only taken into account if the configuration type is "Specific proxy\'s settings"'),
      '#default_value' => $proxySettings['user_password'],
    );
  }

  /**
   * Validates and secure proxy related form values.
   *
   * @param array $form
   *   The Server configuration form.
   * @param array $values
   *   The inserted configuration values to validate.
   * @param string $proxySettingsKey
   *   The array key cf the value item containing the proxy settings.
   *
   * @see SearchApiEuropaSearchService::configurationFormValidate()
   */
  protected function validateAndSecureProxySettings(array $form, array &$values, $proxySettingsKey = 'ingestion_settings') {
    $proxySettings = &$values[$proxySettingsKey]['proxy_settings'];

    if ('custom' != $proxySettings['configuration_type']) {
      // If the settings imply the system's proxy or  nor pxy, no need to go on.
      // We just reset proxy related values.
      $proxySettings['custom_address'] = '';
      $proxySettings['user_name'] = '';
      $proxySettings['user_password'] = '';
      return;
    }

    // Secure text values.
    if (!empty($proxySettings['user_name'])) {
      $proxySettings['user_name'] = check_plain($proxySettings['user_name']);
    }

    if (!empty($proxySettings['user_password'])) {
      $proxySettings['user_password'] = check_plain($proxySettings['user_password']);
    }

    $formItem = $form[$proxySettingsKey]['proxy_settings']['custom_address'];
    $formTitle = $formItem['#title'];
    if (empty($proxySettings['custom_address'])) {
      $tParameters = array(
        '@title' => $formTitle,
        '@select' => $form[$proxySettingsKey]['proxy_settings']['configuration_type']['#title'],
      );
      form_error($formItem, t('The "@title" must be set if "@select" is "Specific proxy\'s settings"', $tParameters));

      return;
    }

    $proxyAddress = $proxySettings['custom_address'];
    $proxyUrl = parse_url($proxyAddress);
    $addressMessage = t('The "@title" is not a valid URL', array('@title' => $formTitle));
    if (!$proxyUrl || !isset($proxyUrl['scheme'])) {
      form_error($formItem, $addressMessage);

      return;
    }

    // We redefine the URL scheme in order to use the validate_url function
    // that support http and https only.
    $urlToValidate = str_replace($proxyUrl['scheme'] . '://', 'http://', $proxyAddress);
    if (!valid_url($urlToValidate)) {
      form_error($formItem, $addressMessage);

      return;
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
      'proxy' => $option['proxy_settings'],
    );

    $option = $this->options['search_settings'];
    $fullRoot = $option['search_url_root'];
    $searchSettings = array(
      'url_root' => $fullRoot,
      'api_key' => $option['search_api_key'],
      'proxy' => $option['proxy_settings'],
    );
    $clientConfiguration = array(
      'indexing_settings' => $indexingSettings,
      'search_settings' => $searchSettings,
      'services_settings' => array(
        'logger' => new Psr3DrupalLog('Search API Europa Search'),
        'log_level' => LogLevel::DEBUG,
      ),
    );

    $this->ESClientFactory = new EuropaSearch($clientConfiguration);
  }

  /**
   * Sends deletion message for entities of a certain type.
   *
   * @param string $entityType
   *   The type of the entities to delete.
   * @param array $entityIds
   *   The ids of the entities to delete.
   * @param \Drupal\search_api_europa_search\Index\SearchApiEuropaSearchIndexSender $indexDeleteSender
   *   The object that will send the deletion message to the
   *   Europa Search services.
   */
  protected function deleteEntities($entityType, array $entityIds, SearchApiEuropaSearchIndexSender $indexDeleteSender) {
    $entities = entity_load($entityType, $entityIds);
    $responses = array();
    foreach ($entities as $id => $entity) {
      $language = entity_language($entityType, $entity);
      $referenceToDelete = $this->getEuropaSearchReferenceValue($entityType, $id, $language);
      $response = $indexDeleteSender->sendDeletionMessage($referenceToDelete);
      $responses[] = $response->getReturnedString();
    }

    $list = implode(', ', $responses);
    watchdog('Search API Europa Search', 'The deleted items references are @list.', array('@list' => $list), WATCHDOG_INFO);
  }

}
