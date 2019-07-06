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

namespace App\GatewayModule\Presenters;

use App\CoreModule\Presenters\ProtectedPresenter;
use App\GatewayModule\Models\PixlaManager;

/**
 * PIXLA management system presenter
 */
class PixlaPresenter extends ProtectedPresenter {

	/**
	 * @var PixlaManager PIXLA management system manager
	 */
	private $manager;

	/**
	 * Constructor
	 * @param PixlaManager $manager PIXLa management system manager
	 */
	public function __construct(PixlaManager $manager) {
		$this->manager = $manager;
		parent::__construct();
	}

	/**
	 * Checks if the PIXLA manager is enabled
	 */
	protected function startup(): void {
		parent::startup();
		if (!$this->context->parameters['features']['pixla']) {
			$this->flashError('gateway.pixla.messages.disabled');
			$this->redirect('Homepage:default');
		}
	}

	/**
	 * Disables and stops PIXLA client
	 */
	public function actionDisable(): void {
		$this->manager->disableService();
		$this->flashSuccess('gateway.pixla.messages.disable');
		$this->setView('default');
		$this->redirect('Pixla:default');
	}

	/**
	 * Enables and starts PIXLA client
	 */
	public function actionEnable(): void {
		$this->manager->enableService();
		$this->flashSuccess('gateway.pixla.messages.enable');
		$this->setView('default');
		$this->redirect('Pixla:default');
	}

	/**
	 * Renders the default page
	 */
	public function renderDefault(): void {
		$this->template->status = $this->manager->getServiceStatus()->toScalar();
		$this->template->token = $this->manager->getToken();
	}

}