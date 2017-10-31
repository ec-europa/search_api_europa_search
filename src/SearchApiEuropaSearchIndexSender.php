<?php

use EC\EuropaSearch\Messages\Index\IndexingWebContent;
use EC\EuropaSearch\EuropaSearch;

/**
 * Class SearchApiEuropaSearchIndex.
 *
 * Manages the indexing message coming from the Search API index.
 */
class SearchApiEuropaSearchIndexSender {

  /**
   * The message to build and to send through the client.
   *
   * @var EC\EuropaSearch\Messages\Index\AbstractIndexingMessage
   */
  protected $indexingMessage;

  /**
   * Type of the entity related to the indexing message.
   *
   * @var string
   */
  protected $entityType;

  /**
   * SearchApiEuropaSearchIndex constructor.
   *
   * @param array $indexedItem
   *   The entity data to sent for indexing.
   * @param string $fallbackLanguage
   *   The language code to use in case the indexed item has "und" as
   *   language; which is not supported by ES services.
   *
   * @throws Exception
   *   It is thrown if the entity type is "file".
   *   The module does not support it now.
   */
  public function __construct(array $indexedItem, $fallbackLanguage = 'en') {
    if (!isset($indexedItem['search_api_europa_search_reference'])) {
      throw new \Exception(t('The "search_api_europa_search_reference" field is missing.'));
    }

    $referenceArray = $indexedItem['search_api_europa_search_reference'];
    $this->entityType = $referenceArray['value']['entity_type'];

    if ('file' == $this->entityType) {
      throw new \Exception(t('The "@type" type is not supported by the module yet.', array('@type' => $this->entityType)));
    }
    $this->buildWebContentMessage($indexedItem, $fallbackLanguage);
  }

  /**
   * Sends the built message to the Europa Search services.
   *
   * @param \EC\EuropaSearch\EuropaSearch $clientFactory
   *   The client factory used for the message sending.
   *
   * @return string
   *   The reference of the indexing element returned by the
   *   Europa Search service.
   */
  public function sendMessage(EuropaSearch $clientFactory) {
    return $clientFactory->getIndexingApplication()->sendMessage($this->indexingMessage);
  }

  /**
   * Adds a web content data to the indexing message to send.
   *
   * @param array $indexedItem
   *   The entity data to sent for indexing.
   * @param string $fallbackLanguage
   *   The language code to use in case the indexed item has "und" as
   *   language; which is not supported by ES services.
   */
  protected function buildWebContentMessage(array $indexedItem, $fallbackLanguage = 'en') {
    $this->indexingMessage = new IndexingWebContent();
    // Set document id.
    $this->indexingMessage->setDocumentId($indexedItem['search_api_europa_search_reference']['value']['sent_reference']);
    unset($indexedItem['search_api_europa_search_reference']);

    // Set document language.
    $language = $fallbackLanguage;
    if (isset($indexedItem['search_api_language']) && (LANGUAGE_NONE != $indexedItem['search_api_language']['value'])) {
      $language = $indexedItem['search_api_language']['value'];
      unset($indexedItem['search_api_language']);
    }
    $this->indexingMessage->setDocumentLanguage($language);

    // Set document URL.
    if (isset($indexedItem['search_api_url'])) {
      $this->indexingMessage->setDocumentURI($indexedItem['search_api_url']['value']);
      unset($indexedItem['search_api_url']);
    }

    // Set document content.
    if (isset($indexedItem['search_api_viewed'])) {
      $this->indexingMessage->setDocumentContent($indexedItem['search_api_viewed']['value']);
      unset($indexedItem['search_api_viewed']);
    }

    // Sets the entity data.
    foreach ($indexedItem as $dataName => $data) {
      $this->addEntityMetadata($dataName, $data);
    }
  }

  /**
   * Build the deletion message for a specific index reference.
   *
   * @param string $referenceToDelete
   *   The ES reference to sent for deleting it from the index.
   */
  protected function buildIndexedItemDeleteMessage($referenceToDelete) {
    // TODO: Implement after the closure of the ticket SEARCH-2346.
  }

  /**
   * Adds an entity metadata to the message.
   *
   * @param string $dataName
   *   The entity field name that will be used as metadata name.
   * @param array $data
   *   The entity field data that will be used to define the metadata values.
   *
   * @throws Exception
   *   Rasied if the entity data type is not supported by the message class.
   */
  protected function addEntityMetadata($dataName, array $data) {
    $dataType = $data['type'];
    $dataValues = $data['value'];
    if (!is_array($dataValues)) {
      $dataValues = array($dataValues);
    }

    $metadataBuilder = new SearchApiEuropaSearchMetadataBuilder($dataName, $dataType, $dataValues);
    $this->indexingMessage->addMetadata($metadataBuilder->getMetadataObject());
  }

}
