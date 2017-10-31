<?php

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Class Psr3DrupalLog.
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

    watchdog($this->type, $message, array('@context' => $this->convertToString($context)), $this->map[$level]);
  }

}
