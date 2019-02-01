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

namespace App\IqrfNetModule\Forms;

use App\CoreModule\Forms\FormFactory;
use App\CoreModule\Models\JsonSchemaManager;
use App\IqrfNetModule\Exceptions\DpaErrorException;
use App\IqrfNetModule\Exceptions\EmptyResponseException;
use App\IqrfNetModule\Models\WebSocketClient;
use App\IqrfNetModule\Presenters\SendJsonPresenter;
use App\IqrfNetModule\Requests\DpaRequest;
use Nette\Forms\Form;
use Nette\SmartObject;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Send raw JSON request form factory
 */
class SendJsonFormFactory {

	use SmartObject;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * @var JsonSchemaManager API JSON schema manager
	 */
	private $jsonSchemaManager;

	/**
	 * @var SendJsonPresenter IQRF Send raw JSON DPA request presenter
	 */
	private $presenter;

	/**
	 * @var DpaRequest JSON DPA request
	 */
	private $request;

	/**
	 * @var WebSocketClient WebSocket client
	 */
	private $wsClient;

	/**
	 * Constructor
	 * @param JsonSchemaManager $jsonSchemaManager API JSON schema manager
	 * @param FormFactory $factory Generic form factory
	 * @param DpaRequest $request JSON DPA request
	 * @param WebSocketClient $wsClient WebSocket client
	 */
	public function __construct(JsonSchemaManager $jsonSchemaManager, FormFactory $factory, DpaRequest $request, WebSocketClient $wsClient) {
		$this->factory = $factory;
		$this->jsonSchemaManager = $jsonSchemaManager;
		$this->request = $request;
		$this->wsClient = $wsClient;
	}

	/**
	 * Create IQRF App send JSON request form
	 * @param SendJsonPresenter $presenter IQRF Send JSON request presenter
	 * @return Form IQRF App send RAW packet form
	 */
	public function create(SendJsonPresenter $presenter): Form {
		$this->presenter = $presenter;
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('iqrfnet.send-json'));
		$form->addTextArea('json', 'json')->setRequired('messages.json');
		$form->addSubmit('send', 'send');
		$form->addProtection('core.errors.form-timeout');
		$form->onSuccess[] = [$this, 'onSuccess'];
		return $form;
	}

	/**
	 * Send raw DPA packet
	 * @param Form $form IQRF App send RAW packet form
	 */
	public function onSuccess(Form $form): void {
		$values = $form->getValues();
		$json = [];
		try {
			$json = Json::decode($values['json']);
			if (isset($json->mType)) {
				$this->jsonSchemaManager->setSchemaFromMessageType($json->mType);
				$this->jsonSchemaManager->validate($json, true);
			}
		} catch (JsonException $e) {
			$this->presenter->flashMessage('iqrfnet.send-json.messages.invalidJson', 'danger');
		}
		try {
			$this->request->setRequest($json);
			$response = $this->wsClient->sendSync($this->request, false);
			$this->presenter->handleShowResponse($response);
		} catch (EmptyResponseException | DpaErrorException | JsonException $e) {
			$this->presenter->flashMessage('iqrfnet.webSocketClient.messages.emptyResponse', 'danger');
		}
	}

}
