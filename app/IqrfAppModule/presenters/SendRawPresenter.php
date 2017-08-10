<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
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

namespace App\IqrfAppModule\Presenters;

use App\Forms\IqrfAppSendRawFormFactory;
use App\Model\IqrfAppManager;
use App\Model\IqrfAppParser;
use App\Model\IqrfMacroManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;

/**
 * Service presenter
 */
class SendRawPresenter extends BasePresenter {

	/**
	 * @var IqrfAppSendRawFormFactory
	 */
	private $sendRawFactory;

	/**
	 * @var IqrfAppManager
	 */
	private $iqrfAppManager;

	/**
	 * @var IqrfAppParser
	 */
	private $iqrfAppParser;

	/**
	 * @var IqrfMacroManager
	 */
	private $iqrfMacroManager;

	/**
	 * Constructor
	 * @param IqrfAppManager $manager
	 * @param IqrfAppParser $parser
	 * @param IqrfMacroManager $macroManager
	 * @param IqrfAppSendRawFormFactory $formFactory
	 */
	public function __construct(IqrfAppManager $manager, IqrfAppParser $parser, IqrfMacroManager $macroManager, IqrfAppSendRawFormFactory $formFactory) {
		$this->iqrfAppManager = $manager;
		$this->iqrfAppParser = $parser;
		$this->iqrfMacroManager = $macroManager;
		$this->sendRawFactory = $formFactory;
	}

	/**
	 * Render send raw DPA packet page
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
		$this->template->macros = $this->iqrfMacroManager->read();
	}

	/**
	 * AJAX handler for showing DPA response
	 * @param string $response DPA response
	 */
	public function handleShowResponse($response) {
		$this->template->response = $response;
		$this->template->parsedResponse = $this->iqrfAppParser->parseResponse($response);
		$this->redrawControl('responseChange');
	}

	/**
	 * Create send raw DPA packet form
	 * @return Form
	 */
	protected function createComponentIqrfAppSendRawForm() {
		$this->onlyForAdmins();
		return $this->sendRawFactory->create($this);
	}

}