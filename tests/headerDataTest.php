<?php
/**
 * Unit tests for Smart Content CDN
 *
 * @package Pantheon\SmartContentCDN
 */

use Pantheon\EI\HeaderData;
use PHPUnit\Framework\TestCase;

/**
 * Main class for testing HeaderData.
 */
class HeaderDataTests extends TestCase {
	/**
	 * Load the SDK into the test environment.
	 *
	 * @return void
	 */
	protected function setUp() : void {
		parent::setUp();
		$this->header_data = new HeaderData();
	}

	/**
	 * Test that the HeaderData methods exist.
	 *
	 * @group headerData
	 */
	public function testMethodsExist() : void {
		$this->assertTrue( method_exists( $this->header_data, 'getRequestHeaders' ) );
		$this->assertTrue( method_exists( $this->header_data, 'getHeader' ) );
		$this->assertTrue( method_exists( $this->header_data, 'parseHeader' ) );
		$this->assertTrue( method_exists( $this->header_data, 'returnPersonalizationObject' ) );
		$this->assertTrue( method_exists( $this->header_data, 'returnVaryHeader' ) );
	}
}
