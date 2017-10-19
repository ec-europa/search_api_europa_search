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
      $entityReference = $entityType;
      // Use double underscore because some language code can contain one like
      // "fr_BE" or "nl_BE".
      $entityReference .= '__' . $this->index->datasource()->getItemId($item);
      $language = (entity_language($entityType, $item))?:LANGUAGE_NONE;
      if ($language) {
        $entityReference .= '__' . $language;
      }
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
        'type' => 'web_content',
      ),
    );
  }

}
