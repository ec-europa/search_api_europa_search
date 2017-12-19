<?php

namespace Drupal\Tests\search_api_europa_search\Kernel;

use PHPUnit\Framework\TestCase;

/**
 * Class SmokeTest.
 *
 * @package Drupal\Tests\search_api_europa_search\Kernel
 */
class SmokeTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $this->assertTrue(module_exists('search_api_europa_search'));
  }

}
