<?php

/**
 * TEST: App\GatewayModule\Models\InfoManager
 * @covers App\GatewayModule\Models\InfoManager
 * @phpVersion >= 7.1
 * @testCase
 */
declare(strict_types = 1);

namespace Tests\Unit\GatewayModule\Models;

use App\CoreModule\Models\VersionManager;
use App\GatewayModule\Models\InfoManager;
use App\IqrfNetModule\Models\EnumerationManager;
use Mockery;
use Mockery\MockInterface;
use Tester\Assert;
use Tests\Toolkit\TestCases\CommandTestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for Gateway info manager
 */
class InfoManagerTest extends CommandTestCase {

	/**
	 * @var MockInterface|EnumerationManager Mocked IQMESH enumeration manager
	 */
	private $enumerationManager;

	/**
	 * @var InfoManager Gateway Info manager with mocked command manager
	 */
	private $manager;

	/**
	 * @var MockInterface|VersionManager Mocked version manager
	 */
	private $versionManager;

	/**
	 * @var string[] Mocked commands
	 */
	private $commands = [
		'daemonVersion' => 'iqrfgd2 version',
		'deviceTreeName' => 'cat /proc/device-tree/model',
		'dmiBoardName' => 'cat /sys/class/dmi/id/board_name',
		'dmiBoardVendor' => 'cat /sys/class/dmi/id/board_vendor',
		'dmiBoardVersion' => 'cat /sys/class/dmi/id/board_version',
		'gw' => 'cat /etc/iqrf-gateway.json',
		'hostname' => 'hostname -f',
		'ipAddressesEth0' => 'ip a s eth0 | grep inet | grep global | grep -v temporary | awk \'{print $2}\'',
		'ipAddressesWlan0' => 'ip a s wlan0 | grep inet | grep global | grep -v temporary | awk \'{print $2}\'',
		'macAddresses' => 'cat /sys/class/net/eth0/address',
		'networkAdapters' => 'ls /sys/class/net | awk \'{ print $0 }\'',
		'gitBranches' => 'git branch -v --no-abbrev',
	];

	/**
	 * Tests the function to get information about the board (via IQRF GW json)
	 */
	public function testGetBoardGw(): void {
		$output = '{"gwProduct":"IQD-GW-01","gwManufacturer":"MICRORISC s.r.o."}';
		$this->receiveCommand($this->commands['gw'], true, $output);
		Assert::same('MICRORISC s.r.o. IQD-GW-01', $this->manager->getBoard());
	}

	/**
	 * Tests the function to get information about the board (via device tree)
	 */
	public function testGetBoardDeviceTree(): void {
		$expected = 'Raspberry Pi 2 Models B Rev 1.1';
		$this->receiveCommand($this->commands['gw'], true);
		$this->receiveCommand($this->commands['deviceTreeName'], true, $expected);
		Assert::same($expected, $this->manager->getBoard());
	}

	/**
	 * Tests the function to get information about the board (via DMI)
	 */
	public function testGetBoardDmi(): void {
		$this->receiveCommand($this->commands['gw'], true);
		$this->receiveCommand($this->commands['deviceTreeName'], true);
		$this->receiveCommand($this->commands['dmiBoardVendor'], true, 'AAEON');
		$this->receiveCommand($this->commands['dmiBoardName'], true, 'UP-APL01');
		$this->receiveCommand($this->commands['dmiBoardVersion'], true, 'V0.4');
		Assert::same('AAEON UP-APL01 (V0.4)', $this->manager->getBoard());
	}

	/**
	 * Tests the function to get information about the board (unknown method)
	 */
	public function testGetBoardUnknown(): void {
		$this->receiveCommand($this->commands['gw'], true);
		$this->receiveCommand($this->commands['deviceTreeName'], true);
		$this->receiveCommand($this->commands['dmiBoardVendor'], true);
		$this->receiveCommand($this->commands['dmiBoardName'], true);
		$this->receiveCommand($this->commands['dmiBoardVersion'], true);
		Assert::same('UNKNOWN', $this->manager->getBoard());
	}

	/**
	 * Tests the function to get the gateway ID
	 */
	public function testGetId(): void {
		$output = '{"gwId":"0242fc1e6f85b296"}';
		$this->receiveCommand($this->commands['gw'], true, $output);
		Assert::same('0242fc1e6f85b296', $this->manager->getId());
	}

	/**
	 * Tests the function to get IPv4 and IPv6 addresses of the gateway
	 */
	public function testGetIpAddresses(): void {
		$this->receiveCommand($this->commands['networkAdapters'], true, 'eth0' . PHP_EOL . 'wlan0' . PHP_EOL . 'lo');
		$this->receiveCommand($this->commands['ipAddressesEth0'], true, '192.168.1.100' . PHP_EOL . 'fda9:d95:d5b1::64');
		$this->receiveCommand($this->commands['ipAddressesWlan0'], true, '');
		$expected = ['eth0' => ['192.168.1.100', 'fda9:d95:d5b1::64']];
		Assert::same($expected, $this->manager->getIpAddresses());
	}

	/**
	 * Tests the function to get MAC addresses of the gateway
	 */
	public function testGetMacAddresses(): void {
		$this->receiveCommand($this->commands['networkAdapters'], true, 'eth0' . PHP_EOL . 'lo');
		$this->receiveCommand($this->commands['macAddresses'], true, '01:02:03:04:05:06');
		$expected = ['eth0' => '01:02:03:04:05:06'];
		Assert::same($expected, $this->manager->getMacAddresses());
	}

	/**
	 * Tests the function to get hostname of the gateway
	 */
	public function testGetHostname(): void {
		$expected = 'gateway';
		$this->receiveCommand($this->commands['hostname'], null, $expected);
		Assert::same($expected, $this->manager->getHostname());
	}

	/**
	 * Tests the function to get information about the Coordinator
	 */
	public function testGetCoordinatorInfo(): void {
		$expected = ['request' => [], 'response' => []];
		$this->enumerationManager->shouldReceive('device')->with(0)->andReturn($expected);
		Assert::same($expected, $this->manager->getCoordinatorInfo());
	}

	/**
	 * Tests the function to get IQRF Gateway Daemon's version
	 */
	public function testGetDaemonVersion(): void {
		$expected = 'v2.0.0dev 2018-07-04T10:30:51';
		$this->commandManager->shouldReceive('commandExist')->with('iqrfgd2')->andReturn(true);
		$this->receiveCommand($this->commands['daemonVersion'], null, $expected);
		Assert::same($expected, $this->manager->getDaemonVersion());
	}

	/**
	 * Tests the function to get IQRF Gateway Daemon's version (IQRF Gateway Daemon is not installed)
	 */
	public function testGetDaemonVersionNotInstalled(): void {
		$this->commandManager->shouldReceive('commandExist')->with('iqrfgd2')->andReturn(false);
		Assert::same('none', $this->manager->getDaemonVersion());
	}

	/**
	 * Tests the function to get IQRF Gateway Daemon's version (unknown version)
	 */
	public function testGetDaemonVersionUnknown(): void {
		$this->commandManager->shouldReceive('commandExist')->with('iqrfgd2')->andReturn(true);
		$this->receiveCommand($this->commands['daemonVersion']);
		Assert::same('unknown', $this->manager->getDaemonVersion());
	}

	/**
	 * Tests the function to get version of the webapp
	 */
	public function testGetWebAppVersion(): void {
		$expected = 'v1.1.6';
		$this->versionManager->shouldReceive('getInstalledWebapp')->andReturn($expected);
		Assert::same($expected, $this->manager->getWebAppVersion());
	}

	/**
	 * Tests the function to get disk usages
	 */
	public function testGetDiskUsages(): void {
		$command = 'df -B1 -x tmpfs -x devtmpfs -T -P | awk \'{if (NR!=1) {$6="";print}}\'';
		$output = '/dev/sda1 ext4 243735838720 205705183232 25625583616  /';
		$this->receiveCommand($command, null, $output);
		$expected = [
			[
				'fsName' => '/dev/sda1',
				'fsType' => 'ext4',
				'size' => '227 GB',
				'used' => '191.58 GB',
				'available' => '23.87 GB',
				'usage' => '84.4%',
				'mount' => '/',
			],
		];
		Assert::same($expected, $this->manager->getDiskUsages());
	}

	/**
	 * Tests the function to get memory usage
	 */
	public function testGetMemoryUsage(): void {
		$command = 'free -bw | awk \'{{if (NR==2) print $2,$3,$4,$5,$6,$7,$8}}\'';
		$output = '8220397568 6708125696 256442368 361750528 115830784 1139998720 879230976';
		$this->receiveCommand($command, null, $output);
		$expected = [
			'size' => '7.66 GB',
			'used' => '6.25 GB',
			'free' => '244.56 MB',
			'shared' => '344.99 MB',
			'buffers' => '110.46 MB',
			'cache' => '1.06 GB',
			'available' => '838.5 MB',
			'usage' => '81.6%',
		];
		Assert::same($expected, $this->manager->getMemoryUsage());
	}


	/**
	 * Tests the function to get swap usage
	 */
	public function testGetSwapUsage(): void {
		$command = 'free -b | awk \'{{if (NR==3) print $2,$3,$4}}\'';
		$output = '8291086336 2250952704 6040133632';
		$this->receiveCommand($command, null, $output);
		$expected = [
			'size' => '7.72 GB',
			'used' => '2.1 GB',
			'free' => '5.63 GB',
			'usage' => '27.15%',
		];
		Assert::same($expected, $this->manager->getSwapUsage());
	}


	/**
	 * Tests the function to get swap usage (computer hasn't got swap)
	 */
	public function testGetSwapUsageWithoutSwap(): void {
		$command = 'free -b | awk \'{{if (NR==3) print $2,$3,$4}}\'';
		$output = '0 0 0 ';
		$this->receiveCommand($command, null, $output);
		Assert::null($this->manager->getSwapUsage());
	}

	/**
	 * Tests the function to convert size in bytes to human readable format
	 */
	public function testConvertSizes(): void {
		Assert::same('1000 B', $this->manager->convertSizes(1e3));
		Assert::same('953.67 MB', $this->manager->convertSizes(1e9));
		Assert::same('953.6743164063 MB', $this->manager->convertSizes(1e9, 10));
	}

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->versionManager = Mockery::mock(VersionManager::class);
		$this->enumerationManager = Mockery::mock(EnumerationManager::class);
		$this->manager = new InfoManager($this->commandManager, $this->enumerationManager, $this->versionManager);
	}

}

$test = new InfoManagerTest();
$test->run();