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

namespace App\IqrfNetModule\Models;

use App\IqrfNetModule\Entities\Dpa;
use App\IqrfNetModule\Enums\DpaInterfaces;
use App\IqrfNetModule\Enums\RfModes;
use App\IqrfNetModule\Enums\TrSeries;
use App\Models\Database\EntityManager;
use App\Models\Database\Repositories\IqrfOsPatchRepository;
use Iqrf\Repository\Models\OsAndDpaManager;

/**
 * IQRF OS manager
 */
class IqrfOsManager {

	/**
	 * @var DpaManager DPA manager
	 */
	private $dpaManager;

	/**
	 * @var OsAndDpaManager IQRF OS and DPA manager
	 */
	private $osDpaManager;

	/**
	 * @var IqrfOsPatchRepository IQRF OS patch database repository
	 */
	private $repository;

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 */
	public function __construct(DpaManager $dpaManager, OsAndDpaManager $osDpaManager, EntityManager $entityManager) {
		$this->dpaManager = $dpaManager;
		$this->osDpaManager = $osDpaManager;
		$this->repository = $entityManager->getIqrfOsPatchRepository();
	}

	/**
	 * Lists all IQRF OS patches
	 * @return array<int, array<string, int|string>> IQRF OS patches
	 */
	public function listOsPatches(): array {
		$patches = [];
		foreach ($this->repository->findAll() as $patch) {
			array_push($patches, $patch->jsonSerialize());
		}
		return $patches;
	}

	/**
	 * Lists available IQRF OS upgrades
	 * @param string $currentVersion Current version of IQRF OS
	 * @param string $currentBuild Current build of IQRF OS
	 * @param int $mcuType Module MCU type
	 * @return array<int, array<string, int|string>> Available IQRF OS upgrades
	 */
	public function listOsUpgrades(string $currentVersion, string $currentBuild, int $mcuType): array {
		$versions = [];
		if ($mcuType !== 4) {
			return $versions;
		}
		$patches = $this->repository->findBy(['fromBuild' => hexdec($currentBuild), 'part' => 1]);
		foreach ($patches as $patch) {
			$toBuild = str_pad(dechex($patch->getToBuild()), 4, '0', STR_PAD_LEFT);
			$toVersion = strval($patch->getToVersion());
			if ($toVersion <= $currentVersion || $toBuild <= $currentBuild) {
				continue;
			}
			foreach ($this->osDpaManager->get($toBuild) as $dpa) {
				$upgrade = $dpa->jsonSerialize();
				$upgrade['osVersion'] = $toVersion;
				if (hexdec($dpa->getDpa()) < 0x400) {
					$upgrade['dpa'] = $dpa->getDpa(true) . ', LP';
					array_push($versions, $upgrade);
					$upgrade['dpa'] = $dpa->getDpa(true) . ', STD';
				}
				array_push($versions, $upgrade);
			}
		}
		return $versions;
	}

	/**
	 * Retrieves names of files to be used in IQRF OS upgrade
	 * @param array<string, int|string> $request API request body
	 * @return array<string, string|array<int, string>> Array containing names of files to be used in upgrade
	 */
	public function getUpgradeFiles(array $request): array {
		$dpaFile = $this->getDpaFileName($request);
		$osFiles = $this->getOsFileNames($request);
		return ['dpa' => $dpaFile, 'os' => $osFiles];
	}

	/**
	 * Retrieves DPA file name for upgrade
	 * @param array<string, int|string> $request API request body
	 * @return string Name of DPA file
	 */
	private function getDpaFileName(array $request): string {
		$iface = DpaInterfaces::fromScalar($request['interface']);
		$trSeries = TrSeries::fromTrMcuType($request['trMcuType']);
		$rfMode = isset($request['rfMode']) ? RfModes::fromScalar($request['rfMode']) : null;
		$dpa = new Dpa($request['dpa'], $iface, $trSeries, $rfMode);
		return $this->dpaManager->getFile($request['toBuild'], $dpa);
	}

	/**
	 * Retrieves OS file names for upgrade
	 * @param array<string, int|string> $request API request body
	 * @return array<int, string> Name of DPA file
	 */
	private function getOsFileNames(array $request): array {
		$files = [];
		$oldVersion = intval($request['fromVersion']);
		$newVersion = intval($request['toVersion']);
		$oldBuild = hexdec($request['fromBuild']);
		$newBuild = hexdec($request['toBuild']);
		$patches = $this->repository->findBy(['fromVersion' => $oldVersion, 'toVersion' => $newVersion, 'fromBuild' => $oldBuild, 'toBuild' => $newBuild]);
		foreach ($patches as $patch) {
			array_push($files, $patch->getFileName());
		}
		return $files;
	}

}
