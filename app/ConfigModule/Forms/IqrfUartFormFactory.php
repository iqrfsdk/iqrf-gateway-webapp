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

namespace App\ConfigModule\Forms;

use App\ConfigModule\Presenters\IqrfUartPresenter;
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\JsonException;

/**
 * IQRF UART configuration form factory
 */
class IqrfUartFormFactory extends GenericConfigFormFactory {

	use SmartObject;

	/**
	 * @var int[] UART baud rates
	 */
	private $baudRates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200, 230400];

	/**
	 * Creates the IQRF UART interface configuration form
	 * @param IqrfUartPresenter $presenter IQRF UART interfaceconfiguration presenter
	 * @return Form IQRF UART interface configuration form
	 * @throws JsonException
	 */
	public function create(IqrfUartPresenter $presenter): Form {
		$this->manager->setComponent('iqrf::IqrfUart');
		$this->presenter = $presenter;
		$form = $this->factory->create('config.iqrfUart.form');
		$form->addText('instance', 'instance')
			->setRequired('messages.instance');
		$form->addText('IqrfInterface', 'IqrfInterface')
			->setRequired('messages.IqrfInterface');
		$form->addSelect('baudRate', 'config.iqrfUart.form.baudRate')
			->setTranslator($this->factory->getTranslator())
			->setItems($this->baudRates, false);
		$form->addInteger('powerEnableGpioPin', 'powerEnableGpioPin');
		$form->addInteger('busEnableGpioPin', 'busEnableGpioPin');
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		$form->setDefaults($this->manager->load(0));
		$form->onSuccess[] = [$this, 'save'];
		return $form;
	}

}