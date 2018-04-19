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

use App\ConfigModule\Model\BaseServiceManager;
use App\ConfigModule\Model\SchedulerManager;
use App\ConfigModule\Presenters\SchedulerPresenter;
use App\Forms\FormFactory;
use Nette;
use Nette\Application\UI\Form;

class ConfigSchedulerFormFactory {

	use Nette\SmartObject;

	/**
	 * @var BaseServiceManager Base service manager
	 */
	private $baseService;

	/**
	 * @var SchedulerManager Scheduler manager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * Constructor
	 * @param FormFactory $factory Generic form factory
	 * @param SchedulerManager $manager Scheduler manager
	 */
	public function __construct(FormFactory $factory, SchedulerManager $manager, BaseServiceManager $baseService) {
		$this->manager = $manager;
		$this->factory = $factory;
		$this->baseService = $baseService;
	}

	/**
	 * Create Scheduler configuration form
	 * @param SchedulerPresenter $presenter
	 * @return Form Scheduler configuration form
	 */
	public function create(SchedulerPresenter $presenter): Form {
		$id = intval($presenter->getParameter('id'));
		$form = $this->factory->create();
		$defaults = $this->manager->load($id);
		$types = [
			'raw', 'raw-hdp', 'std-per-thermometer', 'std-per-ledg',
			'std-per-ledr', 'std-per-frc', 'std-per-io', 'std-sen',
		];
		$baseServices = $this->baseService->getServicesNames();
		$baseService = $defaults['service'];
		foreach (array_keys($defaults) as $key) {
			switch ($key) {
				case 'hwpid':
					$form->addText($key, $key)
							->setRequired(false)
							->addRule(Form::PATTERN, 'It has to contain hexadecimal number - ' . $key, '[0-9A-Fa-f]{4}')
							->addRule(Form::MAX_LENGTH, 'It has to have maximal length of 4 chars.', 4);
					break;
				case 'pcmd':
				case 'pnum':
				case 'nadr':
					$form->addText($key, $key)
							->setRequired(false)
							->addRule(Form::PATTERN, 'It has to contain hexadecimal number - ' . $key, '[0-9A-Fa-f]{1,2}')
							->addRule(Form::MAX_LENGTH, 'It has to have maximal length of 2 chars.', 2);
					break;
				case 'sensors':
					$form->addTextArea($key, $key);
					break;
				case 'timeout':
					$form->addInteger($key, $key);
					break;
				case 'type':
					$form->addSelect($key, $key)->setItems($types, false)
							->setRequired();
					break;
				case 'service':
					$form->addSelect($key, $key)->setItems($baseServices, false)
							->setPrompt('Select Base service')->setRequired();
					break;
				default:
					$form->addText($key, $key);
			}
		}
		if (!in_array($baseService, $baseServices)) {
			unset($defaults['service']);
		}
		$form->addSubmit('save', 'Save');
		$form->setDefaults($defaults);
		$form->addProtection('Timeout expired, resubmit the form.');
		$form->onSuccess[] = function (Form $form, $values) use ($presenter, $id) {
			$this->manager->save($values, $id);
			$presenter->redirect('Scheduler:default');
		};
		return $form;
	}

}
