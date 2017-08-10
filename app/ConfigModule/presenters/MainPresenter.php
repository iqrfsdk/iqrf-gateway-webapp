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

use App\Forms\ConfigComponentsFormFactory;
use App\Forms\ConfigMainFormFactory;
use App\Presenters\BasePresenter;
use App\Model\ConfigManager;

class MainPresenter extends BasePresenter {

	/**
	 * @var ConfigComponentsFormFactory
	 */
	private $componentsFactory;

	/**
	 * @var ConfigMainFormFactory
	 */
	private $mainFactory;

	/**
	 * @var ConfigManager
	 */
	private $configManager;

	/**
	 * Constructor
	 * @param ConfigComponentsFormFactory $componentsFactory
	 * @param ConfigMainFormFactory $mainFactory
	 * @param ConfigManager $configManager
	 */
	public function __construct(ConfigComponentsFormFactory $componentsFactory, ConfigMainFormFactory $mainFactory, ConfigManager $configManager) {
		$this->componentsFactory = $componentsFactory;
		$this->mainFactory = $mainFactory;
		$this->configManager = $configManager;
	}

	/**
	 * Render Main configurator
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
	}

	/**
	 * Create components form
	 * @return Form Components form
	 */
	protected function createComponentConfigComponentsForm() {
		$this->onlyForAdmins();
		return $this->componentsFactory->create($this);
	}

	/**
	 * Create Main daemon settings form
	 * @return Form Main daemon settings form
	 */
	protected function createComponentConfigMainForm() {
		$this->onlyForAdmins();
		return $this->mainFactory->create($this);
	}

}