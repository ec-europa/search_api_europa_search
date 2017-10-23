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
      $referenceToSend = $entityType;
      $referenceToSend .= '__' . $entityId;
      $entityReference = array(
        'entity_type' => $entityType,
        'entity_id' => $entityId,
      );

      $language = (entity_language($entityType, $item)) ?: LANGUAGE_NONE;
      if ($language) {
        $entityReference['entity_language'] = $language;
        $referenceToSend .= '__' . $language;
      }
      $entityReference['sent_reference'] = $referenceToSend;
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

}
