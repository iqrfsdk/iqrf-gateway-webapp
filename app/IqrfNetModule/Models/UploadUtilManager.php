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

use App\CoreModule\Entities\ICommand;
use App\CoreModule\Models\CommandManager;
use App\IqrfNetModule\Exceptions\UploadUtilFileException;
use App\IqrfNetModule\Exceptions\UploadUtilMissingException;
use App\IqrfNetModule\Exceptions\UploadUtilSpiException;
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
	 * Path to uploaded files
	 */
	private const UPLOAD_DIR = '/var/cache/iqrf-gateway-daemon/upload/';

	/**
	 * IQRF Upload Utility command
	 */
	private const UPLOAD_UTIL = 'iqrf-upload-util';

	/**
	 * IQRF Upload Utility launch config
	 */
	private const UPLOAD_UTIL_CONF = '-c /etc/iqrf-upload-util/config.json';

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
	 * @param array<int, mixed> $files Array of files to upload
	 */
	public function executeUpload(array $files): void {
		if (!$this->commandManager->commandExist(self::UPLOAD_UTIL)) {
			throw new UploadUtilMissingException('IQRF Upload Utility is not installed.');
		}
		$this->serviceManager->stop(self::DAEMON);
		foreach ($files as $file) {
			if ($file->type === 'OS') {
				$result = $this->commandManager->run(self::UPLOAD_UTIL . ' ' . self::UPLOAD_UTIL_CONF . ' -I ' . $file->name, true);
			} else {
				$result = $this->commandManager->run(self::UPLOAD_UTIL . ' ' . self::UPLOAD_UTIL_CONF . ' -I ' . self::UPLOAD_DIR . $file->name, true);
			}
			if ($result->getExitCode() !== 0) {
				$this->handleError($result);
			}
		}
		$this->serviceManager->start(self::DAEMON);
	}

	/**
	 * Handles IQRF Upload Utility errors
	 * @param ICommand $result Command result
	 */
	private function handleError(ICommand $result): void {
		$command = $result->getCommand();
		$exitCode = $result->getExitCode();
		$errorMsg = $result->getStderr();
		if ($exitCode >= 1 && $exitCode <= 5) {
			$this->serviceManager->start(self::DAEMON);
			throw new UploadUtilFileException($errorMsg . ' Command executed: ' . $command);
		}
		throw new UploadUtilSpiException($errorMsg . ' Command executed: ' . $command);
	}

}
