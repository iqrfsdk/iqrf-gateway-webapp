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

namespace App\ConfigModule\Presenters;

use App\Forms\ConfigIqrfFormFactory;
use App\Presenters\BasePresenter;
use App\Model\ConfigManager;
use App\Model\InterfaceManager;

class IqrfPresenter extends BasePresenter {

	/**
	 * @var ConfigIqrfFormFactory
	 */
	private $formFactory;

	/**
	 * @var ConfigManager
	 */
	private $configManager;

	/**
	 * @var InterfaceManager
	 */
	private $interfaceManager;

	/**
	 * Constructor
	 * @param ConfigIqrfFormFactory $formFactory
	 * @param ConfigManager $configManager
	 * @param InterfaceManager $interfaceManager
	 */
	public function __construct(ConfigIqrfFormFactory $formFactory, ConfigManager $configManager, InterfaceManager $interfaceManager) {
		$this->configManager = $configManager;
		$this->formFactory = $formFactory;
		$this->interfaceManager = $interfaceManager;
	}

	/**
	 * Render IQRF interface configurator
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
		$this->template->interfaces = $this->interfaceManager->createInterfaceList();
	}


	/**
	 * Create IQRF interface form
	 * @return Form IQRF interface form
	 */
	protected function createComponentConfigIqrfForm() {
		$this->onlyForAdmins();
		return $this->formFactory->create($this);
	}

}