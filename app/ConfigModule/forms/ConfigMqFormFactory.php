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
declare(strict_types=1);

namespace App\ConfigModule\Forms;

use App\ConfigModule\Model\GenericManager;
use App\ConfigModule\Presenters\MqPresenter;
use App\Forms\FormFactory;
use Nette;
use Nette\Forms\Form;

class ConfigMqFormFactory {

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
	 * Create MQTT configuration form
	 * @param MqPresenter $presenter
	 * @return Form MQTT configuration form
	 */
	public function create(MqPresenter $presenter): Form {
		$id = intval($presenter->getParameter('id'));
		$this->manager->setComponent('iqrf::MqMessaging');
		$instances = $this->manager->getInstanceFiles();
		$instanceExist = array_key_exists($id, $instances);
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('config.mq.form'));
		$form->addText('instance', 'instance')->setRequired('messages.instance');
		$form->addText('LocalMqName', 'LocalMqName')->setRequired('messages.LocalMqName');
		$form->addText('RemoteMqName', 'RemoteMqName')->setRequired('messages.RemoteMqName');
		$form->addCheckbox('acceptAsyncMsg', 'acceptAsyncMsg');
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		if ($instanceExist) {
			$this->manager->setFileName($instances[$id]);
			$form->setDefaults($this->manager->load());
		}
		$form->onSuccess[] = function (Form $form, $values) use ($presenter, $instanceExist) {
			if (!$instanceExist) {
				$this->manager->setFileName('iqrf__' . $values['instance']);
			}
			$this->manager->save($values);
			$presenter->redirect('Mq:default');
		};
		return $form;
	}

}
