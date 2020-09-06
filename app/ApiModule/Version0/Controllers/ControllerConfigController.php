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

namespace App\ApiModule\Version0\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ConfigModule\Models\ControllerConfigManager;
use Nette\IOException;
use Nette\Utils\JsonException;

/**
 * IQRF Gateway Controller Controller
 * @Path("/controllerConfig")
 * @Tag("IQRF Gateway Controller configuration")
 */
class ControllerConfigController extends BaseController {

	/**
	 * @var ControllerConfigManager $manager IQRF Gateway Controller configuration manager
	 */
	private $manager;

	/**
	 * Constructor
	 * @param ControllerConfigManager $manager IQRF Gateway Controller configuration manager
	 */
	public function __construct(ControllerConfigManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * @Path("/")
	 * @Method("GET")
	 * @OpenApi("
	 *  summary: Returns current configuration of IQRF Gateway Controller
	 *  responses:
	 *      '200':
	 *          description: Success
	 *          content:
	 *              application/json:
	 *                  schema:
	 *                      $ref: '#/components/schemas/ControllerConfig'
	 *      '500':
	 *          $ref: '#/components/responses/ServerError'
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function getConfig(ApiRequest $request, ApiResponse $response): ApiResponse {
		try {
			$config = $this->manager->getConfig();
			return $response->writeJsonBody($config);
		} catch (JsonException $e) {
			throw new ServerErrorException('Invalid JSON syntax', ApiResponse::S500_INTERNAL_SERVER_ERROR);
		} catch (IOException $e) {
			throw new ServerErrorException($e->getMessage(), ApiResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @Path("/")
	 * @Method("PUT")
	 * @OpenApi("
	 *  summary: Saves new configuration of IQRF Gateway Controller
	 *  requestBody:
	 *      required: true
	 *      content:
	 *          application/json:
	 *              schema:
	 *                  $ref: '#/components/schemas/ControllerConfig'
	 *  responses:
	 *      '200':
	 *          description: Success
	 *      '400':
	 *          $ref: '#/components/responses/BadRequest'
	 *      '500':
	 *          $ref: '#/components/responses/ServerError'
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function setConfig(ApiRequest $request, ApiResponse $response): ApiResponse {
		try {
			$this->manager->saveConfig($request->getJsonBody());
			return $response;
		} catch (JsonException $e) {
			throw new ClientErrorException('Invalid JSON syntax', ApiResponse::S400_BAD_REQUEST);
		} catch (IOException $e) {
			throw new ServerErrorException($e->getMessage(), ApiResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

}
