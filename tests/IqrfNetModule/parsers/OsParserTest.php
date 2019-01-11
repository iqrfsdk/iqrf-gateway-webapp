<?php

/**
 * TEST: App\IqrfNetModule\Parsers\OsParser
 * @covers App\IqrfNetModule\Parsers\OsParser
 * @phpVersion >= 7.0
 * @testCase
 */
declare(strict_types = 1);

namespace Test\IqrfNetModule\Models;

use App\CoreModule\Models\JsonFileManager;
use App\IqrfNetModule\Parsers\OsParser;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/**
 * Tests for parser of DPA OS responses
 */
class OsParserTest extends TestCase {

	/**
	 * @var OsParser DPA OS response parser
	 */
	private $parser;

	/**
	 * @var string[] DPA packets
	 */
	private $packet;

	/**
	 * @var mixed[] Expected parsed responses
	 */
	private $expected;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		$this->parser = new OsParser();
		$jsonFileManager = new JsonFileManager(__DIR__ . '/../../data/iqrf/');
		$this->packet['hwpConfiguration'] = $jsonFileManager->read('response-os-hwp-config')['data']['rsp']['rData'];
		$this->packet['osInfo'] = $jsonFileManager->read('response-os-read')['data']['rsp']['rData'];
		$this->expected['hwpConfiguration'] = $jsonFileManager->read('data-os-hwp-config');
		$this->expected['osInfo'] = $jsonFileManager->read('data-os-read');
	}

	/**
	 * Test function to parse DPA response "OS Info"
	 */
	public function testParseInfo(): void {
		Assert::equal($this->expected['osInfo'], $this->parser->parse($this->packet['osInfo']));
	}

	/**
	 * Test function to parse DPA response "HWP configuration"
	 */
	public function testParseHwp(): void {
		Assert::equal($this->expected['hwpConfiguration'], $this->parser->parse($this->packet['hwpConfiguration']));
	}

	/**
	 * Test function to parse response to DPA OS - "Read info" request
	 */
	public function testParseReadInfo(): void {
		Assert::equal($this->expected['osInfo'], $this->parser->parseReadInfo($this->packet['osInfo']));
		$failPacket = preg_replace('/\.24\./', '\.ff\.', $this->packet['osInfo']);
		$failExpected = $this->expected['osInfo'];
		$failExpected['TrType'] = $failExpected['McuType'] = 'UNKNOWN';
		Assert::equal($failExpected, $this->parser->parseReadInfo($failPacket));
	}

	/**
	 * Test function to get RF band from HWP configuration
	 */
	public function testGetRfBand(): void {
		Assert::equal('868 MHz', $this->parser->getRfBand('30'));
		Assert::equal('916 MHz', $this->parser->getRfBand('31'));
		Assert::equal('433 MHz', $this->parser->getRfBand('32'));
	}

	/**
	 * Test function to parse response to DPA OS - "Read HWP configuration" request
	 */
	public function testParseHwpConfiguration(): void {
		$actual = $this->parser->parseHwpConfiguration($this->packet['hwpConfiguration']);
		Assert::same($this->expected['hwpConfiguration'], $actual);
	}

}

$test = new OsParserTest();
$test->run();