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

namespace App\ConfigModule\Presenters;

use App\ConfigModule\Datagrids\WebsocketDataGridFactory;
use App\ConfigModule\Datagrids\WebsocketMessagingDataGridFactory;
use App\ConfigModule\Datagrids\WebsocketServiceDataGridFactory;
use App\ConfigModule\Forms\WebsocketFormFactory;
use App\ConfigModule\Forms\WebsocketMessagingFormFactory;
use App\ConfigModule\Forms\WebsocketServiceFormFactory;
use App\ConfigModule\Model\GenericManager;
use App\ConfigModule\Model\WebsocketManager;
use Nette\Forms\Form;
use Ublaboo\DataGrid\DataGrid;

/**
 * Websocket interface configuration presenter
 */
class WebsocketPresenter extends GenericPresenter {

	/**
	 * @var array Websocket components
	 */
	protected $components = [
		'messaging' => 'iqrf::WebsocketMessaging',
		'service' => 'shape::WebsocketCppService',
	];

	/**
	 * @var WebsocketManager Websocket manager
	 */
	private $websocketManager;

	/**
	 * @var WebsocketDataGridFactory Websocket interface datagrid factory
	 * @inject
	 */
	public $basicDataGridFactory;

	/**
	 * @var WebsocketFormFactory Websocket instance configuration form factory
	 * @inject
	 */
	public $basicFormFactory;

	/**
	 * @var WebsocketMessagingDataGridFactory Websocket messaging configuration datagrid factory
	 * @inject
	 */
	public $messagingDataGridFactory;

	/**
	 * @var WebsocketMessagingFormFactory Websocket messaging configuration form factory
	 * @inject
	 */
	public $messagingFormFactory;

	/**
	 * @var WebsocketServiceDataGridFactory Websocket service configuration data grid
	 * @inject
	 */
	public $serviceDataGridFactory;

	/**
	 * @var WebsocketServiceFormFactory Websocket service configuration form factory
	 * @inject
	 */
	public $serviceFormFactory;

	/**
	 * Constructor
	 * @param GenericManager $genericManager Generic configuration manager
	 * @param WebsocketManager $websocketManager Websocket configuration manager
	 */
	public function __construct(GenericManager $genericManager, WebsocketManager $websocketManager) {
		$this->websocketManager = $websocketManager;
		parent::__construct($this->components, $genericManager);
	}

	/**
	 * Edit Websocket interface
	 * @param int $id ID of websocket interface
	 */
	public function renderEdit(int $id): void {
		$this->template->id = $id;
	}

	/**
	 * Edit Websocket messaging
	 * @param int $id ID of websocket messaging
	 */
	public function renderEditMessaging(int $id): void {
		$this->configManager->setComponent($this->components['messaging']);
		$this->template->id = $id;
	}

	/**
	 * Edit Websocket service
	 * @param int $id ID of websocket service
	 */
	public function renderEditService(int $id): void {
		$this->configManager->setComponent($this->components['service']);
		$this->template->id = $id;
	}

	/**
	 * Delete Websocket interface
	 * @param int $id ID of websocket interface
	 */
	public function actionDelete(int $id): void {
		$this->websocketManager->delete($id);
		$this->redirect('Websocket:default');
		$this->setView('default');
	}

	/**
	 * Delete Websocket messaging
	 * @param int $id ID of websocket messaging
	 */
	public function actionDeleteMessaging(int $id): void {
		$this->configManager->setComponent($this->components['messaging']);
		$this->configManager->delete($id);
		$this->redirect('Websocket:default');
		$this->setView('default');
	}

	/**
	 * Delete Websocket service
	 * @param int $id ID of websocket service
	 */
	public function actionDeleteService(int $id): void {
		$this->configManager->setComponent($this->components['service']);
		$this->configManager->delete($id);
		$this->redirect('Websocket:default');
		$this->setView('default');
	}

	/**
	 * Create websocket interfaces datagrid
	 * @param string $name Datagrid's component name
	 * @return DataGrid Websocket interfaces datagrid
	 */
	protected function createComponentConfigWebsocketDataGrid(string $name): DataGrid {
		return $this->basicDataGridFactory->create($this, $name);
	}

	/**
	 * Create websocket interface form
	 * @return Form Websocket interface form
	 */
	protected function createComponentConfigWebsocketForm(): Form {
		return $this->basicFormFactory->create($this);
	}

	/**
	 * Create websocket messaging datagrid
	 * @param string $name Datagrid's component name
	 * @return DataGrid Websocket messaging datagrid
	 */
	protected function createComponentConfigWebsocketMessagingDataGrid(string $name): DataGrid {
		return $this->messagingDataGridFactory->create($this, $name);
	}

	/**
	 * Create websocket messaging form
	 * @return Form Websocket messaging form
	 */
	protected function createComponentConfigWebsocketMessagingForm(): Form {
		return $this->messagingFormFactory->create($this);
	}

	/**
	 * Create websocket service datagrid
	 * @param string $name Datagrid's component name
	 * @return DataGrid Websocket service datagrid
	 */
	protected function createComponentConfigWebsocketServiceDataGrid(string $name): DataGrid {
		return $this->serviceDataGridFactory->create($this, $name);
	}

	/**
	 * Create websocket service form
	 * @return Form Websocket service form
	 */
	protected function createComponentConfigWebsocketServiceForm(): Form {
		return $this->serviceFormFactory->create($this);
	}

}
