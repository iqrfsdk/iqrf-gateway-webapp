<?php

/**
 * TEST: App\CloudModule\Model\AzureManager
 * @covers App\CloudModule\Model\AzureManager
 * @phpVersion >= 5.6
 * @testCase
 */
declare(strict_types=1);

namespace Test\ServiceModule\Model;

use App\CloudModule\Model\AzureManager;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

class AzureManagerTest extends TestCase {

	/**
	 * @var Container Nette Tester Container
	 */
	private $container;

	/**
	 * @var AzureManager MS Azure IoT hub manager
	 */
	private $manager;

	/**
	 * Constructor
	 * @param Container $container Nette Tester Container
	 */
	public function __construct(Container $container) {
		$this->container = $container;
	}

	/**
	 * Set up test environment
	 */
	public function setUp() {
		$this->manager = new AzureManager();
	}

	/**
	 * @test
	 * Test function to create Base service
	 */
	public function testCreateBaseService() {
		$mqtt = [
			'Name' => 'BaseServiceForMQTTAzure',
			'Messaging' => 'MqttMessagingAzure',
			'Serializers' => ['JsonSerializer'],
			'Properties' => ['AsyncDpaMessage' => true],
		];
		$actual = $this->manager->createBaseService();
		Assert::same($mqtt['Serializers'], iterator_to_array($actual['Serializers']));
		Assert::same($mqtt['Properties'], iterator_to_array($actual['Properties']));
		unset($actual['Serializers'], $actual['Properties'], $mqtt['Serializers'], $mqtt['Properties']);
		Assert::same($mqtt, iterator_to_array($actual));
	}

}

$test = new AzureManagerTest($container);
$test->run();
