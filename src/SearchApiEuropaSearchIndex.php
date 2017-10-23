<?php

use EC\EuropaSearch\Messages\Index\IndexingWebContent;

/**
 * Class SearchApiEuropaSearchIndex.
 *
 * Manages the indexing request coming from the Search API index.
 */
class SearchApiEuropaSearchIndex {

  /**
   * The message to build and to send through the client.
   *
   * @var EC\EuropaSearch\Messages\Index\IndexingWebContent
   */
  private $indexingMessage;

  /**
   * SearchApiEuropaSearchIndex constructor.
   *
   * @param string $entityType
   *   The type of the entity to sent for indexing.
   *
   * @throws Exception
   *   It is thrown if the entity type is "file".
   *   The mdoule does not support it now.
   */
  public function __construct($entityType = 'node') {
    if ('file' != $entityType) {
      $this->indexingMessage = new IndexingWebContent();
    }
    throw new Exception(t('The "@type" type is not supported by the module yet.', array('@type' => $entityType)));
  }

  /**
   * Adds an entity data to the indexing message to send.
   *
   * @param string $dataName
   *   The entity data name like the field name.
   * @param mixed $dataValue
   *   The entity data value to add.
   * @param string $dataType
   *   The Search API data type.
   */
  protected function addEntityData($dataName, $dataValue, $dataType) {
    // TODO Implement method.
  }

}
