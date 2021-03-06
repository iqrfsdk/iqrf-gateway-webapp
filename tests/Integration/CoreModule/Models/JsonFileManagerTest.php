<?php

/**
 * TEST: App\CoreModule\Models\JsonFileManager
 * @covers App\CoreModule\Models\JsonFileManager
 * @phpVersion >= 7.2
 * @testCase
 */
declare(strict_types = 1);

namespace Tests\Integration\CoreModule\Models;

use App\CoreModule\Entities\CommandStack;
use App\CoreModule\Models\CommandManager;
use App\CoreModule\Models\JsonFileManager;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for JSON file manager
 */
final class JsonFileManagerTest extends TestCase {

	/**
	 * Directory with configuration files
	 */
	private const CONFIG_PATH = __DIR__ . '/../../../data/configuration/';

	/**
	 * Directory with temporary configuration files
	 */
	private const CONFIG_TEMP_PATH = __DIR__ . '/../../../temp/configuration/';

	/**
	 * File name
	 */
	private const FILE_NAME = 'config';

	/**
	 * @var JsonFileManager JSON File manager
	 */
	private $manager;

	/**
	 * @var JsonFileManager JSON File manager
	 */
	private $managerTest;

	/**
	 * Tests the function to get a directory with files
	 */
	public function testGetDirectory(): void {
		Assert::same(self::CONFIG_PATH, $this->manager->getDirectory());
	}

	/**
	 * Tests the function to delete the JSON file
	 */
	public function testDelete(): void {
		$fileName = 'test-delete';
		FileSystem::copy(self::CONFIG_PATH . self::FILE_NAME . '.json', self::CONFIG_TEMP_PATH . $fileName . '.json');
		Assert::true($this->managerTest->exists($fileName));
		$this->managerTest->delete($fileName);
		Assert::false($this->managerTest->exists($fileName));
	}

	/**
	 * Tests the function to check if the JSON file exists (the file is not exist)
	 */
	public function testExistsFail(): void {
		Assert::false($this->manager->exists('nonsense'));
	}

	/**
	 * Tests the function to check if the JSON file exists (the file is exist)
	 */
	public function testExistsSuccess(): void {
		Assert::true($this->manager->exists(self::FILE_NAME));
	}

	/**
	 * Tests the function to read a JSON file
	 */
	public function testRead(): void {
		$text = FileSystem::read(self::CONFIG_PATH . self::FILE_NAME . '.json');
		$expected = Json::decode($text, Json::FORCE_ARRAY);
		Assert::equal($expected, $this->manager->read(self::FILE_NAME));
	}

	/**
	 * Tests the function to write a JSON file
	 */
	public function testWrite(): void {
		$fileName = 'config-test';
		$expected = $this->manager->read(self::FILE_NAME);
		$this->managerTest->write($fileName, $expected);
		Assert::equal($expected, $this->managerTest->read($fileName));
	}

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		$commandStack = new CommandStack();
		$commandManager = new CommandManager(false, $commandStack);
		$this->manager = new JsonFileManager(self::CONFIG_PATH, $commandManager);
		$this->managerTest = new JsonFileManager(self::CONFIG_TEMP_PATH, $commandManager);
	}

}

$test = new JsonFileManagerTest();
$test->run();
