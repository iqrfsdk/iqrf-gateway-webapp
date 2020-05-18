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
use App\IqrfNetModule\Exceptions\UserErrorException;
use App\IqrfNetModule\Requests\ApiRequest;
use Nette\Utils\Arrays;
use Nette\Utils\JsonException;

/**
 * Tool for managing TR configuration
 */
class TrConfigManager {

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
	 * Reads TR's configuration
	 * @param int $address Device address to read the configuration from
	 * @return mixed[] API request and response
	 * @throws DpaErrorException
	 * @throws EmptyResponseException
	 * @throws JsonException
	 * @throws UserErrorException
	 */
	public function read(int $address = 0): array {
		$array = [
			'mType' => 'iqmeshNetwork_ReadTrConf',
			'data' => [
				'repeat' => 2,
				'req' => [
					'deviceAddr' => $address,
				],
				'returnVerbose' => true,
			],
		];
		$this->request->set($array);
		return $this->wsClient->sendSync($this->request);
	}

	/**
	 * Writes TR's configuration
	 * @param int $address Device address to write the configuration to
	 * @param mixed[] $config New TR configuration
	 * @return mixed[] DPA request and response
	 * @throws DpaErrorException
	 * @throws EmptyResponseException
	 * @throws JsonException
	 * @throws UserErrorException
	 */
	public function write(int $address = 0, array $config = []): array {
		$array = [
			'mType' => 'iqmeshNetwork_WriteTrConf',
			'data' => [
				'repeat' => 2,
				'req' => [
					'deviceAddr' => $address,
				],
				'returnVerbose' => true,
			],
		];
		$array['data']['req'] = Arrays::mergeTree($array['data']['req'], $config);
		$this->request->set($array);
		return $this->wsClient->sendSync($this->request);
	}

}
