<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2018 IQRF Tech s.r.o.
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

namespace App\CloudModule\Presenters;

use App\CloudModule\Forms\IbmCloudFormFactory;
use App\CoreModule\Models\JsonFileManager;
use App\CoreModule\Presenters\ProtectedPresenter;
use Nette\Forms\Form;
use Nette\Utils\JsonException;

/**
 * IBM Cloud presenter
 */
class IbmCloudPresenter extends ProtectedPresenter {

	/**
	 * @var IbmCloudFormFactory IBM Cloud form factory
	 * @inject
	 */
	public $formFactory;

	/**
	 * @var JsonFileManager JSON file manager
	 */
	private $fileManager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->fileManager = new JsonFileManager(__DIR__ . '/../json/');
		parent::__construct();
	}

	/**
	 * Create MQTT interface form
	 * @return Form MQTT interface form
	 */
	protected function createComponentCloudIbmForm(): Form {
		return $this->formFactory->create($this);
	}

	/**
	 * Render MQTT interface creation form
	 * @throws JsonException
	 */
	public function renderDefault(): void {
		$this->template->guides = $this->fileManager->read('guides')['ibm-cloud'];
	}

}