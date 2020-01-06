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

use App\ConfigModule\Presenters\MqttPresenter;
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\JsonException;

/**
 * MQTT interface configuration form factory
 */
class MqttFormFactory extends GenericConfigFormFactory {

	use SmartObject;

	/**
	 * Creates the MQTT interface configuration form
	 * @param MqttPresenter $presenter MQTT interface configuration presenter
	 * @return Form MQTT interface configuration form
	 * @throws JsonException
	 */
	public function create(MqttPresenter $presenter): Form {
		$this->manager->setComponent('iqrf::MqttMessaging');
		$this->redirect = 'Mqtt:default';
		$this->presenter = $presenter;
		$qos = ['QoSes.QoS0', 'QoSes.QoS1', 'QoSes.QoS2'];
		$form = $this->factory->create('config.mqtt.form');
		$form->addText('instance', 'instance')
			->setRequired('messages.instance');
		$form->addText('BrokerAddr', 'BrokerAddr')
			->setRequired('messages.BrokerAddr');
		$form->addText('ClientId', 'ClientId')
			->setRequired('messages.ClientId');
		$form->addInteger('Persistence', 'Persistence')
			->setDefaultValue(1);
		$form->addSelect('Qos', 'QoS', $qos)
			->setDefaultValue(1);
		$form->addText('TopicRequest', 'TopicRequest')
			->setRequired('messages.TopicRequest');
		$form->addText('TopicResponse', 'TopicResponse')
			->setRequired('messages.TopicResponse');
		$form->addText('User', 'User');
		$form->addText('Password', 'Password');
		$form->addCheckbox('EnabledSSL', 'EnabledSSL');
		$form->addInteger('KeepAliveInterval', 'KeepAliveInterval')
			->setDefaultValue(20);
		$form->addInteger('ConnectTimeout', 'ConnectTimeout')
			->setDefaultValue(5);
		$form->addInteger('MinReconnect', 'MinReconnect')
			->setDefaultValue(1);
		$form->addInteger('MaxReconnect', 'MaxReconnect')
			->setDefaultValue(64);
		$form->addText('TrustStore', 'TrustStore');
		$form->addText('KeyStore', 'KeyStore');
		$form->addText('PrivateKey', 'PrivateKey');
		$form->addText('PrivateKeyPassword', 'PrivateKeyPassword');
		$form->addText('EnabledCipherSuites', 'EnabledCipherSuites');
		$form->addCheckbox('EnableServerCertAuth', 'EnableServerCertAuth');
		$form->addCheckbox('acceptAsyncMsg', 'acceptAsyncMsg');
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		$id = $presenter->getParameter('id');
		if (isset($id)) {
			$form->setDefaults($this->manager->load((int) $id));
		}
		$form->onSuccess[] = [$this, 'save'];
		return $form;
	}

}
