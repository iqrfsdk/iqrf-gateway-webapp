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

use App\ServiceModule\Exceptions\UnsupportedInitSystemException;
use Nette\SmartObject;

/**
 * Tool for managing services (unknown init daemon)
 */
class UnknownManager implements IServiceManager {

	use SmartObject;

	/**
	 * Disables the service
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function disable(?string $serviceName = null): void {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Enables the service
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function enable(?string $serviceName = null): void {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Checks if the service is active
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function isActive(?string $serviceName = null): bool {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Checks if the service is enabled
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function isEnabled(?string $serviceName = null): bool {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Starts IQRF Gateway Daemon
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function start(?string $serviceName = null): void {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Stops the service
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function stop(?string $serviceName = null): void {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Restarts the service
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function restart(?string $serviceName = null): void {
		throw new UnsupportedInitSystemException();
	}

	/**
	 * Returns status of the service
	 * @var string|null $serviceName Service name
	 * @throws UnsupportedInitSystemException
	 */
	public function getStatus(?string $serviceName = null): string {
		throw new UnsupportedInitSystemException();
	}

}
