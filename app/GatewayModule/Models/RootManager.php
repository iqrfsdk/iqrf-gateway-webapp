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
use App\GatewayModule\Exceptions\ChpasswdErrorException;

/**
 * Root account manager
 */
class RootManager {

	/**
	 * @var CommandManager CommandManager
	 */
	private $commandManager;

	/**
	 * Constructor
	 * @param CommandManager $commandManager CommandManager
	 */
	public function __construct(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Changes root account password
	 * @param string $password New password to set
	 */
	public function setPassword(string $password): void {
		$input = 'root:' . $password;
		$output = $this->commandManager->run('chpasswd', true, $input);
		if ($output->getExitCode() !== 0) {
			throw new ChpasswdErrorException($output->getStderr());
		}
	}

}
