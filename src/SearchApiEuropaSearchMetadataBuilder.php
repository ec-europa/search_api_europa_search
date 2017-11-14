<?php

namespace Drupal\search_api_europa_search;

use EC\EuropaSearch\Messages\Components\DocumentMetadata\DateMetadata;

/**
 * Class SearchApiEuropaSearchMetadataBuilder.
 */
class SearchApiEuropaSearchMetadataBuilder {

  /**
   * The currently built metadata object.
   *
   * @var \EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata
   */
  private $metadataObject;

  /**
   * SearchApiEuropaSearchMetadataBuilder constructor.
   *
   * @param string $fieldName
   *   [optional] The name of the field on which the metadata is based.
   *   It is the name as defined in the Search APi index.
   * @param string $fieldType
   *   [optional] The type of the field on which the metadata is based.
   *   It is the name as defined in the Search APi index.
   * @param array $metadataValues
   *   [optional] Array of values to set.
   *
   * @throws Exception
   *   Raised if the entity data type is not supported by the message class.
   */
  public function __construct($fieldName = '', $fieldType = 'string', array $metadataValues = array()) {
    if (!empty($fieldName)) {
      $this->convertField($fieldName, $fieldType, $metadataValues);
    }
  }

  /**
   * Converts a Search APi field into a AbstractMetadata stored in the builder.
   *
   * @param string $fieldName
   *   The name of the field on which the metadata is based.
   *   It is the name as defined in the Search APi index.
   * @param string $fieldType
   *   The type of the field on which the metadata is based.
   *   It is the name as defined in the Search APi index.
   * @param array $metadataValues
   *   [optional] Array of values to set.
   *
   * @return \EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata
   *   The AbstractMetadata extension object.
   *
   * @throws \Exception
   *   Raised if the entity data type is not supported by the message class.
   */
  public function convertField($fieldName, $fieldType, array $metadataValues = array()) {
    $methodMapping = array(
      'boolean' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\BooleanMetadata',
      'date' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\DateMetadata',
      'decimal' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\FloatMetadata',
      'duration' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\IntegerMetadata',
      'integer' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\IntegerMetadata',
      'string' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\StringMetadata',
      'text' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\FullTextMetadata',
      'uri' => 'EC\EuropaSearch\Messages\Components\DocumentMetadata\URLMetadata',
    );

    if (!isset($methodMapping[$fieldType])) {
      throw new \Exception(t('Unknown type "@dataType" for the "@dataName" field.', array('@dataName' => $fieldName, '@dataType' => $fieldType)));
    }

    $className = $methodMapping[$fieldType];
    $this->metadataObject = new $className($fieldName);

    if (!empty($metadataValues)) {
      $this->setMetadataValues($metadataValues);
    }

    return $this->getMetadataObject();
  }

  /**
   * Gets the built metadata object.
   *
   * @return \EC\EuropaSearch\Messages\Components\DocumentMetadata\AbstractMetadata
   *   The instantiated AbstractMetadata extension object.
   */
  public function getMetadataObject() {
    return $this->metadataObject;
  }

  /**
   * Sets metadata values.
   *
   * @param array $metadataValues
   *   Array of metadata values to set.
   */
  protected function setMetadataValues(array $metadataValues) {
    if ($this->metadataObject instanceof DateMetadata) {
      $this->metadataObject->setTimestampValues($metadataValues);

      return;
    }

    $this->metadataObject->setRawValues($metadataValues);
  }

}
