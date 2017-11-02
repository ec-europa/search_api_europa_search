<?php

/**
 * @file
 * Contains the definitions of Psr3DrupalLog and Psr3LogContextConvert classes.
 */

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Class Psr3DrupalLog.
 *
 * PSR-3 compatible class to integrate Drupal Watchdog into a library.
 */
class Psr3DrupalLog extends AbstractLogger {

  const SIMPLE_DATE = "Y-m-d H:i:s";

  /**
   * Mapping between PSR3 log levels and Drupal watchdog log levels.
   *
   * @var array
   */
  protected $map = array(
    LogLevel::EMERGENCY => WATCHDOG_EMERGENCY,
    LogLevel::ALERT => WATCHDOG_ALERT,
    LogLevel::CRITICAL => WATCHDOG_CRITICAL,
    LogLevel::ERROR => WATCHDOG_ERROR,
    LogLevel::WARNING => WATCHDOG_WARNING,
    LogLevel::NOTICE => WATCHDOG_NOTICE,
    LogLevel::INFO => WATCHDOG_INFO,
    LogLevel::DEBUG => WATCHDOG_DEBUG,
  );

  /**
   * Log type.
   *
   * @var string
   */
  protected $type = 'PSR-3';

  /**
   * Date format to use in log message.
   *
   * @var string
   */
  protected $dateFormat;

  /**
   * Psr3DrupalLog constructor.
   *
   * @param string $type
   *   The message type.
   * @param string $dateFormat
   *   The format of the timestamp: one supported by DateTime::format.
   */
  public function __construct($type, $dateFormat = NULL) {
    $this->dateFormat = $dateFormat ?: static::SIMPLE_DATE;
    $this->type = $type;
  }

  /**
   * Set log type.
   *
   * @param string $type
   *   Log type.
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   *   Log level.
   * @param string $message
   *   Log message.
   * @param array $context
   *   Log context.
   */
  public function log($level, $message, array $context = array()) {
    $message .= ": @context";
    $logContextConverter = new Psr3LogContextConvert();
    watchdog($this->type, $message, array('@context' => $logContextConverter->convertToString($context)), $this->map[$level]);
  }

}

/**
 * Class Psr3LogContextConvert.
 *
 * Utils class for converting a PSR-3 log context in order to
 * use it in a log message.
 *
 * A big part of this code comes from "monolog/monolog" library
 * (http://github.com/Seldaek/monolog).
 */
class Psr3LogContextConvert {

  /**
   * Converts the log context into a string.
   *
   * @param array $context
   *   The log context to convert.
   *
   * @return string
   *   The converted context.
   */
  public function convertToString(array $context) {
    $strContext = '';
    foreach ($context as $data) {
      if (NULL === $data || is_scalar($data)) {
        return (string) $data;
      }

      $data = $this->normalize($data);

      $strContext .= PHP_EOL . str_replace('\\/', '/', json_encode($data));
    }

    return $strContext;
  }

  /**
   * Normalizes a scalar in order to be used in a log message.
   *
   * @param mixed $data
   *   The scalar to normalize (string, boolean, float, integer).
   *
   * @return string
   *   the normalized scalar.
   */
  protected function normalizeScalar($data) {
    if (is_float($data)) {
      if (is_infinite($data)) {
        return ($data > 0 ? '' : '-') . 'INF';
      }
      if (is_nan($data)) {
        return 'NaN';
      }
    }

    return $data;
  }

  /**
   * Normalizes an array in order to be used in a log message.
   *
   * @param array $data
   *   The array to normalize.
   *
   * @return array
   *   Array of the normalized items of the submitted array.
   */
  protected function normalizeArray(array $data) {
    $normalized = array();

    $count = 1;
    foreach ($data as $key => $value) {
      if ($count++ >= 1000) {
        $normalized['...'] = 'Over 1000 items (' . count($data) . ' total), aborting normalization';
        break;
      }
      $normalized[$key] = $this->normalize($value);
    }

    return $normalized;
  }

  /**
   * Normalizes an object in order to be used in a log message.
   *
   * @param object $data
   *   The object to normalize.
   *
   * @return string
   *   The object info string to use in a log message.
   */
  protected function normalizeObject($data) {
    if ($data instanceof \Exception || (PHP_VERSION_ID > 70000 && $data instanceof \Throwable)) {
      return $this->normalizeException($data);
    }

    $normalized_data = "[object] (%s: %s)";
    $dataType = get_class($data);

    // Non-serializable objects that implement __toString stringified.
    if (method_exists($data, '__toString') && !$data instanceof \JsonSerializable) {
      return sprintf($normalized_data, $dataType, $data->__toString());
    }

    // The rest is json-serialized in some way.
    $value = $this->toJson($data, TRUE);

    return sprintf($normalized_data, $dataType, $value);
  }

  /**
   * Normalizes the context item.
   *
   * @param mixed $data
   *   The context item to normalize.
   *
   * @return array|string
   *   The normalized context item.
   */
  protected function normalize($data) {
    if (NULL === $data) {
      return $data;
    }

    if (is_scalar($data)) {
      return $this->normalizeScalar($data);
    }

    if (is_array($data)) {
      return $this->normalizeArray($data);
    }

    if ($data instanceof \DateTime) {
      return $data->format($this->dateFormat);
    }

    if (is_object($data)) {
      return $this->normalizeObject($data);
    }

    if (is_resource($data)) {
      return sprintf('[resource] (%s)', get_resource_type($data));
    }

    return '[unknown(' . gettype($data) . ')]';
  }

  /**
   * Normalizes an exception contained in the context.
   *
   * @param \Exception $exception
   *   The exception to normalize.
   *
   * @return array|string
   *   The normalized exception.
   */
  protected function normalizeException(\Exception $exception) {
    $data = array(
      'class' => get_class($exception),
      'message' => $exception->getMessage(),
      'code' => $exception->getCode(),
      'file' => $exception->getFile() . ':' . $exception->getLine(),
    );

    $trace = $exception->getTrace();
    foreach ($trace as $frame) {
      if (isset($frame['file'])) {
        $data['trace'][] = $frame['file'] . ':' . $frame['line'];
      }
      elseif (isset($frame['function']) && $frame['function'] === '{closure}') {
        // We should again normalize the frames, because it might contain
        // invalid items.
        $data['trace'][] = $frame['function'];
      }
      else {
        // We should again normalize the frames, because it might contain
        // invalid items.
        $data['trace'][] = $this->toJson($this->normalize($frame), TRUE);
      }
    }

    if ($previous = $exception->getPrevious()) {
      $data['previous'] = $this->normalizeException($previous);
    }

    return $data;
  }

  /**
   * Returns the JSON representation of a value.
   *
   * @param mixed $data
   *   The value to represented in Json.
   * @param bool $ignoreErrors
   *   [Optional] TRUE if the JSON encoding errors must be ignored.
   *   (FALSE by default).
   *
   * @return string
   *   The value's JSON representation.
   *
   * @throws \RuntimeException
   *   If encoding fails and errors are not ignored.
   */
  protected function toJson($data, $ignoreErrors = FALSE) {
    // Suppress json_encode errors since it's twitchy with some inputs.
    if ($ignoreErrors) {
      return @$this->jsonEncode($data);
    }

    $json = $this->jsonEncode($data);

    if ($json === FALSE) {
      $json = $this->handleJsonError(json_last_error(), $data);
    }

    return $json;
  }

  /**
   * Encodes the JSON representation of a value.
   *
   * @param mixed $data
   *   The value to encode.
   *
   * @return string
   *   JSON encoded data or NULL on failure
   */
  protected function jsonEncode($data) {
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Handle a json_encode failure.
   *
   * If the failure is due to invalid string encoding, try to clean the
   * input and encode again. If the second encoding attempt fails, the
   * inital error is not encoding related or the input can't be cleaned then
   * raise a descriptive exception.
   *
   * @param int $code
   *   Return code of json_last_error function.
   * @param mixed $data
   *   Data that was meant to be encoded.
   *
   * @return string
   *   JSON encoded data after error correction.
   *
   * @throws \RuntimeException
   *   If failure can't be corrected.
   */
  protected function handleJsonError($code, $data) {
    if ($code !== JSON_ERROR_UTF8) {
      $this->throwEncodeError($code, $data);
    }

    if (is_string($data)) {
      $this->detectAndCleanUtf8($data);
    }
    elseif (is_array($data)) {
      array_walk_recursive($data, array($this, 'detectAndCleanUtf8'));
    }
    else {
      $this->throwEncodeError($code, $data);
    }

    $json = $this->jsonEncode($data);

    if ($json === FALSE) {
      $this->throwEncodeError(json_last_error(), $data);
    }

    return $json;
  }

  /**
   * Throws an exception according to a given code with a customized message.
   *
   * @param int $code
   *   Returned code of json_last_error function.
   * @param mixed $data
   *   Data that was meant to be encoded.
   *
   * @throws \RuntimeException
   *   The exception according to a given code.
   */
  protected function throwEncodeError($code, $data) {
    switch ($code) {
      case JSON_ERROR_DEPTH:
        $msg = 'Maximum stack depth exceeded';
        break;

      case JSON_ERROR_STATE_MISMATCH:
        $msg = 'Underflow or the modes mismatch';
        break;

      case JSON_ERROR_CTRL_CHAR:
        $msg = 'Unexpected control character found';
        break;

      case JSON_ERROR_UTF8:
        $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
        break;

      default:
        $msg = 'Unknown error';
    }

    throw new \RuntimeException('JSON encoding failed: ' . $msg . '. Encoding: ' . var_export($data, TRUE));
  }

  /**
   * Detect invalid UTF-8 string characters and convert to valid UTF-8.
   *
   * Valid UTF-8 input will be left unmodified, but strings containing
   * invalid UTF-8 codepoints will be reencoded as UTF-8 with an assumed
   * original encoding of ISO-8859-15. This conversion may result in
   * incorrect output if the actual encoding was not ISO-8859-15, but it
   * will be clean UTF-8 output and will not rely on expensive and fragile
   * detection algorithms.
   *
   * Function converts the input in place in the passed variable so that it
   * can be used as a callback for array_walk_recursive.
   *
   * @param mixed $data
   *   Input to check and convert if needed.
   */
  public function detectAndCleanUtf8(&$data) {
    if (is_string($data) && !preg_match('//u', $data)) {
      $data = preg_replace_callback(
        '/[\x80-\xFF]+/',
        function ($message) {
          return utf8_encode($message[0]);
        },
        $data
      );
      $data = str_replace(
        array('¤', '¦', '¨', '´', '¸', '¼', '½', '¾'),
        array('€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'),
        $data
      );
    }
  }

}
