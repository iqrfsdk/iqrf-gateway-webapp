<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017 IQRF Tech s.r.o.
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

namespace App\CloudModule\Presenters;

use App\CloudModule\Forms\CloudAwsMqttFormFactory;
use App\Presenters\BasePresenter;

class AwsPresenter extends BasePresenter {

	/**
	 * @var CloudAwsMqttFormFactory
	 */
	private $formFactory;

	/**
	 * Constructor
	 * @param CloudAwsMqttFormFactory $formFactory
	 */
	public function __construct(CloudAwsMqttFormFactory $formFactory) {
		$this->formFactory = $formFactory;
	}

	/**
	 * Render default page
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
	}

	/**
	 * Create MQTT interface form
	 * @return Form MQTT interface form
	 */
	protected function createComponentCloudAwsMqttForm() {
		$this->onlyForAdmins();
		return $this->formFactory->create($this);
	}

}