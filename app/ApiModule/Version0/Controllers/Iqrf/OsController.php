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

namespace App\ApiModule\Version0\Controllers\Iqrf;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version0\Models\RestApiSchemaValidator;
use App\ApiModule\Version0\Controllers\IqrfController;
use App\IqrfNetModule\Entities\IqrfOs;
use App\IqrfNetModule\Models\IqrfOsManager;

/**
 * IQRF OS controller
 * @Path("/")
 */
class OsController extends IqrfController {

	/**
	 * @var IqrfOsManager IQRF OS manager
	 */
	private $osManager;

	/**
	 * Constructor
	 * @param IqrfOsManager $osManager IQRF OS manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(IqrfOsManager $osManager, RestApiSchemaValidator $validator) {
		$this->osManager = $osManager;
		parent::__construct($validator);
	}

	/**
	 * @Path("/osUpgrades")
	 * @Method("POST")
	 * @OpenApi("
	 *  summary: Lists all available IQRF OS patch upgrades
	 *  responses:
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function listOsUpgrades(ApiRequest $request, ApiResponse $response): ApiResponse {
		$data = $request->getJsonBody(false);
		$os = IqrfOs::fromOsRead((array) $data);
		return $response->writeJsonBody($this->osManager->list($os));
	}
}
