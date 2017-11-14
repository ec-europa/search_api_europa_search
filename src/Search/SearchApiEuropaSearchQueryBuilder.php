<?php

namespace Drupal\search_api_europa_search\Search;

use Drupal\search_api_europa_search\SearchApiEuropaSearchMetadataBuilder;
use EC\EuropaSearch\Messages\Components\Filters\Queries\BooleanQuery;
use EC\EuropaSearch\Messages\Components\Filters\BoostableFilter;
use EC\EuropaSearch\Messages\Components\Filters\Clauses\FieldExistsClause;
use EC\EuropaSearch\Messages\Components\Filters\Clauses\TermClause;
use EC\EuropaSearch\Messages\Components\Filters\Clauses\TermsClause;
use EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata;
use EC\EuropaSearch\Messages\Components\Filters\Clauses\RangeClause;

/**
 * Class SearchApiEuropaSearchQueryBuilder.
 *
 * Builds Europa Search Query based on a Search API one.
 */
class SearchApiEuropaSearchQueryBuilder {

  private $indexedFields;

  private $searchApiQuery;

  private $europaSearchQuery;

  private $metadataBuilder;

  /**
   * SearchApiEuropaSearchQueryBuilder constructor.
   *
   * @param \SearchApiQueryInterface $searchApiQuery
   *   The query supplied by Search API.
   * @param array $indexedFields
   *   Array of data about fields indexed in Search API.
   */
  public function __construct(\SearchApiQueryInterface $searchApiQuery, array $indexedFields) {
    $this->indexedFields = $indexedFields;
    $this->searchApiQuery = $searchApiQuery;
    $this->europaSearchQuery = new BooleanQuery();
    $this->metadataBuilder = new SearchApiEuropaSearchMetadataBuilder();

    // Builds the Europa Search query.
    $filter = $this->searchApiQuery->getFilter();
    $this->buildQuery($filter, $this->europaSearchQuery);
  }

  /**
   * Gets the built query.
   *
   * @return \EC\EuropaSearch\Messages\Components\Filters\Queries\BooleanQuery
   *   The query fully built.
   */
  public function getQuery() {
    return $this->europaSearchQuery;
  }

  /**
   * Builds the Europa Search query based on the defined Search API filters.
   *
   * @param \SearchApiQueryFilter $searchApiFilter
   *   The object defining the Search API filters.
   * @param \EC\EuropaSearch\Messages\Components\Filters\BoostableFilter $parentQuery
   *   The Europa Search query to build.
   */
  protected function buildQuery(\SearchApiQueryFilter $searchApiFilter, BoostableFilter $parentQuery) {
    $subFilters = $searchApiFilter->getFilters();
    $conjunction = $searchApiFilter->getConjunction();

    $queriesConjunctions = array(
      'OR' => 'addShouldFilterQuery',
      'AND' => 'addMustFilterQuery',
      '-' => 'addMustNotFilterQuery',
    );

    foreach ($subFilters as $key => $filter) {
      if (is_array($filter)) {
        // Add clause.
        $this->buildClause($filter, $conjunction, $parentQuery);
        continue;
      }

      // Treat SearchApiQueryFilter contained in the currently treated
      // sub-filter.
      $subQuery = new BooleanQuery();
      $this->buildQuery($filter, $subQuery);
      $parentQuery->{$queriesConjunctions[$conjunction]}($subQuery);
    }
  }

  /**
   * Builds a Europa search filter clause & adds it to the query or the filter.
   *
   * @param array $searchApiClause
   *   The filter clause coming from Search API.
   * @param string $conjunction
   *   The conjunction defined in the Search API filter where the current
   *   clause is.
   * @param \EC\EuropaSearch\Messages\Components\Filters\BoostableFilter $parentQuery
   *   The BooleanQuery that must use this clause.
   *
   * @throws \Exception
   *    Rasied if the filter clause operator is unknown.
   */
  protected function buildClause(array $searchApiClause, $conjunction, BoostableFilter $parentQuery) {
    $normalizedClause = $this->normalizeFilter($searchApiClause, $this->indexedFields);

    $operator = $normalizedClause['filter_operator'];
    if (!isset($normalizedClause['filter_value'])) {
      $this->setEuropaSearchFieldExistClause($normalizedClause, $conjunction, $parentQuery);
      return;
    }

    if (!SearchApiEuropaSearchOperators::isSearchApiOperator($operator)) {
      $fieldName = $normalizedClause['field_name'];
      throw new Exception(t('Undefined filter clause operator :operator for :field_name field!',
        array(':operator' => $normalizedClause['filter_operator'], ':field_name' => $fieldName)));
    }

    $impliedMetadata = $normalizedClause['filter_metadata'];
    $clauseValue = $normalizedClause['filter_value'];
    $methodName = $this->getEuropaSearchConjunctionClauseMethod($conjunction, $normalizedClause['field_name']);
    $operator = $normalizedClause['filter_operator'];

    if (SearchApiEuropaSearchOperators::NOT_EQUALS_TO == $operator) {
      $clause = $this->getEuropaSearchEqualsClause($impliedMetadata, $clauseValue);
      $clause->setBoost($normalizedClause['filter_boost']);
      $parentQuery->addMustNotFilterClause($clause);

      return;
    }

    if (SearchApiEuropaSearchOperators::EQUALS_TO == $operator) {
      $clause = $this->getEuropaSearchEqualsClause($impliedMetadata, $clauseValue);
    }

    if (!isset($clause)) {
      $clause = $this->getEuropaSearchRangeClause($impliedMetadata, $clauseValue, $operator);
    }

    $clause->setBoost($normalizedClause['filter_boost']);
    $parentQuery->{$methodName}($clause);
  }

  /**
   * Gets a RangeClause implementation for the Search API "Range" filter.
   *
   * @param \EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata $impliedMetadata
   *   The metadata implied in the filter.
   * @param mixed $clauseValue
   *   The value to set for the filter.
   * @param string $operator
   *   The Search API operator set with the clause.
   *
   * @return \EC\EuropaSearch\Messages\Components\Filters\Clauses\RangeClause
   *   The RangeClause object to use in the query.
   */
  protected function getEuropaSearchRangeClause(AbstractMetadata $impliedMetadata, $clauseValue, $operator) {
    $clause = new RangeClause($impliedMetadata);

    switch ($operator) {
      case SearchApiEuropaSearchOperators::LESS_THAN:
        $clause->setLowerBoundaryExcluded($clauseValue);
        break;

      case SearchApiEuropaSearchOperators::LESS_EQUALS_TO:
        $clause->setLowerBoundaryIncluded($clauseValue);
        break;

      case SearchApiEuropaSearchOperators::GREATER_THAN:
        $clause->setUpperBoundaryExcluded($clauseValue);
        break;

      case SearchApiEuropaSearchOperators::GREATER_EQUALS_TO:
        $clause->setUpperBoundaryIncluded($clauseValue);
        break;
    }

    return $clause;
  }

  /**
   * Gets a AbstractClause implementation for the Search API "Equals" filter.
   *
   * @param \EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata $impliedMetadata
   *   The metadata implied in the filter.
   * @param mixed $clauseValue
   *   The value to set for the filter.
   *
   * @return \EC\EuropaSearch\Messages\Components\Filters\Clauses\TermClause|\EC\EuropaSearch\Messages\Components\Filters\Clauses\TermsClause
   *   The corresponding AbstractClause implementation; TermsClause is an array;
   *   otherwise TermClause.
   */
  protected function getEuropaSearchEqualsClause(AbstractMetadata $impliedMetadata, $clauseValue) {
    if (!is_array($clauseValue)) {
      $clause = new TermClause($impliedMetadata);
      $clause->setTestedValue($clauseValue);

      return $clause;
    }

    $clause = new TermsClause($impliedMetadata);
    $clause->setTestedValues($clauseValue);

    return $clause;
  }

  /**
   * Sets the Europa Search FieldExistClause based on the Search API clause.
   *
   * @param array $normalizedClause
   *   The normalized version of the Search API filter clause.
   * @param string $conjunction
   *   The Search API conjunction related to the clause.
   * @param \EC\EuropaSearch\Messages\Components\Filters\BoostableFilter $parentQuery
   *   The Europa Search query object elated to the clause to set.
   */
  protected function setEuropaSearchFieldExistClause(array $normalizedClause, $conjunction, BoostableFilter $parentQuery) {
    $clause = new FieldExistsClause($normalizedClause['filter_metadata']);

    if (SearchApiEuropaSearchOperators::IS_EMPTY == $normalizedClause['filter_operator']) {
      // A search api clause "is empty" is the equivalent of "must_not" query
      // with "FilterExist" clause.
      $parentQuery->addMustNotFilterClause($clause);
      return;
    }

    $methodName = $this->getEuropaSearchConjunctionClauseMethod($conjunction, $normalizedClause['field_name']);
    $parentQuery->{$methodName}($clause);
  }

  /**
   * Get the BooleanQuery method name corresponding to the conjunction.
   *
   * @param string $conjunction
   *   The Search API conjunction from which deducing the
   *   BooleanQuery method name.
   * @param string $fieldName
   *   The field name for which a clause is set in the BoostableFilter
   *   implementation.
   *
   * @return string
   *   The corresponding BooleanQuery method name.
   *
   * @throws \Exception
   *   Rasied if the conjunction is not linked to a BooleanQuery method name.
   */
  protected function getEuropaSearchConjunctionClauseMethod($conjunction, $fieldName) {
    $clausesConjunctions = array(
      'OR' => 'addShouldFilterClause',
      'AND' => 'addMustFilterClause',
      '-' => 'addMustNotFilterClause',
    );

    if (isset($clausesConjunctions[$conjunction])) {
      return $clausesConjunctions[$conjunction];
    }

    throw new \Exception(t('Undefined conjunction :conjunction for :field_name field!',
      array(':conjunction' => $conjunction, ':field_name' => $fieldName)));
  }

  /**
   * Normalizes the filter info (clause) coming from the Search API query.
   *
   * @param array $filterInfo
   *   The filter info to normalize.
   * @param array $index_fields
   *   The list of fields that indexed in Search API.
   *
   * @return array
   *   Array with the normalized filter info; with as items:
   *   - 'field_name': The field name implied in the filter;
   *   - 'field_type': The field type implied in the filter;
   *   - 'filter_value': (optional) The field value implied in the filter.
   *     It is set if the operator is a mathematical operator;
   *   - 'filter_operator': The operator implied in the filter;
   *   - 'filter_boost': The boost weight of the filter;
   *   - 'filter_metadata': The EuropaSearch Metadata implied in the filter;
   */
  protected function normalizeFilter(array $filterInfo, array $index_fields) {
    $fieldName = $filterInfo[0];
    $filterOperator = $filterInfo[1];
    $fieldValue = NULL;

    if (3 == count($filterInfo)) {
      $fieldValue = $filterInfo[1];
      $filterOperator = $filterInfo[2];
    }

    $indexInfo = $index_fields[$fieldName];
    $dataType = $indexInfo['type'];
    $filterOperator = str_replace('!=', SearchApiEuropaSearchOperators::NOT_EQUALS_TO, $filterOperator);
    $relatedMetaData = $this->metadataBuilder->convertField($fieldName, $dataType);

    $normalizedFilter = array(
      'field_name' => $fieldName,
      'field_type' => $dataType,
      'filter_operator' => $filterOperator,
      'filter_boost' => $indexInfo['boost'],
      'filter_metadata' => $relatedMetaData,
    );

    if (SearchApiEuropaSearchOperators::isExistsOperator($filterOperator)) {
      // If the operator tests if the field exists, no need to add the value to
      // the returned array then.
      return $normalizedFilter;
    }

    if (is_null($fieldValue)) {
      $normalizedFilter['filter_operator'] = SearchApiEuropaSearchOperators::IS_EMPTY;
      if (SearchApiEuropaSearchOperators::NOT_EQUALS_TO) {
        $normalizedFilter['filter_operator'] = SearchApiEuropaSearchOperators::IS_NOT_EMPTY;
      }

      // If the value is null, then we tests if the field is set or not, no
      // need to add the value to the returned array then.
      return $normalizedFilter;
    }

    $fieldValue = $this->formatFilterValue($fieldValue, $dataType);
    $normalizedFilter['filter_value'] = $fieldValue;

    return $normalizedFilter;
  }

  /**
   * Format a value for filtering on a field of a specific type.
   *
   * @param mixed $value
   *   The value to format.
   * @param string $type
   *   The Search API field type associated to the value.
   *
   * @return mixed
   *   The formatted value.
   */
  protected function formatFilterValue($value, $type) {
    switch ($type) {
      case 'boolean':

        return boolval($value);

      case 'float':

        return floatval($value);

      case 'integer':

        return intval($value);

      case 'date':
        $value = is_numeric($value) ? (int) $value : strtotime($value);
        $dateTime = new \DateTime('@' . $value);

        return $dateTime->format('d-m-Y H:i:s');

      default:
        return check_plain($value);
    }
  }

}
