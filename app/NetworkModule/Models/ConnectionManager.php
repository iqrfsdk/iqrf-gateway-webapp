<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2019 IQRF Tech s.r.o.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
declare(strict_types = 1);

namespace App\NetworkModule\Models;

use App\CoreModule\Models\CommandManager;
use App\NetworkModule\Entities\Connection;
use App\NetworkModule\Entities\ConnectionDetail;
use Ramsey\Uuid\UuidInterface;
use stdClass;

/**
 * Network connection manager
 */
class ConnectionManager {

	/**
	 * @var CommandManager Command manager
	 */
	private $commandManager;

	/**
	 * Constructor
	 * @param CommandManager $commandManager Command manager
	 */
	public function __construct(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Returns the detailed network connection entity
	 * @param UuidInterface $uuid Network connection UUID
	 * @return ConnectionDetail Detailed network connection entity
	 */
	public function get(UuidInterface $uuid): ConnectionDetail {
		$output = $this->commandManager->run('nmcli -t connection show ' . $uuid->toString(), true)->getStdout();
		return ConnectionDetail::fromNmCli($output);
	}

	/**
	 * Lists the network connections
	 * @return Connection[] Network connections
	 */
	public function list(): array {
		$output = $this->commandManager->run('nmcli -t connection show', true)->getStdout();
		$array = explode(PHP_EOL, trim($output));
		foreach ($array as &$row) {
			$row = Connection::fromString($row);
		}
		return $array;
	}

	/**
	 * Sets the network connection's configuration
	 * @param ConnectionDetail $connection Detailed network connection entity
	 * @param stdClass $values Network connection configuration form values
	 */
	public function set(ConnectionDetail $connection, stdClass $values): void {
		$connection->fromForm($values);
		$uuid = $connection->getUuid()->toString();
		$configuration = $connection->toNmCli();
		$command = sprintf('nmcli -t connection modify %s %s', $uuid, $configuration);
		$this->commandManager->run($command, true);
	}

}
