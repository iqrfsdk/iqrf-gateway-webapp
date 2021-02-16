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

namespace App\ConsoleModule\Commands;

use App\CoreModule\Models\CommandManager;
use App\Models\Database\EntityManager;
use App\Models\Database\Repositories\WireguardInterfaceRepository;

abstract class WireguardCommand extends EntityManagerCommand {

	public const WG_DIR = '/tmp/wireguard/';

	/**
	 * @var CommandManager Command manager
	 */
	protected $commandManager;

	/**
	 * @var WireguardInterfaceRepository Wireguard interface repository
	 */
	protected $repository;

	/**
	 * Constructor
	 * @param CommandManager $commandManager Command manager
	 * @param EntityManager $entityManager Entity manager
	 * @param string|null $name Command name
	 */
	public function __construct(CommandManager $commandManager, EntityManager $entityManager, ?string $name = null) {
		parent::__construct($entityManager, $name);
		$this->commandManager = $commandManager;
		$this->repository = $entityManager->getWireguardInterfaceRepository();
	}

}
