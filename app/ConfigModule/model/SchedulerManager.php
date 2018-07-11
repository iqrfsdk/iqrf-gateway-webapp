<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2018 IQRF Tech s.r.o.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
declare(strict_types=1);

namespace App\ConfigModule\Model;

use App\ConfigModule\Model\GenericManager;
use App\ConfigModule\Model\MainManager;
use App\Model\JsonFileManager;
use Nette;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class SchedulerManager {

	use Nette\SmartObject;

	/**
	 * @var GenericManager Generic config manager
	 */
	private $genericConfigManager;

	/**
	 * @var MainManager Main configuration manager
	 */
	private $mainConfigManager;

	/**
	 * @var JsonFileManager JSON file manager
	 */
	private $fileManager;

	/**
	 * @var string File name (without .json)
	 */
	private $fileName = 'Tasks';

	/**
	 * Constructor
	 * @param MainManager $mainManager Main configuration manager
	 * @param GenericManager $genericManager Generic configuration manager
	 */
	public function __construct(MainManager $mainManager, GenericManager $genericManager) {
		$this->genericConfigManager = $genericManager;
		$this->mainConfigManager = $mainManager;
		$path = $this->mainConfigManager->load()['cacheDir'] . '/Scheduler';
		$this->fileManager = new JsonFileManager($path);
	}

	/**
	 * Add task
	 * @param string $type Task type
	 */
	public function add(string $type) {
		$json = $this->fileManager->read($this->fileName);
		$taskManager = new JsonFileManager(__DIR__ . '/../json');
		$tasks = $taskManager->read('Scheduler');
		$task = array_key_exists($type, $tasks) ? $tasks[$type] : null;
		array_push($json['TasksJson'], $task);
		$this->fileManager->write($this->fileName, $json);
	}

	/**
	 * Delete task
	 * @param int $id Task ID
	 */
	public function delete(int $id) {
		$json = $this->fileManager->read($this->fileName);
		unset($json['TasksJson'][$id]);
		$json['TasksJson'] = array_values($json['TasksJson']);
		$this->fileManager->write($this->fileName, $json);
	}

	/**
	 * Fix HWPID format
	 * @param string $hwpid HWPID to fix
	 * @return string Fixed HWPID
	 */
	public function fixHwpid(string $hwpid): string {
		$data = str_split($hwpid, 2);
		return $data[1] . '.' . $data[0];
	}

	/**
	 * Get last ID
	 * @return int Last ID in array
	 */
	public function getLastId(): int {
		$json = $this->fileManager->read($this->fileName);
		$tasks = $json['TasksJson'];
		end($tasks);
		return key($tasks);
	}

	/**
	 * Get DPA request from JSON
	 * @param array $data JSON
	 * @return string DPA request
	 */
	public function getRequest(array $data) {
		if ($data['type'] === 'raw') {
			return $data['request'];
		}
		$nadr = (empty($data['nadr']) ? '00' : Strings::padLeft($data['nadr'], 2, '0')) . '.00.';
		$hwpid = (!empty($data['hwpid']) ? $this->fixHwpid($data['hwpid']) : 'ff.ff');
		switch ($data['type']) {
			case 'raw-hdp':
				$pnum = Strings::padLeft($data['pnum'], 2, '0') . '.';
				$pcmd = Strings::padLeft($data['pcmd'], 2, '0') . '.';
				if (!empty($data['req_data'])) {
					$pdata = '.' . $data['req_data'];
				} else if (!empty($data['rdata'])) {
					$pdata = '.' . $data['rdata'];
				} else {
					$pdata = '';
				}
				return $nadr . $pnum . $pcmd . $hwpid . $pdata;
		}
	}

	/**
	 * Get available messagings
	 * @return array Available messagings
	 */
	public function getMessagings(): array {
		$components = [
			'iqrf::MqMessaging', 'iqrf::MqttMessaging', 'iqrf::WebsocketMessaging'
		];
		$messagings = [];
		foreach ($components as $components) {
			$this->genericConfigManager->setComponent($components);
			foreach ($this->genericConfigManager->getInstances() as $instance) {
				$messagings[] = $instance['instance'];
			}
		}
		return $messagings;
	}

	/**
	 * Get scheduler's services
	 * @return array Scheduler's services
	 */
	public function getServices(): array {
		$services = [];
		$this->genericConfigManager->setComponent('iqrf::SchedulerMessaging');
		foreach ($this->genericConfigManager->getInstances() as $instance) {
			$services[] = $instance['instance'];
		}
		return $services;
	}

	/**
	 * Get tasks in Scheduler
	 * @return array Tasks
	 */
	public function getTasks(): array {
		$jsonTasks = $this->fileManager->read($this->fileName)['TasksJson'];
		$tasks = [];
		foreach ($jsonTasks as $json) {
			$task['time'] = $json['time'];
			$task['service'] = $json['service'];
			$task['messaging'] = $json['task']['messaging'];
			$message = $json['task']['message'];
			$task['type'] = $message['ctype'] . ' | ' . $message['type'];
			$task['request'] = $this->getRequest($message);
			$task['id'] = array_keys($jsonTasks, $json, true)[0];
			array_push($tasks, $task);
		}
		return $tasks;
	}

	/**
	 * Convert Task JSON array to Task configuration form array
	 * @param int $id Task ID
	 * @return array Array for form
	 */
	public function load(int $id = 0): array {
		$json = $this->fileManager->read($this->fileName);
		$tasks = $json['TasksJson'];
		if ($id > count($tasks)) {
			return [];
		}
		return $tasks[$id];
	}

	/**
	 * Save scheduler setting
	 * @param ArrayHash $array Scheduler settings
	 * @param int $id Task ID
	 */
	public function save(ArrayHash $array, int $id = 0) {
		$json = $this->saveJson($this->fileManager->read($this->fileName), $array, $id);
		$this->fileManager->write($this->fileName, $json);
	}

	/**
	 * Convert Task configuration form array to JSON array
	 * @param array $scheduler Original Task JSON array
	 * @param ArrayHash $update Changed settings
	 * @param int $id Task ID
	 * @return array JSON array
	 */
	public function saveJson(array $scheduler, ArrayHash $update, int $id): array {
		$update['task']['message'] = (array) $update['task']['message'];
		$update['task'] = (array) $update['task'];
		$scheduler['TasksJson'][$id] = (array) $update;
		return $scheduler;
	}

}
