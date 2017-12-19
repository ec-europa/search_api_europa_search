<?php

namespace Drupal\search_api_europa_search\Index;

use Drupal\search_api_europa_search\MetadataBuilder;
use EC\EuropaSearch\Messages\Index\IndexWebContentMessage;
use EC\EuropaSearch\Messages\Index\DeleteIndexItemMessage;
use EC\EuropaSearch\EuropaSearch;

/**
 * Class SearchApiEuropaSearchIndex.
 *
 * Manages the indexing message coming from the Search API index.
 */
class IndexSender {

  /**
   * The EuropaSearch client factory managing the Europa Search connection.
   *
   * @var \EC\EuropaSearch\EuropaSearch
   */
  protected $clientFactory;

  /**
   * Code of the language to use in case of an 'und' indexed item.
   *
   * @var string
   */
  protected $fallbackLanguage;

  /**
   * SearchApiEuropaSearchIndex constructor.
   *
   * @param \EC\EuropaSearch\EuropaSearch $clientFactory
   *   The client factory used for the message sending.
   * @param string $fallbackLanguage
   *   The language code to use in case the indexed item has "und" as
   *   language; which is not supported by ES services.
   */
  public function __construct(EuropaSearch $clientFactory, $fallbackLanguage = 'en') {
    $this->clientFactory = $clientFactory;
    $this->fallbackLanguage = $fallbackLanguage;
  }

  /**
   * Sends the indexing message to the Europa Search services.
   *
   * @param array $indexedItem
   *   The entity data to sent for indexing.
   *
   * @return string
   *   The reference of the indexing element returned by the
   *   Europa Search service.
   *
   * @throws \Exception
   *   Raised if
   *   - The entity type is "file".
   *     The module does not support it now.
   *   - 'search_api_europa_search_reference' is not set for the indexed item.
   */
  public function sendIndexingMessage(array $indexedItem) {
    if (!isset($indexedItem['search_api_europa_search_reference'])) {
      throw new \Exception(t('The "search_api_europa_search_reference" field is missing.'));
    }

    $referenceArray = $indexedItem['search_api_europa_search_reference'];
    $entityType = $referenceArray['value']['entity_type'];

    if ('file' == $entityType) {
      throw new \Exception(t('The "@type" type is not supported by the module yet.', array('@type' => $entityType)));
    }

    $indexingMessage = $this->buildWebContentMessage($indexedItem);

    return $this->clientFactory->getIndexingApplication()->sendMessage($indexingMessage);
  }

  /**
   * Sends the index deletion message to the Europa Search services.
   *
   * @param string $referenceToDelete
   *   The reference of the index item to delete.
   *
   * @return bool
   *   True if the deletion is successful.
   *
   * @throws \Exception
   *   Raised if
   *   - The entity type is "file".
   *     The module does not support it now.
   *   - 'search_api_europa_search_reference' is not set for the indexed item.
   */
  public function sendDeletionMessage($referenceToDelete) {

    $deletionMessage = $this->buildIndexedItemDeletionMessage($referenceToDelete);

    return $this->clientFactory->getIndexingApplication()->sendMessage($deletionMessage);
  }

  /**
   * Adds a web content data to the indexing message to send.
   *
   * @param array $indexedItem
   *   The entity data to sent for indexing.
   *
   * @return \EC\EuropaSearch\Messages\Index\IndexWebContentMessage
   *   The message for a web content indexing.
   */
  protected function buildWebContentMessage(array $indexedItem) {
    $indexingMessage = new IndexWebContentMessage();

    // Set document id.
    $indexingMessage->setDocumentId($indexedItem['search_api_europa_search_reference']['value']['sent_reference']);
    unset($indexedItem['search_api_europa_search_reference']);

    // Set document language.
    $language = $this->fallbackLanguage;
    if (isset($indexedItem['search_api_language']) && (LANGUAGE_NONE != $indexedItem['search_api_language']['value'])) {
      $language = $indexedItem['search_api_language']['value'];
      unset($indexedItem['search_api_language']);
    }
    $indexingMessage->setDocumentLanguage($language);

    // Set document URL.
    if (isset($indexedItem['search_api_url'])) {
      $indexingMessage->setDocumentURI($indexedItem['search_api_url']['value']);
      unset($indexedItem['search_api_url']);
    }

    // Set document content.
    if (isset($indexedItem['search_api_viewed'])) {
      $indexingMessage->setDocumentContent($indexedItem['search_api_viewed']['value']);
      unset($indexedItem['search_api_viewed']);
    }

    // Sets the entity data.
    foreach ($indexedItem as $dataName => $data) {
      $metadata = $this->getEntityMetadata($dataName, $data);
      $indexingMessage->addMetadata($metadata);
    }

    return $indexingMessage;
  }

  /**
   * Build the deletion message for a specific index reference.
   *
   * @param string $referenceToDelete
   *   The ES reference to sent for deleting it from the index.
   *
   * @return \EC\EuropaSearch\Messages\Index\IndexedItemDeletionMessage
   *   The message for a index deletion.
   */
  protected function buildIndexedItemDeletionMessage($referenceToDelete) {
    $deletionMessage = new DeleteIndexItemMessage();
    $deletionMessage->setDocumentId($referenceToDelete);

    return $deletionMessage;
  }

  /**
   * Gets an entity metadata to add to the message.
   *
   * @param string $dataName
   *   The entity field name that will be used as metadata name.
   * @param array $data
   *   The entity field data that will be used to define the metadata values.
   *
   * @return \EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata
   *   The entity metadata to add to the message.
   *
   * @throws \Exception
   *   Raised if the entity data type is not supported by the message class.
   */
  protected function getEntityMetadata($dataName, array $data) {
    $dataType = search_api_extract_inner_type($data['type']);
    $dataValues = $data['value'];

    if (!is_array($dataValues)) {
      $dataValues = array($dataValues);
    }

    $metadataBuilder = new MetadataBuilder($dataName, $dataType, $dataValues);

    return $metadataBuilder->getMetadataObject();
  }

}
