<?php

namespace Drupal\search_api_europa_search;

use Drupal\search_api_europa_search\Traits\SearchApiEuropaSearchUtil;

/**
 * Search API data alteration callback that adds an reference for all items.
 */
class SearchApiEuropaSearchAlterAddReference extends \SearchApiAbstractAlterCallback {

  use SearchApiEuropaSearchUtil;

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
        'sent_reference' => $this->getEuropaSearchReferenceValue($entityType, $entityId, $language),
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

}
