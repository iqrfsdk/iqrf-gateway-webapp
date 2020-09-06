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

namespace App\IqrfNetModule\Models;

use App\IqrfNetModule\Exceptions\DpaErrorException;
use App\IqrfNetModule\Exceptions\EmptyResponseException;
use App\IqrfNetModule\Exceptions\InvalidOperationModeException;
use App\IqrfNetModule\Exceptions\UserErrorException;
use App\IqrfNetModule\Requests\ApiRequest;
use Nette\Utils\JsonException;

/**
 * Tool for controlling IQRF Gateway Daemon's mode
 */
class GwModeManager {

	/**
	 * @var ApiRequest JSON API request
	 */
	private $request;

	/**
	 * @var WebSocketClient WebSocket client
	 */
	private $wsClient;

	/**
	 * Constructor
	 * @param ApiRequest $request JSON API request
	 * @param WebSocketClient $wsClient WebSocket client
	 */
	public function __construct(ApiRequest $request, WebSocketClient $wsClient) {
		$this->request = $request;
		$this->wsClient = $wsClient;
	}

	/**
	 * Sets IQRF Gateway Daemon's operation mode
	 * @param string $mode IQRF Gateway Daemon's operation mode
	 * @throws DpaErrorException
	 * @throws EmptyResponseException
	 * @throws InvalidOperationModeException
	 * @throws JsonException
	 * @throws UserErrorException
	 */
	public function set(string $mode): void {
		$modes = ['forwarding', 'operational', 'service'];
		if (!in_array($mode, $modes, true)) {
			throw new InvalidOperationModeException();
		}
		$request = [
			'mType' => 'mngDaemon_Mode',
			'data' => [
				'req' => [
					'operMode' => $mode,
				],
				'returnVerbose' => true,
			],
		];
		$this->request->set($request);
		$this->wsClient->sendSync($this->request);
	}

	/**
	 * Returns the current IQRF Gateway Daemon's operation mode
	 * @return string Current IQRF Gateway Daemon's operation mode
	 * @throws DpaErrorException
	 * @throws EmptyResponseException
	 * @throws JsonException
	 * @throws UserErrorException
	 */
	public function get(): string {
		$request = [
			'mType' => 'mngDaemon_Mode',
			'data' => [
				'req' => [
					'operMode' => '',
				],
				'returnVerbose' => true,
			],
		];
		$this->request->set($request);
		$api = $this->wsClient->sendSync($this->request);
		return $api['response']->data->rsp->operMode ?? 'unknown';
	}

}