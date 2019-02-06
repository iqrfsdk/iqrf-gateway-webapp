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
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * IQRF Gateway's board manager
 */
class IqrfBoardInfoManager implements IBoardInfoManager {

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
	 * Gets IQRF Gateway's board's name
	 * @return string|null IQRF Gateway's board's name
	 */
	public function getName(): ?string {
		$gwJson = $this->commandManager->send('cat /etc/iqrf-gateway.json', true);
		if ($gwJson === '') {
			return null;
		}
		try {
			$gw = Json::decode($gwJson, Json::FORCE_ARRAY);
			return $gw['gwManufacturer'] . ' ' . $gw['gwProduct'];
		} catch (JsonException $e) {
			return null;
		}
	}

}
