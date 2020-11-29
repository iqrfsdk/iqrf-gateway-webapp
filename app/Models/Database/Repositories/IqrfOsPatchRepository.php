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

namespace App\Models\Database\Repositories;

use App\Models\Database\Entities\IqrfOsPatch;
use Doctrine\ORM\EntityRepository;
use function assert;

/**
 * IQRF OS patch repository
 */
class IqrfOsPatchRepository extends EntityRepository {

	/**
	 * Retrieves list of all patch details
	 * @return array<int, array<int|string>> Array of OS patch detail
	 */
	public function getOsPatchDetails(): array {
		$patches = [];
		foreach ($this->findAll() as $patch) {
			assert($patch instanceof IqrfOsPatch);
			array_push($patches, [$patch->getId(), $patch->getModuleType(), $patch->getFromVersion(), $patch->getFromBuild(), $patch->getToVersion(), $patch->getToBuild(), $patch->getPart(), $patch->getParts(), $patch->getFileName()]);
		}
		return $patches;
	}

}
