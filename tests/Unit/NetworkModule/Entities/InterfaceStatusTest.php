<?php

/**
 * TEST: App\NetworkModule\Entities\InterfaceStatus
 * @covers App\NetworkModule\Entities\InterfaceStatus
 * @phpVersion >= 7.2
 * @testCase
 */
declare(strict_types = 1);

namespace Tests\Unit\NetworkModule\Entities;

use App\NetworkModule\Entities\InterfaceStatus;
use App\NetworkModule\Enums\InterfaceStates;
use App\NetworkModule\Enums\InterfaceTypes;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for network interface entity
 */
final class InterfaceStatusTest extends TestCase {

	/**
	 * Network connection name
	 */
	private const CONNECTION = 'eth0';

	/**
	 * Network interface name
	 */
	private const NAME = 'eth0';

	/**
	 * @var InterfaceTypes Network interface type
	 */
	private $type;

	/**
	 * @var InterfaceStates Network interface state
	 */
	private $state;

	/**
	 * @var InterfaceStatus Network interface entity
	 */
	private $entity;

	/**
	 * Sets up the test environment
	 */
	public function __construct() {
		$this->type = InterfaceTypes::ETHERNET();
		$this->state = InterfaceStates::CONNECTED();
		$this->entity = new InterfaceStatus(self::NAME, $this->type, $this->state, self::CONNECTION);
	}

	/**
	 * Tests the function to deserialize network interface entity from a nmcli row
	 */
	public function testNmCliDeserialize(): void {
		$string = 'eth0:ethernet:connected:eth0';
		Assert::equal($this->entity, InterfaceStatus::nmCliDeserialize($string));
	}

	/**
	 * Tests the function to get network interface name
	 */
	public function testGetName(): void {
		Assert::same(self::NAME, $this->entity->getName());
	}

	/**
	 * Tests the function to get network interface type
	 */
	public function testGetType(): void {
		Assert::same($this->type, $this->entity->getType());
	}

	/**
	 * Tests the function to get network interface state
	 */
	public function testGetState(): void {
		Assert::same($this->state, $this->entity->getState());
	}

	/**
	 * Tests the function to get network connection name
	 */
	public function testGetConnectionName(): void {
		Assert::same(self::CONNECTION, $this->entity->getConnectionName());
	}

	/**
	 * Tests the function to serialize network interface status entity into JSON
	 */
	public function testJsonSerialize(): void {
		$expected = [
			'name' => self::NAME,
			'type' => $this->type->toScalar(),
			'state' => $this->state->toScalar(),
			'connectionName' => self::CONNECTION,
		];
		Assert::same($expected, $this->entity->jsonSerialize());
	}

}

$test = new InterfaceStatusTest();
$test->run();
