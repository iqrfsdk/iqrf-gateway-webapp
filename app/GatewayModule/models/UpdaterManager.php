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

namespace App\GatewayModule\Models;

use App\CoreModule\Models\CommandManager;
use Nette\SmartObject;

/**
 * Tool for updating packages of IQRF Gateways
 */
class UpdaterManager {

	use SmartObject;

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
	 * Lists upgradable packages
	 * @param callable $callback Callback
	 */
	public function listUpgradable(callable $callback): void {
		$this->commandManager->runAsync($callback, 'apt-get --just-print upgrade', true);
	}

	/**
	 * Updates a list of packages
	 * @param callable $callback Callback
	 */
	public function update(callable $callback): void {
		$this->commandManager->runAsync($callback, 'apt-get update', true);
	}

	/**
	 * Upgrades packages
	 * @param callable $callback Callback
	 */
	public function upgrade(callable $callback): void {
		$this->commandManager->runAsync($callback, 'apt-get upgrade -y', true);
	}

}
