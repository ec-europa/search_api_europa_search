<?php

/**
 * Search API data alteration callback that adds an reference for all items.
 */
class SearchApiEuropaSearchAlterAddReference extends SearchApiAbstractAlterCallback {

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    foreach ($items as &$item) {
      $entityType = $this->index->datasource()->getEntityType();
      $entityId = $this->index->datasource()->getItemId($item);

      // Multi type case.
      if (empty($entityType)) {
        list($entityType, $entityId) = explode('/', $entityId);
      }

      $language = (entity_language($entityType, $item)) ?: LANGUAGE_NONE;

      $entityReference = array(
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'entity_language' => $language,
        'sent_reference' => self::getEuropaSearchReferenceValue($entityType, $entityId, $language),
      );

      $item->search_api_europa_search_reference = $entityReference;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    return array(
      'search_api_europa_search_reference' => array(
        'label' => t('Europa Search reference'),
        'description' => t('A special reference that identifies the item in the Europa Search index.'),
        'type' => 'string',
      ),
    );
  }

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
  public static function getEuropaSearchReferenceValue($entityType, $entityId, $entityLanguage) {
    return $entityType . '__' . $entityId . '__' . $entityLanguage;
  }

}
