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
declare(strict_types=1);

namespace App\ConfigModule\Forms;

use App\ConfigModule\Model\GenericManager;
use App\ConfigModule\Presenters\JsCachePresenter;
use App\Forms\FormFactory;
use Nette;
use Nette\Application\UI\Form;

class ConfigJsCacheFormFactory {

	use Nette\SmartObject;

	/**
	 * @var GenericManager Generic configuration manager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * Constructor
	 * @param GenericManager $manager Generic configuration manager
	 * @param FormFactory $factory Generic form factory
	 */
	public function __construct(GenericManager $manager, FormFactory $factory) {
		$this->manager = $manager;
		$this->factory = $factory;
	}

	/**
	 * Create JS cache service configuration form
	 * @param JsCachePresenter $presenter
	 * @return Form JS cache configuration form
	 */
	public function create(JsCachePresenter $presenter): Form {
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('config.js-cache.form'));
		$this->manager->setComponent('iqrf::JsCache');
		$this->manager->setFileName($this->manager->getInstanceFiles()[0]);
		$form->addText('instance', 'instance')->setRequired('messages.instance');
		$form->addText('urlRepo', 'urlRepo')->setRequired('messages.urlRepo');
		$form->addInteger('checkPeriodSec', 'checkPeriodSec');
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		$form->setDefaults($this->manager->load());
		$form->onSuccess[] = function (Form $form, $values) use ($presenter) {
			$this->manager->save($values);
			$presenter->redirect('Homepage:default');
		};
		return $form;
	}

}