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
use App\ApiModule\Version0\Utils\ContentTypeUtil;
use App\GatewayModule\Exceptions\UnknownFileFormatExceptions;
use App\IqrfNetModule\Entities\Dpa;
use App\IqrfNetModule\Enums\DpaInterfaces;
use App\IqrfNetModule\Enums\RfModes;
use App\IqrfNetModule\Enums\TrSeries;
use App\IqrfNetModule\Enums\UploadFormats;
use App\IqrfNetModule\Models\DpaManager;
use App\IqrfNetModule\Models\UploadManager;
use App\Models\Database\EntityManager;
use App\Models\Database\Repositories\IqrfOsPatchRepository;
use GuzzleHttp\Exception\ClientException;
use Nette\IOException;

/**
 * Upload controller
 * @Path("/")
 */
class UploadController extends IqrfController {

	/**
	 * @var UploadManager Upload manager
	 */
	private $uploadManager;

	/**
	 * @var DpaManager DPA manager
	 */
	private $dpaManager;

	/**
	 * @var IqrfOsPatchRepository IQRF OS patch database repository
	 */
	private $repository;

	/**
	 * Constructor
	 * @param DpaManager $dpaManager IQRF DPA Manager
	 * @param UploadManager $uploadManager Upload manager
	 * @param EntityManager $entityManager Entity manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(DpaManager $dpaManager, UploadManager $uploadManager, EntityManager $entityManager, RestApiSchemaValidator $validator) {
		$this->dpaManager = $dpaManager;
		$this->uploadManager = $uploadManager;
		$this->repository = $entityManager->getIqrfOsPatchRepository();
		parent::__construct($validator);
	}

	/**
	 * @Path("/upload")
	 * @Method("POST")
	 * @OpenApi("
	 *  summary: Uploads file
	 *  requestBody:
	 *      description: Uploads file
	 *      required: true
	 *      content:
	 *          multipart/form-data:
	 *              schema:
	 *                  type: object
	 *                  properties:
	 *                      format:
	 *                          enum: [hex, iqrf, trcnfg, '']
	 *                          type: string
	 *                      file:
	 *                          type: string
	 *
	 *  responses:
	 *      '200':
	 *          description: Success
	 *      '400':
	 *          $ref: '#/components/responses/BadRequest'
	 *      '415':
	 *          description: Unsupported media file
	 *      '500':
	 *          $ref: '#/components/responses/ServerError'
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function upload(ApiRequest $request, ApiResponse $response): ApiResponse {
		ContentTypeUtil::validContentType($request, ['multipart/form-data']);
		try {
			$format = $request->getParsedBody()['format'] ?? null;
			if ($format !== null) {
				$format = UploadFormats::fromScalar($format);
			}
			$file = $request->getUploadedFiles()[0];
			return $response->writeJsonBody($this->uploadManager->uploadFile($file->getClientFilename(), $file->getStream()->getContents(), $format));
		} catch (UnknownFileFormatExceptions $e) {
			throw new ClientErrorException('Invalid file format', ApiResponse::S400_BAD_REQUEST);
		} catch (IOException $e) {
			throw new ServerErrorException('Write failure', ApiResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @Path("/dpaFile")
	 * @Method("POST")
	 * @OpenApi("
	 *  summary: Retrieves DPA file
	 *  requestBody:
	 *      required: true
	 *      content:
	 *          application/json:
	 *              schema:
	 *                  $ref: '#/components/schemas/DpaFile'
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
	public function getDpaFile(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('dpaFile', $request);
		try {
			$data = $request->getJsonBody(false);
			$interface = DpaInterfaces::fromScalar($data->interfaceType);
			$trSeries = TrSeries::fromTrMcuType($data->trSeries);
			$rfMode = isset($data->rfMode) ? RfModes::fromScalar($data->rfMode) : null;
			$dpa = new Dpa($data->dpa, $interface, $trSeries, $rfMode);
			if (hexdec($dpa->getVersion()) < 0x400 && $rfMode === null) {
				throw new ClientErrorException('Missing RF mode for DPA Version < 4.00', ApiResponse::S400_BAD_REQUEST);
			}
			$fileName = $this->dpaManager->getFile($data->osBuild, $dpa);
			if ($fileName === null) {
				throw new ClientErrorException('DPA file not found', ApiResponse::S404_NOT_FOUND);
			}
			return $response->writeJsonBody(['fileName' => $fileName]);
		} catch (IOException $e) {
			throw new ServerErrorException('Filesystem failure', ApiResponse::S500_INTERNAL_SERVER_ERROR);
		} catch (ClientException $e) {
			throw new ServerErrorException('Download failure', ApiResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @Path("/osPatches")
	 * @Method("GET")
	 * @OpenApi("
	 *  summary: Lists all IQRF OS patch files
	 *  responses:
	 *      '200':
	 *          description: Success
	 *          content:
	 *              application/json:
	 *                  schema:
	 *                      type: array
	 *                      items:
	 *                          $ref: '#/components/schemas/IqrfOsPatchDetail'
	 * ")
	 * @param ApiRequest $request API request
	 * @param ApiResponse $response API response
	 * @return ApiResponse API response
	 */
	public function listOsFiles(ApiRequest $request, ApiResponse $response): ApiResponse {
		$patches = [];
		foreach ($this->repository->findAll() as $patch) {
			array_push($patches, $patch->jsonSerialize());
		}
		return $response->writeJsonBody($patches);
	}

}
