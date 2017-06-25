<?php

/**
 * TEST: App\Model\IqrfAppManager
 * @phpVersion >= 5.6
 * @testCase
 */
use App\Model\CommandManager;
use App\Model\IqrfAppManager;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../bootstrap.php';

class IqrfAppManagerTest extends TestCase {

	private $container;

	function __construct(Container $container) {
		$this->container = $container;
	}

	/**
	 * @test
	 * Test function to validation of raw IQRF packet
	 */
	public function testValidatePacket() {
		$commandManager = new CommandManager(false);
		$iqrfAppManager = new IqrfAppManager($commandManager);
		$packet0 = '01.00.06.03.ff.ff';
		$packet1 = '01 00 06 03 ff ff';
		$packet2 = '01.00.06.03.ff.ff.';
		$packet3 = '01 00 06 03 ff ff.';
		$packet4 = ';01.00.06.03.ff.ff';
		$packet5 = ';01 00 06 03 ff ff';
		$packet6 = '01.00.06.03.ff.ff;';
		$packet7 = '01 00 06 03 ff ff;';
		$packet8 = '; echo Test > test.log';
		Assert::true($iqrfAppManager->validatePacket($packet0), 'Valid packet with dots.');
		Assert::true($iqrfAppManager->validatePacket($packet1), 'Valid packet with spaces.');
		Assert::true($iqrfAppManager->validatePacket($packet2), 'Valid packet with dots.');
		Assert::true($iqrfAppManager->validatePacket($packet3), 'Valid packet with spaces.');
		Assert::false($iqrfAppManager->validatePacket($packet4), 'Invalid packet with dots.');
		Assert::false($iqrfAppManager->validatePacket($packet5), 'Invalid packet with spaces.');
		Assert::false($iqrfAppManager->validatePacket($packet6), 'Invalid packet with dots.');
		Assert::false($iqrfAppManager->validatePacket($packet7), 'Invalid packet with spaces.');
		Assert::false($iqrfAppManager->validatePacket($packet8), 'Invalid packet.');
	}

	/**
	 * @test
	 * Test function to parse OS read info
	 */
	public function testParseOsReadInfo() {
		$commandManager = new CommandManager(false);
		$iqrfAppManager = new IqrfAppManager($commandManager);
		$packet = '00.00.02.80.00.00.00.00.05.a4.00.81.38.24.79.08.00.28.00.f0';
		$array = $iqrfAppManager->parseOsReadInfo($packet);
		$expected = [
			'ModuleId' => '8100A405',
			'OsVersion' => '3.08D',
			'TrType' => 'DCTR-72D',
			'McuType' => 'PIC16F1938',
			'OsBuild' => '7908',
			'Rssi' => '00',
			'SupplyVoltage' => '3.00',
			'Flags' => '00',
			'SlotLimits' => 'f0',
		];
		Assert::equal($expected, $array);
	}

}

$test = new IqrfAppManagerTest($container);
$test->run();
