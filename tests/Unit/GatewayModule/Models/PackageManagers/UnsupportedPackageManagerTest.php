<?php
/**
 * TEST: App\GatewayModule\Models\PackageManagers\UnsupportedPackageManager
 * @covers App\GatewayModule\Models\PackageManagers\UnsupportedPackageManager
 * @phpVersion >= 7.2
 * @testCase
 */

declare(strict_types = 1);

namespace Tests\Unit\GatewayModule\Models\PackageManagers;

use App\GatewayModule\Exceptions\UnsupportedPackageManagerException;
use App\GatewayModule\Models\PackageManagers\UnsupportedPackageManager;
use Tester\Assert;
use Tests\Toolkit\TestCases\CommandTestCase;

require __DIR__ . '/../../../../bootstrap.php';

/**
 * Tests for tool for unsupported package manager
 */
final class UnsupportedPackageManagerTest extends CommandTestCase {

	/**
	 * Packages
	 */
	private const PACKAGES = ['iqrf-gateway-daemon', 'iqrf-gateway-webapp'];

	/**
	 * @var UnsupportedPackageManager Tool for updating IQRF Gateway
	 */
	private $manager;

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->manager = new UnsupportedPackageManager();
	}

	/**
	 * Tests the function to install packages
	 */
	public function testInstall(): void {
		Assert::throws(function (): void {
			$this->manager->install([$this, 'callback'], self::PACKAGES);
		}, UnsupportedPackageManagerException::class);
	}

	/**
	 * Tests the function to list upgradable packages
	 */
	public function testListUpgradable(): void {
		Assert::throws(function (): void {
			$this->manager->listUpgradable([$this, 'callback']);
		}, UnsupportedPackageManagerException::class);
	}

	/**
	 * Tests the function to get list of upgradable packages
	 */
	public function testGetUpgradable(): void {
		Assert::throws([$this->manager, 'getUpgradable'], UnsupportedPackageManagerException::class);
	}

	/**
	 * Tests the function to remove packages
	 */
	public function testRemove(): void {
		Assert::throws(function (): void {
			$this->manager->remove([$this, 'callback'], self::PACKAGES);
		}, UnsupportedPackageManagerException::class);
	}

	/**
	 * Tests the function to purge packages
	 */
	public function testPurge(): void {
		Assert::throws(function (): void {
			$this->manager->purge([$this, 'callback'], self::PACKAGES);
		}, UnsupportedPackageManagerException::class);
	}

	/**
	 * Tests the function to update list of packages
	 */
	public function testUpdate(): void {
		Assert::throws(function (): void {
			$this->manager->update([$this, 'callback']);
		}, UnsupportedPackageManagerException::class);
	}

	/**
	 * Tests the function to upgrade packages
	 */
	public function testUpgrade(): void {
		Assert::throws(function (): void {
			$this->manager->upgrade([$this, 'callback']);
		}, UnsupportedPackageManagerException::class);
	}

	/**
	 * Just an empty callback
	 */
	public function callback(): void {
	}

}

$test = new UnsupportedPackageManagerTest();
$test->run();
