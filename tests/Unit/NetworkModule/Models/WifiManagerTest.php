<?php

/**
 * TEST: App\NetworkModule\Models\WifiManager
 * @covers App\NetworkModule\Models\WifiManager
 * @phpVersion >= 7.1
 * @testCase
 */

declare(strict_types = 1);

namespace Tests\Unit\NetworkModule\Models;

use App\NetworkModule\Entities\WifiNetwork;
use App\NetworkModule\Enums\WifiMode;
use App\NetworkModule\Enums\WifiSecurity;
use App\NetworkModule\Models\WifiManager;
use Tester\Assert;
use Tests\Toolkit\TestCases\CommandTestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for WiFi network manager
 */
class WifiManagerTest extends CommandTestCase {

	/**
	 * @var WifiManager WiFi network manager
	 */
	private $manager;

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->manager = new WifiManager($this->commandManager);
	}

	public function testList(): void {
		$output = '*:04\:F0\:21\:24\:1E\:53:WIFI MAGDA:Infra:36:405 Mbit/s:60:▂▄▆_:WPA2' . PHP_EOL
			. ' :18\:E8\:29\:E4\:CB\:9A:WIFI MAGDA:Infra:1:195 Mbit/s:47:▂▄__:WPA2' . PHP_EOL
			. ' :1A\:E8\:29\:E5\:CB\:9A:WIFI MAGDA:Infra:36:405 Mbit/s:32:▂▄__:WPA2' . PHP_EOL;
		$this->receiveCommand('nmcli -t device wifi list --rescan auto', true, $output);
		$ssid = 'WIFI MAGDA';
		$mode = WifiMode::INFRA();
		$security = WifiSecurity::WPA2_PERSONAL();
		$expected = [
			new WifiNetwork(true, '04:F0:21:24:1E:53', $ssid, $mode, 36, '405 Mbit/s', 60, $security),
			new WifiNetwork(false, '18:E8:29:E4:CB:9A', $ssid, $mode, 1, '195 Mbit/s', 47, $security),
			new WifiNetwork(false, '1A:E8:29:E5:CB:9A', $ssid, $mode, 36, '405 Mbit/s', 32, $security),
		];
		Assert::equal($expected, $this->manager->list());
	}

}

$test = new WifiManagerTest();
$test->run();