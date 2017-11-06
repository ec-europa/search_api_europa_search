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
   * @var SearchApiQueryInterface
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
        'excerpt' => $result->getResultSummary(),
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
    $indexedField = $this->searchApiQuery->getIndex()->getFields();

    foreach ($indexedField as $fieldName => $fieldInfo) {
      $dataType = search_api_extract_inner_type($fieldInfo['type']);

      $comparableName = str_replace(':', '_', $fieldName);
      $comparableName = strtoupper($comparableName);

      if (isset($resultMetadata[$comparableName])) {
        $fieldValue = $resultMetadata[$comparableName];
        if ('date' == $dataType) {
          $fieldValue = $this->formatIsoDate($fieldValue);
        }

        $fields[$fieldName] = array(
          '#value' => $fieldValue,
        );

        if ($this->isStringDataType($dataType) && $this->isTextFormatProcessorActive()) {
          // Deactivation of the value sanitation in favor of
          // the "search_api_europa_search_processor" process.
          $fields[$fieldName]['#sanitize_callback'] = FALSE;
        }
      }
    }

    return $fields;
  }

  /**
   * Formats ISO date into timestamp.
   *
   * @param array|string $fieldValue
   *   The date value to format.
   *
   * @return array|string
   *   The formatted value.
   */
  protected function formatIsoDate($fieldValue) {
    if (empty($fieldValue)) {
      return $fieldValue;
    }

    if (is_array($fieldValue)) {
      $formattedValues = array();
      foreach ($fieldValue as $rawValue) {
        $formattedValues[] = date("U", strtotime($rawValue));
      }

      return $formattedValues;
    }

    return date("U", strtotime($fieldValue));
  }

  /**
   * Checks if the text format processor is enabled.
   *
   * @return bool
   *   True if the "europa_search_text_format_enabled" is TRUE and the "Filter"
   *   module is enabled.
   */
  protected function isTextFormatProcessorActive() {
    if (!module_exists('filter')) {
      return FALSE;
    }

    return $this->searchApiQuery->getOption('europa_search_text_format_enabled');
  }

  /**
   * Checks if the inner data type is of "string" type.
   *
   * By default, "", "", "" are of string type.
   *
   * @param string $dataType
   *   The data type to check.
   *
   * @return bool
   *   TRUE, if the data type is of "string" type; otherwise FALSE.
   *
   * @see search_api_extract_inner_type()
   */
  protected function isStringDataType($dataType) {
    $stringTypes = &drupal_static(__FUNCTION__, array());

    if (empty($stringTypes)) {
      $stringTypes = array(
        'text',
        'string',
        'uri',
      );
      $typeInfo = search_api_get_item_type_info();

      if (!empty($typeInfo)) {
        foreach ($typeInfo as $type => $info) {
          if (isset($typeInfo['fallback']) && ('string' == $typeInfo['fallback'])) {
            $stringTypes[] = $type;
          }
        }
      }
    }

    return in_array($dataType, $stringTypes);
  }

}
