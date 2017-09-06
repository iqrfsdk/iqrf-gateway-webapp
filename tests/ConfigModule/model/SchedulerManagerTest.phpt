<?php

/**
 * TEST: App\ConfigModule\Model\SchedulerManager
 * @phpVersion >= 5.6
 * @testCase
 */

namespace Test\ConfigModule\Model;

use App\ConfigModule\Model\SchedulerManager;
use App\Model\JsonFileManager;
use Nette\DI\Container;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

class SchedulerManagerTest extends TestCase {

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * @var array
	 */
	private $array = [
		'time' => '*/5 * * * * * *',
		'service' => 'BaseServiceForMQTT1',
		'ctype' => 'dpa',
		'type' => 'std-sen',
		'nadr' => '1',
		'cmd' => 'READ',
		'hwpid' => 'ffff',
		'sensors' => 'Temperature1' . PHP_EOL . 'CO2_1' . PHP_EOL . 'Humidity1',
	];

	/**
	 * @var string
	 */
	private $fileName = 'Scheduler';

	/**
	 * @var string
	 */
	private $path = __DIR__ . '/../../configuration/';

	/**
	 * @var string
	 */
	private $pathTest = __DIR__ . '/../../configuration-test/';

	/**
	 * Constructor
	 * @param Container $container
	 */
	public function __construct(Container $container) {
		$this->container = $container;
	}

	/**
	 * @test
	 * Test function to add configuration of Scheduler
	 */
	public function testAdd() {
		$fileManager = new JsonFileManager($this->pathTest);
		$manager = new SchedulerManager($fileManager);
		$expected = Json::decode(FileSystem::read($this->path . $this->fileName . '.json'), Json::FORCE_ARRAY);
		$fileManager->write($this->fileName, $expected);
		$manager->add('raw');
		$task = [
			'time' => '',
			'service' => '',
			'message' => [
				'ctype' => 'dpa',
				'type' => 'raw',
				'msgid' => '',
				'timeout' => 0,
				'request' => '',
				'request_ts' => '',
				'confirmation' => '',
				'confirmation_ts' => '',
				'response' => '',
				'respons_ts' => '',
			],
		];
		array_push($expected['TasksJson'], $task);
		Assert::equal($expected, $fileManager->read($this->fileName));
	}

	/**
	 * @test
	 * Test function to delete configuration of Scheduler
	 */
	public function testDelete() {
		$fileManager = new JsonFileManager($this->pathTest);
		$manager = new SchedulerManager($fileManager);
		$expected = Json::decode(FileSystem::read($this->path . $this->fileName . '.json'), Json::FORCE_ARRAY);
		$fileManager->write($this->fileName, $expected);
		unset($expected['TasksJson'][5]);
		$manager->delete(5);
		Assert::equal($expected, $fileManager->read($this->fileName));
	}

	/**
	 * @test
	 * Test function to get last ID
	 */
	public function testGetLastId() {
		$fileManager = new JsonFileManager($this->path);
		$manager = new SchedulerManager($fileManager);
		$expected = count($fileManager->read($this->fileName)['TasksJson']) - 1;
		Assert::equal($expected, $manager->getLastId());
	}

	/**
	 * @test
	 * Test function to get tasks
	 */
	public function testGetTasks() {
		$fileManager = new JsonFileManager($this->path);
		$manager = new SchedulerManager($fileManager);
		$expected = [
			[
				"time" => "*/5 * * * * * *",
				"service" => "BaseServiceForMQTT1",
				"type" => "dpa | std-sen",
				"request" => null,
				"id" => 0,
			], [
				"time" => "*/5 1 * * * * *",
				"service" => "BaseServiceForMQTT1",
				"type" => "dpa | std-per-ledg",
				"request" => "01.00.07.03.ffff",
				"id" => 1,
			], [
				"time" => "*/5 1 * * * * *",
				"service" => "BaseServiceForMQTT1",
				"type" => "dpa | raw",
				"request" => "01.00.06.03.ff.ff",
				"id" => 2,
			], [
				"time" => "*/5 1 * * * * *",
				"service" => "BaseServiceForMQTT1",
				"type" => "dpa | raw-hdp",
				"request" => "01.00.06.03.ffff",
				"id" => 3,
			], [
				"time" => "*/5 1 * * * * *",
				"service" => "BaseServiceForMQTT1",
				"type" => "dpa | raw-hdp",
				"request" => "00.00.0d.00.ffff.c0.00.00",
				"id" => 4,
			], [
				"time" => "*/5 1 * * * * *",
				"service" => "BaseServiceForMQTT1",
				"type" => "dpa | std-per-frc",
				"request" => "00.00.0d.00.ffff",
				"id" => 5,
			]
		];
		Assert::equal($expected, $manager->getTasks());
	}

	/**
	 * @test
	 * Test function to parse configuration of Scheduler
	 */
	public function testLoad() {
		$fileManager = new JsonFileManager($this->path);
		$manager = new SchedulerManager($fileManager);
		Assert::equal($this->array, $manager->load(0));
		Assert::equal([], $manager->load(10));
	}

	/**
	 * @test
	 * Test function to parse configuration of Scheduler
	 */
	public function testSave() {
		$fileManager = new JsonFileManager($this->pathTest);
		$manager = new SchedulerManager($fileManager);
		$array = $this->array;
		$array['nadr'] = '0';
		$expected = Json::decode(FileSystem::read($this->path . $this->fileName . '.json'), Json::FORCE_ARRAY);
		$fileManager->write($this->fileName, $expected);
		$expected['TasksJson'][0]['message']['nadr'] = '0';
		$manager->save(ArrayHash::from($array), 0);
		Assert::equal($expected, $fileManager->read($this->fileName));
	}

	/**
	 * @test
	 * Test function to parse configuration of Scheduler
	 */
	public function testSaveJson() {
		$fileManager = new JsonFileManager($this->path);
		$manager = new SchedulerManager($fileManager);
		$updateArray = $this->array;
		$updateArray['nadr'] = '0';
		$json = Json::decode(FileSystem::read($this->path . $this->fileName . '.json'), Json::FORCE_ARRAY);
		$expected = $json;
		$expected['TasksJson'][0]['message']['nadr'] = '0';
		$update = ArrayHash::from($updateArray);
		Assert::equal($expected, $manager->saveJson($json, $update, 0));
	}

}

$test = new SchedulerManagerTest($container);
$test->run();