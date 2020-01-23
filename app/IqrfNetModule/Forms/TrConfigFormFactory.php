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

namespace App\IqrfNetModule\Forms;

use App\CoreModule\Forms\FormFactory;
use App\IqrfNetModule\Exceptions\DpaErrorException;
use App\IqrfNetModule\Exceptions\EmptyResponseException;
use App\IqrfNetModule\Exceptions\UserErrorException;
use App\IqrfNetModule\Models\TrConfigManager;
use App\IqrfNetModule\Presenters\TrConfigPresenter;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\SmartObject;
use Nette\Utils\JsonException;

/**
 * IQRF TR configuration form factory
 */
class TrConfigFormFactory {

	use SmartObject;

	/**
	 * @var mixed[] TR configuration
	 */
	protected $configuration = [];

	/**
	 * @var TrConfigManager IQRF TR configuration manager
	 */
	protected $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	protected $factory;

	/**
	 * @var TrConfigPresenter IQRF TR configuration presenter presenter
	 */
	protected $presenter;

	/**
	 * Constructor
	 * @param FormFactory $factory Generic form factory
	 * @param TrConfigManager $manager IQRF TR configuration manager
	 */
	public function __construct(FormFactory $factory, TrConfigManager $manager) {
		$this->factory = $factory;
		$this->manager = $manager;
	}

	/**
	 * Creates IQRF TR configuration form
	 * @param TrConfigPresenter $presenter IQRF TR configuration presenter
	 * @return Form IQRF TR configuration form
	 */
	public function create(TrConfigPresenter $presenter): Form {
		$this->presenter = $presenter;
		$this->load();
		$form = $this->factory->create('iqrfnet.trConfig');
		$this->addRfConfiguration($form);
		$this->addRfpgwConfiguration($form);
		$this->addDpaEmbeddedPeripherals($form);
		$this->addDpaOtherConfiguration($form);
		$form->addSubmit('save', 'save');
		$form->addProtection('core.errors.form-timeout');
		$form->setDefaults($this->configuration);
		$form->onSuccess[] = [$this, 'save'];
		return $form;
	}

	/**
	 * Adds RF configuration to the form
	 * @param Form $form IQRF RF configuration form
	 */
	private function addRfConfiguration(Form &$form): void {
		$form->addGroup('rf');
		$rfBands = ['433', '868', '916'];
		foreach ($rfBands as $key => $rfBand) {
			$rfBands[$rfBand] = 'rfBands.' . $rfBand;
			unset($rfBands[$key]);
		}
		$form->addSelect('rfBand', 'rfBand', $rfBands)->setDisabled();
		if (array_key_exists('rfBand', $this->configuration)) {
			$form['rfBand']->setDefaultValue($this->configuration['rfBand']);
		}
		$rfChannels = ['rfChannelA', 'rfChannelB'];
		foreach ($rfChannels as $rfChannel) {
			$form->addInteger($rfChannel, $rfChannel);
			$this->setRfChannelRule($form[$rfChannel]);
		}
		$subChannels = ['rfSubChannelA', 'rfSubChannelB'];
		foreach ($subChannels as $subChannel) {
			if (array_key_exists($subChannel, $this->configuration)) {
				$form->addInteger($subChannel, $subChannel);
				$this->setRfChannelRule($form[$subChannel]);
			}
		}
		if (array_key_exists('stdAndLpNetwork', $this->configuration)) {
			$warning = $form->getTranslator()->translate('messages.breakInteroperability');
			$form->addCheckbox('stdAndLpNetwork', 'stdAndLpNetwork')
				->setHtmlAttribute('data-warning', $warning);
		}
		$form->addInteger('txPower', 'txPower')
			->addRule(Form::RANGE, 'messages.txPower', [0, 7])
			->setRequired('messages.txPower');
		$form->addInteger('rxFilter', 'rxFilter')
			->addRule(Form::RANGE, 'messages.rxFilter', [0, 64])
			->setRequired('messages.rxFilter');
		$form->addInteger('lpRxTimeout', 'lpRxTimeout')
			->addRule(Form::RANGE, 'messages.lpRxTimeout', [1, 255])
			->setRequired('messages.lpRxTimeout');
	}

	/**
	 * Sets rules for RF channel input
	 * @param TextInput $input RF channel input
	 */
	private function setRfChannelRule(TextInput $input): void {
		$rfBand = $this->configuration['rfBand'] ?? null;
		switch ($rfBand) {
			case '443':
				$input->addRule(Form::RANGE, 'messages.rfChannel443', [0, 16]);
				break;
			case '868':
				$input->addRule(Form::RANGE, 'messages.rfChannel868', [0, 67]);
				break;
			case '916':
				$input->addRule(Form::RANGE, 'messages.rfChannel916', [0, 255]);
				break;
			default:
				$input->setDisabled();
				break;
		}
	}

	/**
	 * Adds RFPGM configuration to the form
	 * @param Form $form IQRF RF configuration form
	 */
	private function addRfpgwConfiguration(Form &$form): void {
		$form->addGroup('rfPgm');
		$form->addCheckbox('rfPgmEnableAfterReset', 'rfPgmEnableAfterReset');
		$form->addCheckbox('rfPgmTerminateAfter1Min', 'rfPgmTerminateAfter1Min');
		$form->addCheckbox('rfPgmTerminateMcuPin', 'rfPgmTerminateMcuPin');
		$form->addCheckbox('rfPgmDualChannel', 'rfPgmDualChannel');
		$form->addCheckbox('rfPgmLpMode', 'rfPgmLpMode');
		$form->addCheckbox('rfPgmIncorrectUpload', 'rfPgmIncorrectUpload')->setDisabled();
		if (array_key_exists('rfPgmIncorrectUpload', $this->configuration)) {
			$form['rfPgmIncorrectUpload']->setDefaultValue($this->configuration['rfPgmIncorrectUpload']);
		}
	}

	/**
	 * Adds embedded peripherals to the form
	 * @param Form $form DPA configuration form
	 */
	private function addDpaEmbeddedPeripherals(Form &$form): void {
		$form->addGroup('dpaEmbeddedPeripherals');
		$embeddedPeripherals = $form->addContainer('embPers');
		$unchangeablePeripherals = ['coordinator', 'node', 'os'];
		if ($this->presenter->getUser()->isInRole('power')) {
			foreach ($unchangeablePeripherals as $peripheral) {
				$embeddedPeripherals->addCheckbox($peripheral, 'embPers.' . $peripheral)
					->setDisabled();
				if (array_key_exists('embPers', $this->configuration)) {
					$embeddedPeripherals[$peripheral]->setValue($this->configuration['embPers'][$peripheral]);
				}
			}
		}
		$changeablePeripherals = ['eeprom', 'eeeprom', 'ram', 'ledr', 'ledg', 'spi', 'io', 'thermometer', 'uart', 'frc'];
		foreach ($changeablePeripherals as $peripheral) {
			$embeddedPeripherals->addCheckbox($peripheral, 'embPers.' . $peripheral);
		}
	}

	/**
	 * Add other configuration to the form
	 * @param Form $form DPA configuration form
	 */
	private function addDpaOtherConfiguration(Form &$form): void {
		$form->addGroup('dpaOther');
		$form->addCheckbox('customDpaHandler', 'customDpaHandler');
		$form->addCheckbox('ioSetup', 'ioSetup');
		$form->addCheckbox('dpaAutoexec', 'dpaAutoexec');
		$form->addCheckbox('routingOff', 'routingOff');
		$form->addCheckbox('peerToPeer', 'peerToPeer');
		if (array_key_exists('dpaPeerToPeer', $this->configuration)) {
			$form->addCheckbox('dpaPeerToPeer', 'dpaPeerToPeer');
		}
		if (array_key_exists('neverSleep', $this->configuration)) {
			$form->addCheckbox('neverSleep', 'neverSleep');
		}
		$uartBaudrates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200, 230400];
		foreach ($uartBaudrates as $key => $baudrate) {
			$uartBaudrates[$baudrate] = 'uartBaudrates.' . $baudrate;
			unset($uartBaudrates[$key]);
		}
		$form->addSelect('uartBaudrate', 'uartBaudrate', $uartBaudrates);
		if (array_key_exists('nodeDpaInterface', $this->configuration)) {
			$form->addCheckbox('nodeDpaInterface', 'nodeDpaInterface');
		}
	}

	/**
	 * Loads IQRF TR configuration into the form
	 */
	private function load(): void {
		$address = (int) $this->presenter->getParameter('address', 0);
		try {
			$dpa = $this->manager->read($address);
		} catch (DpaErrorException | EmptyResponseException | JsonException | UserErrorException $e) {
			return;
		}
		if (!array_key_exists('response', $dpa)) {
			return;
		}
		$this->configuration = $dpa['response']['data']['rsp'];
		if (array_key_exists('stdAndLpNetwork', $this->configuration)) {
			$this->configuration['stdAndLpNetwork'] = (int) $this->configuration['stdAndLpNetwork'];
		}
	}

	/**
	 * Writes IQRF TR configuration from the form
	 * @param Form $form Set TR configuration form
	 */
	public function save(Form $form): void {
		$address = $this->presenter->getParameter('id', 0);
		$config = $form->getValues('array');
		if (array_key_exists('stdAndLpNetwork', $config)) {
			$config['stdAndLpNetwork'] = boolval($config['stdAndLpNetwork']);
		}
		try {
			$this->manager->write($address, $config);
			$this->presenter->flashSuccess('iqrfnet.trConfiguration.write.success');
		} catch (DpaErrorException | EmptyResponseException | JsonException | UserErrorException $e) {
			$this->presenter->flashError('iqrfnet.trConfiguration.write.failure');
		}
	}

}
