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

namespace App\ConfigModule\Forms;

use App\ConfigModule\Model\GenericManager;
use App\ConfigModule\Presenters\IqrfPresenter;
use App\Forms\FormFactory;
use Nette;
use Nette\Forms\Form;

class ConfigIqrfSpiFormFactory {

	use Nette\SmartObject;

	/**
	 * @var GenericManager Generic config manager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * Constructor
	 * @param FormFactory $factory Generic form factory
	 * @param GenericManager $manager Generic config manager
	 */
	public function __construct(FormFactory $factory, GenericManager $manager) {
		$this->factory = $factory;
		$this->manager = $manager;
	}

	/**
	 * Create IQRF SPI configuration form
	 * @param IqrfPresenter $presenter
	 * @return Form IQRF configuration form
	 */
	public function create(IqrfPresenter $presenter): Form {
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('config.iqrfSpi.form'));
		$this->manager->setComponent('iqrf::IqrfSpi');
		$this->manager->setFileName($this->manager->getInstanceFiles()[0]);
		$form->addText('instance', 'instance')->setRequired('messages.instance');
		$form->addText('IqrfInterface', 'IqrfInterface')->setRequired('messages.IqrfInterface');
		$form->addInteger('enableGpioPin', 'enableGpioPin');
		$form->addInteger('spiCe0GpioPin', 'spiCe0GpioPin');
		$form->addInteger('spiMisoGpioPin', 'spiMisoGpioPin');
		$form->addInteger('spiMosiGpioPin', 'spiMosiGpioPin');
		$form->addInteger('spiClkGpioPin', 'spiClkGpioPin');
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		$form->setDefaults($this->manager->load());
		$form->onSuccess[] = function (Form $form, $values) use ($presenter) {
			$this->manager->save($values);
			$presenter->redirect('Iqrf:default');
		};
		return $form;
	}

}
