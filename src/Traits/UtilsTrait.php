<?php

namespace Drupal\search_api_europa_search\Traits;

/**
 * Trait UtilsTrait.
 *
 * Proposes methods that can be rused though the module classes.
 *
 * @package Drupal\search_api_europa_search\Traits
 */
trait UtilsTrait {

  /**
   * Gets the Europa Search reference.
   *
   * @param string $entityType
   *   The type of the entity from which retrieving the reference.
   * @param string $entityId
   *   The id of the entity from which retrieving the reference.
   * @param string $entityLanguage
   *   The language of the entity from which retrieving the reference.
   *
   * @return string
   *   The reference value.
   */
  public function getEuropaSearchReferenceValue($entityType, $entityId, $entityLanguage) {
    return $entityType . '__' . $entityId . '__' . $entityLanguage;
  }

}
