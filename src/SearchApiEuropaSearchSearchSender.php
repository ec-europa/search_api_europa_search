<?php

use EC\EuropaSearch\Messages\Search\SearchMessage;
use EC\EuropaSearch\EuropaSearch;

/**
 * Class SearchApiEuropaSearchSearchSender.
 *
 * Manages the search message coming from the Search API index.
 */
class SearchApiEuropaSearchSearchSender {

  /**
   * The message to build and to send through the client.
   *
   * @var EC\EuropaSearch\Messages\Search\SearchMessage
   */
  protected $searchMessage;

  /**
   * The query communicated by the Search API module.
   *
   * @var SearchApiQueryInterface
   */
  protected $clientFactory;

  /**
   * SearchApiEuropaSearchSearchSender constructor.
   *
   * @param \EC\EuropaSearch\EuropaSearch $clientFactory
   *   The client factory used for the message sending.
   */
  public function __construct(EuropaSearch $clientFactory) {
    $this->searchMessage = new SearchMessage();
    $this->clientFactory = $clientFactory;
  }

  /**
   * Sends the built message to the Europa Search services.
   *
   * @param SearchApiQueryInterface $query
   *   The Search API query object.
   *
   * @return string
   *   The reference of the indexing element returned by the
   *   Europa Search service.
   */
  public function sendMessage(SearchApiQueryInterface $query) {
    $this->buildMessageObject($query);
    return $this->clientFactory->getSearchApplication()->sendMessage($this->searchMessage);
  }

  /**
   * Builds the SearchMessage object.
   *
   * @param SearchApiQueryInterface $query
   *   The Search API query object.
   */
  public function buildMessageObject(SearchApiQueryInterface $query) {
    $searchOptions = $query->getOptions();
    $searchApiIndex = $query->getIndex();
    $sortDefinitions = $query->getSort();
    $indexedFieldsData = $searchApiIndex->getFields();

    // Builds the full text criteria of the query.
    $text = $this->buildFullTextSearchCriteria($query->getKeys(), $searchOptions);
    $this->searchMessage->setSearchedText(check_plain($text));

    // The pagination starts to 1 for Europa Search services.
    $offset = 1;
    $limit = intval($searchOptions['limit']);
    if (isset($searchOptions['offset']) && ('0' != $searchOptions['offset'])) {
      $offset = (intval($searchOptions['offset'])) + 1;
      $limit += 1;
    }
    $this->searchMessage->setPagination($limit, $offset);

    // Build the sort criteria for the query.
    $this->buildEuropaSearchSortCriteria($sortDefinitions, $indexedFieldsData);

    // Build the highlighting settings.
    if (!empty($searchOptions['europa_search_highlight_settings'])) {
      $highLightSettings = $searchOptions['europa_search_highlight_settings'];
      $this->searchMessage->setHighLightParameters($highLightSettings['highlight_regex'], $highLightSettings['highlight_limit']);
    }

    // Build the query itself, full text search does not belong to the query
    // (see buildFullTextSearchCriteria()).
    $this->buildEuropaSearchQuery($query, $indexedFieldsData);
  }

  /**
   * Builds the full text search criteria.
   *
   * @param array|string $searchApiKeys
   *   The key(s) defined in the Search API query.
   * @param string $parse_mode
   *   The parse mode defined in the Search API query.
   *
   * @return string
   *   The full text criteria to use in the service request.
   */
  private function buildFullTextSearchCriteria($searchApiKeys, $parse_mode = '') {
    if (is_array($searchApiKeys)) {
      return $this->buildFulltextSearchOnArray($searchApiKeys, $parse_mode);
    }

    if ('direct' === $parse_mode) {
      return check_plain($searchApiKeys);
    }

    $negation = !empty($searchApiKeys['#negation']);

    return (TRUE === $negation ? '- ' : '') . $searchApiKeys;
  }

  /**
   * Build the full text search criteria based on multiple keys.
   *
   * @param array $searchApiKeys
   *   The key(s) defined in the Search API query.
   * @param string $parse_mode
   *   The parse mode defined in the Search API query.
   *
   * @return string
   *   The full text criteria to use in the service request.
   */
  private function buildFulltextSearchOnArray(array $searchApiKeys, $parse_mode = '') {
    $conjunction = '';
    if (isset($searchApiKeys['#conjunction']) && ('AND' == $searchApiKeys['#conjunction'])) {
      $conjunction = '+';
    }

    $negation = !empty($searchApiKeys['#negation']);

    $values = array();
    foreach ($searchApiKeys as $key => $value) {
      if ((!element_child($key)) || empty($value)) {
        continue;
      }
      $values[] = $this->buildFullTextSearchCriteria($value);
    }

    if (!empty($values)) {
      return (TRUE === $negation ? '- ' : '') . implode(" {$conjunction} ", $values);
    }

    return '';
  }

  /**
   * Builds the Europa Search query based on the Search API filters.
   *
   * @param SearchApiQueryInterface $searchApiQuery
   *   The query sent by Search API.
   * @param array $indexedFields
   *   The fields that are indexed in Search API.
   */
  protected function buildEuropaSearchQuery(SearchApiQueryInterface $searchApiQuery, array $indexedFields) {
    $builder = new SearchApiEuropaSearchQueryBuilder($searchApiQuery, $indexedFields);
    $query = $builder->getQuery();
    $this->searchMessage->setQuery($query);
  }

  /**
   * Builds the Europa Search query based on the Search API filters.
   *
   * @param array $sortDefinitions
   *   Array wih the different sort definitions as defined in the query.
   * @param array $indexedFields
   *   Array wih the indexed fields as defined in the Search API index.
   */
  protected function buildEuropaSearchSortCriteria(array $sortDefinitions, array $indexedFields) {
    $sortFields = array_keys($sortDefinitions);
    $sortField = reset($sortFields);

    if (('search_api_relevance' != $sortField) && (isset($indexedFields[$sortField]))) {
      $fieldDefinition = $indexedFields[$sortField];
      $metadataBuilder = new SearchApiEuropaSearchMetadataBuilder($sortField, $fieldDefinition['type']);
      $sortDirection = $sortDefinitions[$sortField];
      $this->searchMessage->setSortCriteria($metadataBuilder->getMetadataObject(), $sortDirection);
    }
  }

}
