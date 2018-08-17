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

namespace App\CloudModule\Forms;

use App\CloudModule\Model\AwsManager;
use App\CloudModule\Presenters\AwsPresenter;
use App\CloudModule\Model\InvalidPrivateKeyForCertificate;
use App\Forms\FormFactory;
use App\Model\NonExistingJsonSchemaException;
use App\ServiceModule\Model\NotSupportedInitSystemException;
use App\ServiceModule\Model\ServiceManager;
use GuzzleHttp\Exception\TransferException;
use Nette;
use Nette\Forms\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\IOException;

/**
 * Form for creating MQTT connection into Amazon AWS IoT
 */
class AwsMqttFormFactory {

	use Nette\SmartObject;

	/**
	 * @var AwsManager Amazon AWS IoT manager
	 */
	private $cloudManager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * @var AwsPresenter Amazon AWS IoT presenter
	 */
	private $presenter;

	/**
	 * @var ServiceManager Service manager
	 */
	private $serviceManager;

	/**
	 * Constructor
	 * @param AwsManager $aws Amazon AWS IoT manager
	 * @param FormFactory $factory Generic form factory
	 * @param ServiceManager $serviceManager Service manager
	 */
	public function __construct(AwsManager $aws, FormFactory $factory, ServiceManager $serviceManager) {
		$this->cloudManager = $aws;
		$this->factory = $factory;
		$this->serviceManager = $serviceManager;
	}

	/**
	 * Create MQTT configuration form
	 * @param AwsPresenter $presenter Amazon AWS IoT presenter
	 * @return Form MQTT configuration form
	 */
	public function create(AwsPresenter $presenter): Form {
		$this->presenter = $presenter;
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('cloud.amazonAws.form'));
		$form->addText('endpoint', 'endpoint')->setRequired();
		$form->addUpload('cert', 'certificate')->setRequired();
		$form->addUpload('key', 'pkey')->setRequired();
		$form->addSubmit('save', 'save')
				->onClick[] = function (SubmitButton $button) {
			$this->save($button);
		};
		$form->addSubmit('save_restart', 'save_restart')
				->onClick[] = function (SubmitButton $button) {
			$this->save($button, true);
		};
		$form->addProtection('core.errors.form-timeout');
		return $form;
	}

	/**
	 * Create the MQTT interface
	 * @param SubmitButton $button Form's sumbit button
	 * @param bool $needRestart Is restart needed?
	 */
	public function save(SubmitButton $button, bool $needRestart = false) {
		$values = $button->getForm()->getValues(true);
		try {
			$this->cloudManager->createMqttInterface($values);
			$this->presenter->flashMessage('cloud.messages.success', 'success');
			$this->presenter->redirect(':Config:Mqtt:default');
		} catch (\Exception $e) {
			if ($e instanceof InvalidPrivateKeyForCertificate) {
				$this->presenter->flashMessage('cloud.amazonAws.messages.mismatchedCrtAndKey', 'danger');
			} else if ($e instanceof NonExistingJsonSchemaException) {
				$this->presenter->flashMessage('config.messages.nonExistingJsonSchema', 'danger');
			} else if ($e instanceof IOException) {
				$this->presenter->flashMessage('config.messages.writeFailure', 'danger');
			} else if ($e instanceof TransferException) {
				$this->presenter->flashMessage('cloud.messages.downloadFailure', 'danger');
			} else {
				throw $e;
			}
		}
		if ($needRestart) {
			try {
				$this->serviceManager->restart();
				$this->presenter->flashMessage('service.actions.restart.message', 'info');
			} catch (NotSupportedInitSystemException $e) {
				$this->presenter->flashMessage('service.errors.unsupportedInit', 'danger');
			}
		}
	}

}