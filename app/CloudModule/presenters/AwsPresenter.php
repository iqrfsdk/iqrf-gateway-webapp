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

use App\CloudModule\Forms\CloudAwsMqttFormFactory;
use App\Presenters\ProtectedPresenter;
use Nette\Forms\Form;

class AwsPresenter extends ProtectedPresenter {

	/**
	 * @var CloudAwsMqttFormFactory Amazon AWS IoT form factory
	 * @inject
	 */
	public $formFactory;

	/**
	 * Create MQTT interface form
	 * @return Form MQTT interface form
	 */
	protected function createComponentCloudAwsMqttForm(): Form {
		return $this->formFactory->create($this);
	}

}
