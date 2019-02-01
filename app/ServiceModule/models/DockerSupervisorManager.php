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

namespace App\ServiceModule\Models;

use App\CoreModule\Models\CommandManager;
use Nette\SmartObject;

/**
 * Tool for managing services (supervisord init daemon in a Docker container)
 */
class DockerSupervisorManager implements IServiceManager {

	use SmartObject;

	/**
	 * @var CommandManager Command Manager
	 */
	private $commandManager;

	/**
	 * @var string Name of service
	 */
	private $serviceName = 'iqrf-gateway-daemon';

	/**
	 * Constructor
	 * @param CommandManager $commandManager Command manager
	 */
	public function __construct(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Start IQRF Gateway Daemon's service
	 * @return string Output from init daemon
	 */
	public function start(): string {
		$cmd = 'supervisorctl start ' . $this->serviceName;
		return $this->commandManager->send($cmd, true);
	}

	/**
	 * Stop IQRF Gateway Daemon's service
	 * @return string Output from init daemon
	 */
	public function stop(): string {
		$cmd = 'supervisorctl stop ' . $this->serviceName;
		return $this->commandManager->send($cmd, true);
	}

	/**
	 * Restart IQRF Gateway Daemon's service
	 * @return string Output from init daemon
	 */
	public function restart(): string {
		$cmd = 'supervisorctl restart ' . $this->serviceName;
		return $this->commandManager->send($cmd, true);
	}

	/**
	 * Get status of IQRF Gateway Daemon's service
	 * @return string Output from init daemon
	 */
	public function getStatus(): string {
		$cmd = 'supervisorctl status ' . $this->serviceName;
		return $this->commandManager->send($cmd, true);
	}

}
