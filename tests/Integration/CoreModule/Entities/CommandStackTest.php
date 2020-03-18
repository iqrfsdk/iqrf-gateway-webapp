<?php

/**
 * TEST: App\CoreModule\Entities\CommandStack
 * @covers App\CoreModule\Entities\CommandStack
 * @phpVersion >= 7.2
 * @testCase
 */
declare(strict_types = 1);

namespace Tests\Integration\CoreModule\Entities;

use App\CoreModule\Entities\Command;
use App\CoreModule\Entities\CommandStack;
use Symfony\Component\Process\Process;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for command stack entity
 */
class CommandStackTest extends TestCase {

	/**
	 * @var Command Command entity
	 */
	private $entity;

	/**
	 * @var string Command
	 */
	private $command = 'ls -al';

	/**
	 * @var CommandStack Command stack
	 */
	private $stack;

	/**
	 * Tests the function to return commands in stack (empty stack)
	 */
	public function testGetCommandsEmpty(): void {
		Assert::same([], $this->stack->getCommands());
	}

	/**
	 * Tests the function to add command into stack
	 */
	public function testAddCommand(): void {
		$this->stack->addCommand($this->entity);
		$commands = $this->stack->getCommands();
		Assert::same(1, count($commands));
		Assert::same($this->command, $commands[0]->getCommand());
	}

	/**
	 * Sets up the testing environment
	 */
	protected function setUp(): void {
		$process = Process::fromShellCommandline($this->command);
		$process->run();
		$this->entity = new Command($this->command, $process);
		$this->stack = new CommandStack();
		parent::setUp();
	}

}

$test = new CommandStackTest();
$test->run();
