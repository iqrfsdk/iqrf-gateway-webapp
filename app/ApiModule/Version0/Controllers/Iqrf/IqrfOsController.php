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
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version0\Controllers\IqrfController;
use App\ApiModule\Version0\Models\RestApiSchemaValidator;
use App\IqrfNetModule\Exceptions\DpaFileNotFoundException;
use App\IqrfNetModule\Exceptions\DpaRfMissingException;
use App\IqrfNetModule\Exceptions\UploadUtilFileException;
use App\IqrfNetModule\Exceptions\UploadUtilMissingException;
use App\IqrfNetModule\Exceptions\UploadUtilSpiException;
use App\IqrfNetModule\Models\IqrfOsManager;
use App\IqrfNetModule\Models\UploadUtilManager;
use GuzzleHttp\Exception\ClientException;
use Nette\IOException;

/**
 * IQRF OS controller
 * @Path("/")
 */
class IqrfOsController extends IqrfController {

	/**
	 * @var IqrfOsManager IQRF OS manager
	 */
	private $iqrfOsManager;

	/**
	 * @var UploadUtilManager IQRF Upload Utility manager
	 */
	private $uploadUtilManager;

	/**
	 * Constructor
	 * @param IqrfOsManager $iqrfOsManager IQRF OS manager
	 * @param UploadUtilManager $uploadUtilManager IQRF Upload Utility manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(IqrfOsManager $iqrfOsManager, UploadUtilManager $uploadUtilManager, RestApiSchemaValidator $validator) {
		$this->iqrfOsManager = $iqrfOsManager;
		$this->uploadUtilManager = $uploadUtilManager;
		parent::__construct($validator);
	}

	/**
	 * @Path("/osPatches")
	 * @Method("GET")
	 * @OpenApi("
	 *  summary: Lists all IQRF OS patches
	 *  responses:
	 *      '200':
	 *          description: Success
	 *          content:
	 *              application/json:
	 *                  schema:
	 *                      type: array
	 *                      items:
	 *                         $ref: '#/components/schemas/IqrfOsPatchDetail'
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function listOsPatches(ApiRequest $request, ApiResponse $response): ApiResponse {
		$patches = $this->iqrfOsManager->listOsPatches();
		return $response->writeJsonBody($patches);
	}

	/**
	 * @Path("/osUpgrades")
	 * @Method("POST")
	 * @OpenApi("
	 *  summary: Lists all IQRF OS upgrades
	 *  requestBody:
	 *      required: true
	 *      content:
	 *          application/json:
	 *              schema:
	 *                  $ref: '#/components/schemas/IqrfOsPatchUpgrade'
	 *  responses:
	 *      '200':
	 *          description: Success
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function listOsUpgrades(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('iqrfOsPatchUpgrade', $request);
		$data = $request->getJsonBody(false);
		$upgrades = $this->iqrfOsManager->listOsUpgrades($data->version, $data->build, $data->mcuType);
		return $response->writeJsonBody($upgrades);
	}

	/**
	 * @Path("/osUpgradeFiles")
	 * @Method("POST")
	 * @OpenApi("
	 *  summary: Retrieves IQRF OS and DPA upgrade file names
	 *  requestBody:
	 *      required: true
	 *      content:
	 *          application/json:
	 *              schema:
	 *                  $ref: '#/components/schemas/IqrfOsDpaUpgrade'
	 *  responses:
	 *      '200':
	 *          description: Success
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function osUpgradeFiles(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('iqrfOsDpaUpgrade', $request);
		try {
			$files = $this->iqrfOsManager->getUpgradeFiles((array) $request->getJsonBody(false));
			return $response->writeJsonBody($files);
		} catch (DpaRfMissingException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST);
		} catch (DpaFileNotFoundException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S404_NOT_FOUND);
		} catch (IOException $e) {
			throw new ServerErrorException('Filesystem failure', ApiResponse::S500_INTERNAL_SERVER_ERROR);
		} catch (ClientException $e) {
			throw new ServerErrorException('Download failure', ApiResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @Path("/utilUpload")
	 * @Method("POST")
	 * @OpenApi("
	 *  summary: Executes upload using the IQRF Upload Utility
	 *  requestBody:
	 *      required: true
	 *      content:
	 *          application/json:
	 *              schema:
	 *                  $ref: '#/components/schemas/UploadUtil'
	 *  responses:
	 *      '200':
	 *          description: Success
	 *      '400':
	 *          $ref: '#/components/responses/BadRequest'
	 *      '404':
	 *          description: Not found
	 *      '500':
	 *          $ref: '#/components/responses/ServerError'
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function utilUpload(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('uploadUtil', $request);
		try {
			$data = $request->getJsonBody(false);
			$this->uploadUtilManager->executeUpload($data->files);
			return $response->writeBody('Workaround');
		} catch (UploadUtilFileException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST);
		} catch (UploadUtilMissingException | UploadUtilSpiException $e) {
			throw new ServerErrorException($e->getMessage(), ApiResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

}
