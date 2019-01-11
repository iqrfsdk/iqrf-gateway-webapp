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

use App\ConfigModule\Exceptions\InvalidConfigurationFormatException;
use App\ConfigModule\Models\SchedulerMigrationManager;
use App\ConfigModule\Presenters\MigrationPresenter;
use App\CoreModule\Exceptions\NonExistingJsonSchemaException;
use App\CoreModule\Forms\FormFactory;
use Nette\Forms\Form;
use Nette\IOException;
use Nette\SmartObject;

/**
 * Scheduler's configuration migration form factory
 */
class SchedulerMigrationFormFactory {

	use SmartObject;

	/**
	 * @var SchedulerMigrationManager Scheduler's configuration migration manager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * @var MigrationPresenter Configuration migration presenter
	 */
	private $presenter;

	/**
	 * Constructor
	 * @param FormFactory $factory Generic form factory
	 * @param SchedulerMigrationManager $manager Scheduler's configuration migration manager
	 */
	public function __construct(FormFactory $factory, SchedulerMigrationManager $manager) {
		$this->factory = $factory;
		$this->manager = $manager;
	}

	/**
	 * Create configuration migration form
	 * @param MigrationPresenter $presenter Configuration migration presenter
	 * @return Form Configuration migration form
	 */
	public function create(MigrationPresenter $presenter): Form {
		$this->presenter = $presenter;
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('config.schedulerMigration'));
		$form->addUpload('configuration', 'configuration')->setRequired('messages.configuration');
		$form->addSubmit('import', 'import');
		$form->addProtection('core.errors.form-timeout');
		$form->onSuccess[] = [$this, 'import'];
		return $form;
	}

	/**
	 * Import a configuration
	 * @param Form $form Configuration migration form
	 */
	public function import(Form $form): void {
		try {
			$this->manager->upload($form->getValues(true));
			$this->presenter->flashMessage('config.migration.messages.importedConfig', 'success');
		} catch (InvalidConfigurationFormatException $e) {
			$this->presenter->flashMessage('config.migration.errors.invalidFormat', 'danger');
		} catch (NonExistingJsonSchemaException $e) {
			$this->presenter->flashMessage('config.messages.writeFailures.nonExistingJsonSchema', 'danger');
		} catch (IOException $e) {
			/// TODO: Use custom error message.
			$$this->presenter->flashMessage('config.messages.writeFailures.ioError', 'danger');
		} finally {
			$this->presenter->redirect('Homepage:default');
		}
	}

}