<?php

/**
 * TEST: App\NetworkModule\Entities\WifiConnection
 * @covers App\NetworkModule\Entities\WifiConnection
 * @phpVersion >= 7.3
 * @testCase
 */
declare(strict_types = 1);

namespace Tests\Unit\NetworkModule\Entities;

use App\NetworkModule\Entities\WifiConnection;
use App\NetworkModule\Entities\WifiConnectionSecurity;
use App\NetworkModule\Enums\WifiKeyManagement;
use App\NetworkModule\Enums\WifiMode;
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for WiFi connection entity
 */
class WifiConnectionTest extends TestCase {

	/**
	 * NetworkManager data directory
	 */
	private const NM_DATA = __DIR__ . '/../../../data/networkManager/';

	/**
	 * @var WifiConnection WiFi connection entity
	 */
	private $entity;

	/**
	 * @var string SSID
	 */
	private $ssid;

	/**
	 * @var WifiMode WiFi network mode
	 */
	private $mode;

	/**
	 * @var WifiConnectionSecurity WiFi connection security entity
	 */
	private $security;

	/**
	 * Sets up the testing environment
	 */
	protected function setUp(): void {
		$this->ssid = 'WIFI MAGDA';
		$this->mode = WifiMode::INFRA();
		$this->security = new WifiConnectionSecurity(WifiKeyManagement::WPA_PSK(), 'password');
		$this->entity = new WifiConnection($this->ssid, $this->mode, $this->security);
	}

	/**
	 * Tests the function to create a new IPv6 connection entity from nmcli connection configuration
	 */
	public function testFromNmCli(): void {
		$configuration = FileSystem::read(self::NM_DATA . '5c7010a8-88f6-48e6-8ab2-5ad713217831.conf');
		Assert::equal($this->entity, WifiConnection::fromNmCli($configuration));
	}

	/**
	 * Tests the function to get WiFI network mode
	 */
	public function testGetMode(): void {
		Assert::equal($this->mode, $this->entity->getMode());
	}

	/**
	 * Tests teh function to get SSID
	 */
	public function testGetSsid(): void {
		Assert::same($this->ssid, $this->entity->getSsid());
	}

	/**
	 * Tests the function to return JSON serialized data
	 */
	public function testJsonSerialize(): void {
		$expected = [
			'ssid' => 'WIFI MAGDA',
			'mode' => 'infrastructure',
			'security' => [
				'keyManagement' => 'wpa-psk',
				'psk' => 'password',
			],
		];
		Assert::same($expected, $this->entity->jsonSerialize());
	}

	/**
	 * Tests the function to convert WiFi connection entity to nmcli configuration string
	 */
	public function testToNmCli(): void {
		$expected = '802-11-wireless.ssid "WIFI MAGDA" 802-11-wireless.mode "infrastructure" 802-11-wireless-security.key-mgmt "wpa-psk" 802-11-wireless-security.psk "password" ';
		Assert::same($expected, $this->entity->toNmCli());
	}

}

$test = new WifiConnectionTest();
$test->run();
