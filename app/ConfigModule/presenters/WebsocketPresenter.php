<?php

/**
 * Copyright 2018 IQRF Tech s.r.o.
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

namespace App\ConfigModule\Presenters;

use App\ConfigModule\Forms\ConfigWebsocketMessagingFormFactory;
use App\ConfigModule\Forms\ConfigWebsocketServiceFormFactory;
use App\Presenters\BasePresenter;
use Nette\Forms\Form;

class WebsocketPresenter extends BasePresenter {

	/**
	 * @var ConfigWebsocketMessagingFormFactory Websocket messaging configuration form factory
	 * @inject
	 */
	public $messagingFormFactory;

	/**
	 * @var ConfigWebsocketServiceFormFactory Websocket service configuration form factory
	 * @inject
	 */
	public $serviceFormFactory;

	/**
	 * Render websocket interface configurator
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
	}

	/**
	 * Create websocket messaging form
	 * @return Form Websocket messaging form
	 */
	protected function createComponentConfigWebsocketMessagingForm(): Form {
		$this->onlyForAdmins();
		return $this->messagingFormFactory->create($this);
	}

	/**
	 * Create websocket service form
	 * @return Form Websocket service form
	 */
	protected function createComponentConfigWebsocketServiceForm(): Form {
		$this->onlyForAdmins();
		return $this->serviceFormFactory->create($this);
	}

}
