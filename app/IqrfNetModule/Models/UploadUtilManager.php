<?php

/**
 * Copyright 2020 MICRORISC s.r.o.
 * Copyright 2017-2020 IQRF Tech s.r.o.
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

namespace App\IqrfNetModule\Models;

use App\CoreModule\Models\CommandManager;
use App\IqrfNetModule\Exceptions\UploadUtilMissingException;
use App\ServiceModule\Models\ServiceManager;

/**
 * IQRF Upload utility manager
 */
class UploadUtilManager {

	/**
	 * IQRF Gateway Daemon service name
	 */
	private const DAEMON = 'iqrf-gateway-daemon';

	/**
	 * @var CommandManager Command manager
	 */
	private $commandManager;

	/**
	 * @var ServiceManager Service Manager
	 */
	private $serviceManager;

	/**
	 * Constructor
	 * @param CommandManager $commandManager Command manager
	 * @param ServiceManager $serviceManager Service Manager
	 */
	public function __construct(CommandManager $commandManager, ServiceManager $serviceManager) {
		$this->commandManager = $commandManager;
		$this->serviceManager = $serviceManager;
	}

	/**
	 * Uploads file content using the IQRF Upload Utility
	 * @param array<int, array<string, string>> $files Array of files to upload
	 */
	public function executeUpload(array $files): void {
		if (!$this->commandManager->commandExist('iqrf-upload-util')) {
			throw new UploadUtilMissingException('IQRF Upload Utility is not installed.');
		}
		$this->serviceManager->disable(self::DAEMON);
		$this->serviceManager->enable(self::DAEMON);
	}

}
