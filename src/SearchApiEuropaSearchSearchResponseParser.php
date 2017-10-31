<?php

use EC\EuropaSearch\Messages\Search\SearchResponse;
use EC\EuropaSearch\Messages\Search\SearchResult;

/**
 * Class SearchApiEuropaSearchSearchResponseParser.
 *
 * Parses search responses coming from the Eruopa Search services client.
 */
class SearchApiEuropaSearchSearchResponseParser {

  /**
   * The Search API index related to the Search related responses objects.
   *
   * @var SearchApiIndex
   */
  private $searchApiQuery;

  /**
   * SearchApiEuropaSearchSearchResponseParser constructor.
   *
   * @param SearchApiQueryInterface $query
   *   The Search Api query related to the responses to treat.
   */
  public function __construct(SearchApiQueryInterface $query) {
    $this->searchApiQuery = $query;
  }

  /**
   * Parses a SearchResponse object into an array supported by Search API.
   *
   * @param EC\EuropaSearch\Messages\Search\SearchResponse $response
   *   The Search response object to parse.
   *
   * @return array
   *   Array of results data as Search API module excepts it.
   */
  public function parseSearch(SearchResponse $response) {
    $totalResults = $response->getTotalResults();
    $search_results = array('result count' => $totalResults);

    $results = $response->getResults();

    foreach ($results as $result) {
      $reference = $result->getResultReference();
      list($entityType, $entityId, $actualEntityLang) = explode('__', $reference);

      $fields = $this->parseSearchResultMetadata($result);

      $search_results['results'][$entityId] = array(
        'id' => $entityId,
        'score' => $result->getSortingWeight(),
        'fields' => $fields,
      );
    }

    return $search_results;
  }

  /**
   * Parse metadata of the search result to retrieve Search API fields.
   *
   * @param EC\EuropaSearch\Messages\Search\SearchResult $result
   *   The search results where to find the metadata.
   *
   * @return array
   *   Array of fields as defined in Search API with their values.
   */
  public function parseSearchResultMetadata(SearchResult $result) {
    $resultMetadata = $result->getResultMetadata();
    $fields = array();
    if (empty($resultMetadata)) {
      return $fields;
    }

    foreach ($resultMetadata as $key => $data) {
      // TODO: Implement the mapping mechanic.
    }

    return $fields;
  }

}
