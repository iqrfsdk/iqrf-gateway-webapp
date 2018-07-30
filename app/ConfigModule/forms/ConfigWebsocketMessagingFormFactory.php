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
use App\ConfigModule\Presenters\WebsocketPresenter;
use App\Forms\FormFactory;
use Nette;
use Nette\Forms\Form;
use Nette\IOException;

class ConfigWebsocketMessagingFormFactory {

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
	 * Create websocket messaging configuration form
	 * @param WebsocketPresenter $presenter
	 * @return Form Websocket messaging configuration form
	 */
	public function create(WebsocketPresenter $presenter): Form {
		$id = intval($presenter->getParameter('id'));
		$form = $this->factory->create();
		$translator = $form->getTranslator();
		$form->setTranslator($translator->domain('config.websocket.messaging.form'));
		$this->manager->setComponent('iqrf::WebsocketMessaging');
		$instances = $this->manager->getInstanceFiles();
		$instanceExist = array_key_exists($id, $instances);
		if ($instanceExist) {
			$this->manager->setFileName($instances[$id]);
			$defaults = $this->manager->load();
		} else {
			$defaults = ['RequiredInterfaces' => [['name' => 'shape::IWebsocketService', 'target' => ['instance' => '']]]];
		}
		$form->addText('instance', 'instance')->setRequired('messages.instance');
		$form->addCheckbox('acceptAsyncMsg', 'acceptAsyncMsg');
		$requiredInterfaces = $form->addContainer('RequiredInterfaces');
		foreach ($defaults['RequiredInterfaces'] as $id => $requiredInterface) {
			$container = $requiredInterfaces->addContainer($id);
			$container->addSelect('name', 'config.websocket.messaging.form.requiredInterface.name')
					->setItems(['shape::IWebsocketService',], false)
					->setTranslator($translator)
					->setRequired('messages.requiredInterface.name');
			$target = $container->addContainer('target');
			$target->addSelect('instance', 'config.websocket.messaging.form.requiredInterface.instance')
					->setItems($this->manager->getComponentInstances('shape::WebsocketService'), false)
					->setTranslator($translator)
					->setRequired('messages.requiredInterface.instance');
		}
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		if ($instanceExist) {
			$form->setDefaults($defaults);
		}
		$form->onSuccess[] = function (Form $form, $values) use ($presenter, $instanceExist) {
			if (!$instanceExist) {
				$this->manager->setFileName('iqrf__' . $values['instance']);
			}
			try {
				$this->manager->save($values);
				$presenter->flashMessage('config.messages.success', 'success');
			} catch (IOException $e) {
				$presenter->flashMessage('config.messages.writeFailure', 'danger');
			} finally {
				$presenter->redirect('Websocket:default');
			}
		};
		return $form;
	}

}
