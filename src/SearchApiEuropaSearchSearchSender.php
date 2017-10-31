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
  protected $searchApiQuery;

  /**
   * SearchApiEuropaSearchSearchSender constructor.
   *
   * @param SearchApiQueryInterface $query
   *   The Search API query object.
   */
  public function __construct(SearchApiQueryInterface $query) {
    $this->searchMessage = new SearchMessage();
    $this->searchApiQuery = $query;

    $this->buildMessageObject();
  }

  /**
   * Sends the built message to the Europa Search services.
   *
   * @param \EC\EuropaSearch\EuropaSearch $clientFactory
   *   The client factory used for the message sending.
   *
   * @return string
   *   The reference of the indexing element returned by the
   *   Europa Search service.
   */
  public function sendMessage(EuropaSearch $clientFactory) {
    return $clientFactory->getSearchApplication()->sendMessage($this->searchMessage);
  }

  /**
   * Builds the SearchMessage object.
   */
  public function buildMessageObject() {
    $searchOptions = $this->searchApiQuery->getOptions();
    $searchApiIndex = $this->searchApiQuery->getIndex();
    $sortDefinitions = $this->searchApiQuery->getSort();
    $indexedFieldsData = $searchApiIndex->getFields();

    // Builds the full text criteria of the query.
    $text = $this->buildFullTextSearchCriteria($this->searchApiQuery->getKeys(), $searchOptions);
    $this->searchMessage->setSearchedText($text);

    // The pagination starts to 1 for Europa Search services.
    $offset = 1;
    if (!empty($searchOptions['offset']) && ('0' != $searchOptions['offset'])) {
      $offset = ((int) $searchOptions['offset']) + 1;
    }
    $this->searchMessage->setPagination((int) $searchOptions['limit'], $offset);

    // Build the sort criteria for the query.
    $this->buildEuropaSearchSortCriteria($sortDefinitions, $indexedFieldsData);

    // Build the query itself, full text search does not belong to the query
    // (see buildFullTextSearchCriteria()).
    $this->buildEuropaSearchQuery($this->searchApiQuery->getFilter(), $indexedFieldsData);
  }

  /**
   * Build the full text search criteria.
   *
   * @param array $searchApiKeys
   *   The keys defined in the Search API query.
   * @param string $parse_mode
   *   The parse mode defined in the Search API query.
   *
   * @return string
   *   The full text criteria to use in the service request.
   */
  private function buildFullTextSearchCriteria(array $searchApiKeys, $parse_mode = '') {
    $conjunction = (isset($searchApiKeys['#conjunction']) && ('AND' == $searchApiKeys['#conjunction'])) ? '+' : '';
    $negation = !empty($searchApiKeys['#negation']);
    $values = array();

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
    $filters = $searchApiQuery->getFilters();
    foreach ($filters as $key => $searchApiFilter) {
      // TODO finalize implementation.
    }
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
