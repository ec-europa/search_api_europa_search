<?php

namespace Drupal\search_api_europa_search\Search;

/**
 * Class SearchApiOperator.
 *
 * Helper class offering mathode to work with Search API filter operator.
 */
class SearchApiEuropaSearchOperators {
  const EQUALS_TO = '=';
  const NOT_EQUALS_TO = '<>';
  const GREATER_THAN = '>';
  const GREATER_EQUALS_TO = '>=';
  const LESS_THAN = '<';
  const LESS_EQUALS_TO = '<=';
  const IS_EMPTY = 'empty';
  const IS_NOT_EMPTY = 'not empty';

  /**
   * Gets the list of Search API search operators.
   *
   * @return array
   *   The list of existing operators.
   */
  public static function getSearchApiOperatorList() {
    $class = new \ReflectionClass(__CLASS__);
    return $class->getConstants();
  }

  /**
   * Checks if a string is a Search API search operator.
   *
   * @param string $operatorValue
   *   The value to test.
   *
   * @return bool
   *   TRUE if the value is an operator; otherwise FALSE.
   */
  public static function isSearchApiOperator($operatorValue) {
    $operatorArray = self::getSearchApiOperatorList();

    return (in_array($operatorValue, $operatorArray));
  }

  /**
   * Checks if a string is a "Field Exist" operator.
   *
   * @param string $operatorValue
   *   The value to test.
   *
   * @return bool
   *   TRUE if the value is a "Field Exist" operator; otherwise FALSE.
   */
  public static function isExistsOperator($operatorValue) {
    $existOperator = array(
      self::IS_EMPTY,
      self::IS_NOT_EMPTY,
    );

    return (in_array($operatorValue, $existOperator));
  }

}
