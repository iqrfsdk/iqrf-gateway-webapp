<?php

/**
 * TEST: App\GatewayModule\Models\DmiBoardInfoManager
 * @covers App\GatewayModule\Models\DmiBoardInfoManager
 * @phpVersion >= 7.1
 * @testCase
 */
declare(strict_types = 1);

namespace Test\Unit\GatewayModule\Models;

use App\GatewayModule\Models\DmiBoardInfoManager;
use Tester\Assert;
use Tests\Toolkit\TestCases\CommandTestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for DMI board manager
 */
class DmiBoardInfoManagerTest extends CommandTestCase {

	/**
	 * @var DmiBoardInfoManager DMI board manager
	 */
	private $manager;

	/**
	 * @var string[] Mocked commands
	 */
	private $commands = [
		'dmiBoardName' => 'cat /sys/class/dmi/id/board_name',
		'dmiBoardVendor' => 'cat /sys/class/dmi/id/board_vendor',
		'dmiBoardVersion' => 'cat /sys/class/dmi/id/board_version',
	];

	/**
	 * Tests the function to get board's name from DMI (success)
	 */
	public function testGetNameSuccess(): void {
		$this->receiveCommand($this->commands['dmiBoardVendor'], true, 'AAEON');
		$this->receiveCommand($this->commands['dmiBoardName'], true, 'UP-APL01');
		$this->receiveCommand($this->commands['dmiBoardVersion'], true, 'V0.4');
		Assert::same('AAEON UP-APL01 (V0.4)', $this->manager->getName());
	}

	/**
	 * Tests the function to get board's name from DMI (fail)
	 */
	public function testGetNameFail(): void {
		$this->receiveCommand($this->commands['dmiBoardVendor'], true);
		$this->receiveCommand($this->commands['dmiBoardName'], true);
		$this->receiveCommand($this->commands['dmiBoardVersion'], true);
		Assert::null($this->manager->getName());
	}

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->manager = new DmiBoardInfoManager($this->commandManager);
	}

}

$test = new DmiBoardInfoManagerTest();
$test->run();
